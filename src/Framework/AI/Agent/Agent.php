<?php

namespace Lightpack\AI\Agent;

use Lightpack\AI\AI;

class Agent
{
    protected array $tools = [];
    protected AI $ai;
    protected ?float $temperature = null;
    protected ?string $systemPrompt = null;
    
    public function __construct(AI $ai)
    {
        $this->ai = $ai;
    }
    
    public function tool(string $name, callable $fn, ?string $description = null, array $parameters = []): self
    {
        $this->tools[$name] = [
            'fn' => $fn,
            'description' => $description ?? "Tool: {$name}",
            'parameters' => $parameters
        ];
        
        return $this;
    }
    
    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }
    
    public function system(string $prompt): self
    {
        $this->systemPrompt = $prompt;
        return $this;
    }
    
    public function ask(string $query, array $context = []): AgentResult
    {
        $plan = $this->planTools($query, $context);
        
        $results = $this->executeTools($plan, $query);
        
        $answer = $this->generateAnswer($query, $results, $context);
        
        return new AgentResult(
            $answer,
            $results,
            $plan['tools'] ?? [],
            $plan['reasoning'] ?? ''
        );
    }
    
    public function conversation(string $sessionId): Conversation
    {
        return new Conversation($this, $sessionId);
    }
    
    protected function planTools(string $query, array $context): array
    {
        if (empty($this->tools)) {
            return ['tools' => [], 'parameters' => [], 'reasoning' => 'No tools available'];
        }
        
        $toolList = $this->getToolDescriptions();
        
        $prompt = "Query: {$query}\n\nAvailable tools:\n{$toolList}";
        
        if ($this->systemPrompt) {
            $prompt = "{$this->systemPrompt}\n\n{$prompt}";
        }
        
        if (!empty($context)) {
            $prompt .= "\n\nContext: " . json_encode($context, JSON_PRETTY_PRINT);
        }
        
        $schema = [
            'tools' => ['array', 'Tool names to use (empty array if none needed)'],
            'reasoning' => ['string', 'Brief explanation of tool selection']
        ];
        
        if ($this->hasToolsWithParameters()) {
            $schema['parameters'] = ['object', 'Parameters for each tool (key: tool name, value: parameters object)'];
        }
        
        $task = $this->ai->task()->prompt($prompt)->expect($schema);
        
        if ($this->temperature !== null) {
            $task->temperature($this->temperature);
        }
        
        return $task->run();
    }
    
    protected function executeTools(array $plan, string $query): array
    {
        $results = [];
        $toolNames = $plan['tools'] ?? [];
        $parameters = $plan['parameters'] ?? [];
        
        foreach ($toolNames as $name) {
            if (isset($this->tools[$name])) {
                try {
                    $toolParams = $parameters[$name] ?? [];
                    
                    if (empty($this->tools[$name]['parameters'])) {
                        $results[$name] = $this->tools[$name]['fn']($query);
                    } else {
                        $results[$name] = $this->tools[$name]['fn']($toolParams);
                    }
                } catch (\Exception $e) {
                    $results[$name] = [
                        'error' => $e->getMessage(),
                        'type' => get_class($e)
                    ];
                }
            }
        }
        
        return $results;
    }
    
    protected function generateAnswer(string $query, array $results, array $context): string
    {
        $prompt = "Question: {$query}";
        
        if (!empty($results)) {
            $prompt .= "\n\nTool Results:\n" . json_encode($results, JSON_PRETTY_PRINT);
        }
        
        if (!empty($context)) {
            $prompt .= "\n\nAdditional Context:\n" . json_encode($context, JSON_PRETTY_PRINT);
        }
        
        $prompt .= "\n\nProvide a helpful, accurate answer based on the available information.";
        
        return $this->ai->ask($prompt);
    }
    
    protected function getToolDescriptions(): string
    {
        $descriptions = [];
        
        foreach ($this->tools as $name => $tool) {
            $desc = "- {$name}: {$tool['description']}";
            
            if (!empty($tool['parameters'])) {
                $params = [];
                foreach ($tool['parameters'] as $paramName => $paramDef) {
                    $type = is_array($paramDef) ? $paramDef[0] : 'string';
                    $description = is_array($paramDef) && isset($paramDef[1]) ? $paramDef[1] : '';
                    $params[] = "  - {$paramName} ({$type}): {$description}";
                }
                $desc .= "\n" . implode("\n", $params);
            }
            
            $descriptions[] = $desc;
        }
        
        return implode("\n", $descriptions);
    }
    
    protected function hasToolsWithParameters(): bool
    {
        foreach ($this->tools as $tool) {
            if (!empty($tool['parameters'])) {
                return true;
            }
        }
        return false;
    }
    
    public function getTools(): array
    {
        return array_keys($this->tools);
    }
}
