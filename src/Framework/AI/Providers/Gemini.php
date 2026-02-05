<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;

class Gemini extends AI
{
    public function generate(array $params): array
    {
        return $this->executeWithCache($params, function() use ($params) {
            $baseUrl = $this->config->get('ai.providers.gemini.base_url');
            $model = $params['model'] ?? $this->config->get('ai.providers.gemini.model');
            $apiKey = $this->config->get('ai.providers.gemini.key');
            
            $endpoint = $params['endpoint'] ?? $baseUrl . '/models/' . $model . ':generateContent?key=' . $apiKey;
            
            $result = $this->makeApiRequest(
                $endpoint,
                $this->prepareRequestBody($params), 
                $this->prepareHeaders(), 
                $this->config->get('ai.http_timeout')
            );
            
            return $this->parseOutput($result);
        });
    }

    protected function parseOutput(array $result): array
    {
        $candidate = $result['candidates'][0] ?? [];
        $content = $candidate['content'] ?? [];
        $parts = $content['parts'] ?? [];
        
        $text = '';
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }

        return [
            'text' => $text,
            'finish_reason' => $candidate['finishReason'] ?? '',
            'usage' => $result['usageMetadata'] ?? [],
            'raw' => $result,
        ];
    }

    public function generateStream(array $params, callable $onChunk): void
    {
        $baseUrl = $this->config->get('ai.providers.gemini.base_url');
        $model = $params['model'] ?? $this->config->get('ai.providers.gemini.model');
        $apiKey = $this->config->get('ai.providers.gemini.key');
        
        $endpoint = $params['endpoint'] ?? $baseUrl . '/models/' . $model . ':streamGenerateContent?key=' . $apiKey;
        
        $body = $this->prepareRequestBody($params);
        
        $buffer = '';
        
        $this->http
            ->headers($this->prepareHeaders())
            ->timeout($this->config->get('ai.http_timeout'))
            ->stream('POST', $endpoint, $body, function($chunk) use (&$buffer, $onChunk) {
                $buffer .= $chunk;
                
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    
                    if (trim($line) === '') {
                        continue;
                    }
                    
                    $json = json_decode($line, true);
                    if (!$json) {
                        continue;
                    }
                    
                    $candidate = $json['candidates'][0] ?? [];
                    $content = $candidate['content'] ?? [];
                    $parts = $content['parts'] ?? [];
                    
                    foreach ($parts as $part) {
                        if (isset($part['text']) && $part['text'] !== '') {
                            $onChunk($part['text']);
                        }
                    }
                }
            });
    }

    protected function prepareRequestBody(array $params): array
    {
        $messages = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        
        $contents = [];
        
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $content = $msg['content'];
            
            $parts = $this->convertContentToParts($content);
            
            $contents[] = [
                'role' => $role,
                'parts' => $parts,
            ];
        }
        
        $body = [
            'contents' => $contents,
        ];
        
        if (!empty($params['system'])) {
            $body['systemInstruction'] = [
                'parts' => [['text' => $params['system']]]
            ];
        }
        
        $generationConfig = [];
        
        if (isset($params['temperature'])) {
            $generationConfig['temperature'] = $params['temperature'];
        }
        
        if (isset($params['max_tokens'])) {
            $generationConfig['maxOutputTokens'] = $params['max_tokens'];
        }
        
        if (!empty($generationConfig)) {
            $body['generationConfig'] = $generationConfig;
        }
        
        return $body;
    }

    protected function convertContentToParts(mixed $content): array
    {
        if (is_string($content)) {
            return [['text' => $content]];
        }
        
        if (!is_array($content)) {
            return [['text' => (string)$content]];
        }
        
        if ($this->isMultimodalContent($content)) {
            $parts = [];
            
            foreach ($content as $item) {
                $type = $item['type'] ?? null;
                
                if ($type === 'text') {
                    $parts[] = ['text' => $item['text']];
                } elseif ($type === 'image_url') {
                    $imageUrl = $item['image_url']['url'] ?? $item['image_url'];
                    if (str_starts_with($imageUrl, 'data:')) {
                        preg_match('/data:([^;]+);base64,(.+)/', $imageUrl, $matches);
                        if ($matches) {
                            $parts[] = [
                                'inline_data' => [
                                    'mime_type' => $matches[1],
                                    'data' => $matches[2],
                                ]
                            ];
                        }
                    }
                } elseif ($type === 'document') {
                    // Convert generic document format to Gemini's inline_data format
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $item['mime_type'],
                            'data' => $item['data'],
                        ]
                    ];
                } elseif (isset($item['inline_data'])) {
                    $parts[] = ['inline_data' => $item['inline_data']];
                }
            }
            
            return $parts;
        }
        
        return [['text' => implode("\n", $content)]];
    }

    protected function prepareHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    protected function generateEmbedding(string|array $input, array $options = []): array
    {
        $model = $options['model'] ?? $this->config->get('ai.providers.gemini.embedding_model', 'text-embedding-004');
        $apiKey = $this->config->get('ai.providers.gemini.key');
        $baseUrl = $this->config->get('ai.providers.gemini.base_url');
        
        if (is_string($input)) {
            $endpoint = $baseUrl . '/models/' . $model . ':embedContent?key=' . $apiKey;
            
            $result = $this->makeApiRequest(
                $endpoint,
                ['content' => ['parts' => [['text' => $input]]]],
                ['Content-Type' => 'application/json'],
                $this->config->get('ai.http_timeout', 15)
            );
            
            return $result['embedding']['values'] ?? [];
        }
        
        $endpoint = $baseUrl . '/models/' . $model . ':batchEmbedContents?key=' . $apiKey;
        
        $requests = array_map(fn($text) => [
            'model' => 'models/' . $model,
            'content' => ['parts' => [['text' => $text]]]
        ], $input);
        
        $result = $this->makeApiRequest(
            $endpoint,
            ['requests' => $requests],
            ['Content-Type' => 'application/json'],
            $this->config->get('ai.http_timeout', 15)
        );
        
        return array_map(fn($item) => $item['values'] ?? [], $result['embeddings'] ?? []);
    }
}
