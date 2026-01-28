<?php
namespace Lightpack\AI;

class TaskBuilder
{
    protected array $messages = [];
    protected $provider;
    protected ?string $prompt = null;
    protected ?array $expectSchema = null;
    protected ?string $expectArrayKey = null;
    protected array $requiredFields = [];
    protected array $errors = [];
    protected ?array $example = null;
    protected ?string $model = null;
    protected ?float $temperature = null;
    protected ?int $maxTokens = null;
    protected ?string $system = null;
    protected ?string $rawResponse = null;
    protected ?bool $useCache = null;
    protected ?int $cacheTtl = null;
    protected array $tools = [];
    
    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    /**
     * Add a message to the chat history (role: user, system, assistant).
     */
    public function message(string $role, string $content): self
    {
        $this->messages[] = ['role' => $role, 'content' => $content];
        return $this;
    }

    public function prompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function expect(array $schema): self
    {
        // If $schema is a list of keys (numeric), default all types to 'string'
        $normalized = [];
        foreach ($schema as $key => $type) {
            if (is_int($key)) {
                $normalized[$type] = 'string';
            } else {
                // Handle both 'key' => 'type' and 'key' => ['type', 'description']
                if (is_array($type)) {
                    $normalized[$key] = $type[0]; // Extract just the type
                } else {
                    $normalized[$key] = $type;
                }
            }
        }
        $this->expectSchema = $normalized;
        return $this;
    }

    /**
     * Specify required fields for the result.
     */
    public function required(string ...$fields): self
    {
        $this->requiredFields = $fields;
        return $this;
    }

    public function expectArray(string $key = 'item'): self
    {
        $this->expectArrayKey = $key;
        return $this;
    }

    public function example(array $example): self
    {
        $this->example = $example;
        return $this;
    }

    public function model(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    public function system(string $system): self
    {
        $this->system = $system;
        return $this;
    }

    public function cache(bool $useCache): self
    {
        $this->useCache = $useCache;
        return $this;
    }

    public function cacheTtl(int $ttl): self
    {
        $this->cacheTtl = $ttl;
        return $this;
    }

    public function tool(string $name, callable $fn, ?string $description = null, array $params = []): self
    {
        $this->tools[$name] = [
            'fn' => $fn,
            'description' => $description ?? "Tool: {$name}",
            'params' => $params,
        ];

        return $this;
    }

    public function run(): array
    {
        if (!empty($this->tools) && ($this->prompt !== null || !empty($this->messages))) {
            return $this->runWithTools();
        }

        $params = $this->buildParams();
        if ($this->useCache !== null) {
            $params['cache'] = $this->useCache;
        }
        if ($this->cacheTtl !== null) {
            $params['cache_ttl'] = $this->cacheTtl;
        }
        $result = $this->provider->generate($params);
        $this->rawResponse = $result['text'] ?? '';

        $data = $this->extractAndDecodeJson($this->rawResponse);
        $success = false;
        $this->errors = [];

        // Store original data for required field check
        $originalData = is_array($data) ? $data : [];

        if ($this->expectArrayKey && is_array($data)) {
            $data = $this->coerceSchemaOnArray($data);
            $success = true;
        } elseif ($this->expectSchema && is_array($data)) {
            $data = $this->coerceSchemaOnObject($data);
            $success = true;
        } elseif (!$this->expectSchema && !$this->expectArrayKey) {
            // Plain text mode - no schema expected, always success if we got a response
            $success = !empty($this->rawResponse);
        }

        // Check required fields BEFORE coercion
        if ($success && !empty($this->requiredFields)) {
            if ($this->expectArrayKey && is_array($originalData)) {
                $this->validateRequiredFieldsInArray($originalData);
            } else {
                $this->validateRequiredFieldsInObject($originalData);
            }
            if (!empty($this->errors)) {
                $success = false;
            }
        }


        return [
            'success' => $success,
            'data' => $success ? $data : null,
            'raw' => $this->rawResponse,
            'errors' => $this->errors,
        ];
    }

    protected function runWithTools(): array
    {
        $this->errors = [];

        $userQuery = $this->prompt ?? $this->lastUserMessage() ?? '';
        if ($userQuery === '') {
            return [
                'success' => false,
                'data' => null,
                'raw' => null,
                'errors' => ['No user prompt provided'],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        $decisionPrompt = $this->buildToolDecisionPrompt($userQuery);
        $decisionText = $this->generateRawText($decisionPrompt, temperature: 0.0);
        $decision = $this->decodeJsonObject($decisionText);

        if (!is_array($decision)) {
            return [
                'success' => false,
                'data' => null,
                'raw' => $decisionText,
                'errors' => ['Failed to parse tool decision JSON'],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        $toolName = $decision['tool'] ?? null;
        $toolParams = $decision['params'] ?? null;

        if (!is_string($toolName) || $toolName === '') {
            return [
                'success' => false,
                'data' => null,
                'raw' => $decisionText,
                'errors' => ['Tool decision missing "tool"'],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        if ($toolName === 'none') {
            $answer = $this->generateRawText($this->buildToolNoneAnswerPrompt($userQuery), temperature: $this->temperature ?? 0.3);

            return [
                'success' => true,
                'data' => null,
                'raw' => $answer,
                'errors' => [],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        if (!isset($this->tools[$toolName])) {
            return [
                'success' => false,
                'data' => null,
                'raw' => $decisionText,
                'errors' => ["Unknown tool: {$toolName}"],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        if (!is_array($toolParams)) {
            return [
                'success' => false,
                'data' => null,
                'raw' => $decisionText,
                'errors' => ['Tool decision missing "params" object'],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        $toolDef = $this->tools[$toolName];
        $validatedParams = $this->validateToolParams($toolParams, $toolDef['params'] ?? []);
        if ($validatedParams === null) {
            return [
                'success' => false,
                'data' => null,
                'raw' => $decisionText,
                'errors' => $this->errors,
                'tools_used' => [$toolName],
                'tool_results' => [],
            ];
        }

        try {
            $toolResult = ($toolDef['fn'])($validatedParams);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data' => null,
                'raw' => null,
                'errors' => ['Tool execution failed: ' . $e->getMessage()],
                'tools_used' => [$toolName],
                'tool_results' => [],
            ];
        }

        $finalPrompt = $this->buildToolFinalAnswerPrompt($userQuery, $toolName, $toolResult);
        $answer = $this->generateRawText($finalPrompt, temperature: $this->temperature ?? 0.3);

        return [
            'success' => true,
            'data' => null,
            'raw' => $answer,
            'errors' => [],
            'tools_used' => [$toolName],
            'tool_results' => [$toolName => $toolResult],
        ];
    }

    protected function buildToolDecisionPrompt(string $userQuery): string
    {
        $toolLines = [];
        foreach ($this->tools as $name => $tool) {
            $toolLines[] = $this->describeToolForPrompt($name, $tool);
        }

        $toolList = implode("\n", $toolLines);

        return "Decide if you should call ONE tool to help answer the user.\n\n"
            . "User: {$userQuery}\n\n"
            . "Available Tools:\n{$toolList}\n\n"
            . "Rules:\n"
            . "- Return ONLY a JSON object. No markdown, no extra text.\n"
            . "- Choose tool=\"none\" if no tool is needed or if required parameters are missing.\n"
            . "- If you choose a tool, include a JSON object params with all required parameters.\n\n"
            . "Response format:\n"
            . '{"tool":"tool_name_or_none","params":{}}';
    }

    protected function buildToolNoneAnswerPrompt(string $userQuery): string
    {
        return "User: {$userQuery}\n\n"
            . "Provide a helpful answer. If you need more details, ask a clarifying question.";
    }

    protected function buildToolFinalAnswerPrompt(string $userQuery, string $toolName, mixed $toolResult): string
    {
        $toolResultText = $this->formatForPrompt($toolResult);

        return "User: {$userQuery}\n\n"
            . "Tool Used: {$toolName}\n\n"
            . "Tool Result:\n{$toolResultText}\n\n"
            . "Rules:\n"
            . "- Use ONLY the Tool Result for facts.\n"
            . "- If the Tool Result does not contain enough information, say so explicitly.\n"
            . "- Do not invent details.\n\n"
            . "Answer:";
    }

    protected function describeToolForPrompt(string $name, array $tool): string
    {
        $desc = (string)($tool['description'] ?? "Tool: {$name}");
        $params = $tool['params'] ?? [];
        $paramLines = [];

        foreach ($params as $key => $type) {
            if (is_int($key)) {
                continue;
            }

            $paramType = is_array($type) ? ($type[0] ?? 'string') : $type;
            $paramDesc = is_array($type) ? ($type[1] ?? '') : '';
            $line = "- {$key}: {$paramType}";
            if ($paramDesc !== '') {
                $line .= " ({$paramDesc})";
            }
            $paramLines[] = $line;
        }

        $paramsText = empty($paramLines) ? '- (no parameters)' : implode("\n", $paramLines);

        return "{$name}: {$desc}\nParameters:\n{$paramsText}\n";
    }

    protected function validateToolParams(array $params, array $schema): ?array
    {
        $this->errors = [];

        $normalized = [];
        foreach ($schema as $key => $type) {
            if (is_int($key)) {
                $normalized[$type] = 'string';
            } else {
                $normalized[$key] = is_array($type) ? ($type[0] ?? 'string') : $type;
            }
        }

        foreach ($normalized as $key => $type) {
            if (!array_key_exists($key, $params) || $params[$key] === null) {
                $this->errors[] = "Missing required parameter: {$key}";
                continue;
            }

            $value = $params[$key];
            if (!$this->valueMatchesType($value, $type)) {
                $this->errors[] = "Invalid parameter type for {$key}: expected {$type}";
                continue;
            }

            if ($type === 'int') {
                $params[$key] = (int)$value;
            } elseif ($type === 'number') {
                $params[$key] = (float)$value;
            } elseif ($type === 'bool') {
                $params[$key] = (bool)$value;
            } elseif ($type === 'string') {
                $params[$key] = (string)$value;
            }
        }

        return empty($this->errors) ? $params : null;
    }

    protected function valueMatchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value) || is_numeric($value) || is_bool($value),
            'int' => is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value) === 1),
            'number' => is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)),
            'bool' => is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1',
            'array' => is_array($value),
            default => true,
        };
    }

    protected function generateRawText(string $prompt, float $temperature = 0.3): string
    {
        $task = new self($this->provider);
        if ($this->model !== null) {
            $task->model($this->model);
        }
        if ($this->maxTokens !== null) {
            $task->maxTokens($this->maxTokens);
        }
        if ($this->system !== null) {
            $task->system($this->system);
        }
        $task->prompt($prompt)->temperature($temperature);

        $result = $task->run();
        return (string)($result['raw'] ?? '');
    }

    protected function decodeJsonObject(string $text): ?array
    {
        $json = $this->extractJson($text) ?? $text;
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function lastUserMessage(): ?string
    {
        for ($i = count($this->messages) - 1; $i >= 0; $i--) {
            if (($this->messages[$i]['role'] ?? null) === 'user') {
                return (string)($this->messages[$i]['content'] ?? '');
            }
        }

        return null;
    }

    protected function formatForPrompt(mixed $value): string
    {
        $maxChars = 12000;
        $maxDepth = 5;
        $maxItems = 50;
        $maxStringLen = 800;

        if (is_string($value)) {
            return $this->truncateString($value, $maxChars);
        }

        if (is_scalar($value) || $value === null) {
            return $this->truncateString((string)$value, $maxChars);
        }

        $normalized = $this->normalizeForPrompt($value, $maxDepth, $maxItems, $maxStringLen);
        $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if (!is_string($json)) {
            $json = '[unserializable tool result]';
        }

        return $this->truncateString($json, $maxChars);
    }

    protected function normalizeForPrompt(mixed $value, int $maxDepth, int $maxItems, int $maxStringLen): mixed
    {
        if ($maxDepth <= 0) {
            return '[max depth reached]';
        }

        if (is_string($value)) {
            return $this->truncateString($value, $maxStringLen);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            $out = [];
            $count = 0;

            foreach ($value as $k => $v) {
                if ($count >= $maxItems) {
                    $out['__truncated__'] = true;
                    $out['__truncated_reason__'] = 'max items reached';
                    break;
                }

                $key = is_string($k) ? $this->truncateString($k, 120) : $k;
                $out[$key] = $this->normalizeForPrompt($v, $maxDepth - 1, $maxItems, $maxStringLen);
                $count++;
            }

            return $out;
        }

        if (is_object($value)) {
            return $this->normalizeForPrompt(get_object_vars($value), $maxDepth - 1, $maxItems, $maxStringLen);
        }

        return '[unsupported tool result type]';
    }

    protected function truncateString(string $value, int $maxLen): string
    {
        if (strlen($value) <= $maxLen) {
            return $value;
        }

        return substr($value, 0, $maxLen) . '...';
    }

    /**
     * Build request parameters for the provider.
     */
    protected function buildParams(): array
    {
        $params = [];
        if (!empty($this->messages)) {
            $params['messages'] = $this->messages;
            if ($this->system !== null) {
                array_unshift($params['messages'], ['role' => 'system', 'content' => $this->system]);
            }
        }

        if ($this->prompt) {
            $params['prompt'] = $this->prompt;
        }

        if ($this->temperature !== null) {
            $params['temperature'] = $this->temperature;
        }

        if ($this->maxTokens !== null) {
            $params['max_tokens'] = $this->maxTokens;
        }

        if ($this->model) {
            $params['model'] = $this->model;
        }

        if ($this->example) {
            $params['example'] = $this->example;
        }
        return $params;
    }

    /**
     * Extract and decode JSON from the raw LLM response.
     */
    protected function extractAndDecodeJson(string $text)
    {
        $json = $this->extractJson($text) ?? $text;
        return json_decode($json, true);
    }

    /**
     * Coerce schema on an array of objects.
     */
    protected function coerceSchemaOnArray(array $data): array
    {
        foreach ($data as &$item) {
            if (is_array($item) && $this->expectSchema) {
                foreach ($this->expectSchema as $key => $type) {
                    if (!array_key_exists($key, $item)) {
                        $item[$key] = null;
                    }
                    settype($item[$key], $type);
                }
            }
        }
        unset($item);
        return $data;
    }

    /**
     * Coerce schema on a single object.
     */
    protected function coerceSchemaOnObject(array $data): array
    {
        foreach ($this->expectSchema as $key => $type) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = null;
            }
            
            // Don't coerce null values - preserve them
            if ($data[$key] !== null) {
                // Map 'number' to 'float' for settype compatibility
                $phpType = $type === 'number' ? 'float' : $type;
                settype($data[$key], $phpType);
            }
        }
        return $data;
    }

    public function raw(): string
    {
        return $this->rawResponse;
    }

    protected function buildSchemaInstruction(): string
    {
        if ($this->expectSchema) {
            $keys = implode('", "', array_keys($this->expectSchema));
            $example = $this->example ?? $this->autoExampleFromSchema();
            return 'Respond ONLY as a JSON object with keys: "' . $keys . '". No markdown, no extra text. Example: ' . json_encode($example);
        }
        if ($this->expectArrayKey) {
            return 'Respond ONLY as a JSON array of ' . $this->expectArrayKey . ' objects. No markdown, no extra text.';
        }
        return '';
    }

    /**
     * Validate required fields in a single object.
     */
    private function validateRequiredFieldsInObject(array $data): void
    {
        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null) {
                $this->errors[] = "Missing required field: $field";
            }
        }
    }

    /**
     * Validate required fields in an array of objects.
     */
    private function validateRequiredFieldsInArray(array $items): void
    {
        foreach ($items as $i => $item) {
            foreach ($this->requiredFields as $field) {
                if (!array_key_exists($field, $item) || $item[$field] === null) {
                    $this->errors[] = "Item " . ($i + 1) . ": Missing required field: $field";
                }
            }
        }
    }


    protected function autoExampleFromSchema(): array
    {
        $example = [];
        foreach ($this->expectSchema as $key => $type) {
            $example[$key] = $type === 'string' ? 'example' : ($type === 'int' ? 0 : null);
        }
        return $example;
    }

    /**
     * Extract JSON array, multi-object, or object from a string (for messy LLM outputs).
     */
    /**
     * Attempt to extract valid JSON (array or object) from messy LLM output.
     * Handles the most common LLM output quirks:
     *
     *   1. JSON array:
     *      - Example input: 'Here is your data: [{"a":1},{"b":2}]'
     *      - Extracted:      '[{"a":1},{"b":2}]'
     *
     *   2. Multiple JSON objects (newline or space separated):
     *      - Example input: '{"a":1}\n{"b":2}\n{"c":3}'
     *      - Extracted:      '[{"a":1},{"b":2},{"c":3}]'
     *
     *   3. Single JSON object:
     *      - Example input: 'Result: {"a":1, "b":2}'
     *      - Extracted:      '{"a":1, "b":2}'
     *
     *   4. If nothing found, returns null.
     *
     * This makes the builder robust to unpredictable LLM output formatting.
     */
    protected function extractJson(string $text): ?string
    {
        // 1. Try to extract a JSON array (most robust, preferred format)
        if (preg_match('/(\[.*\])/s', $text, $matches)) {
            return $matches[0];
        }
        // 2. If multiple JSON objects (e.g., separated by newlines), wrap as array
        if (preg_match_all('/\{.*?\}/s', $text, $matches) && count($matches[0]) > 1) {
            // Join all found objects into a valid JSON array
            return '[' . implode(',', $matches[0]) . ']';
        }
        // 3. Fallback: extract a single JSON object
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            return $matches[0];
        }
        // 4. Nothing found: return null
        return null;
    }
}


