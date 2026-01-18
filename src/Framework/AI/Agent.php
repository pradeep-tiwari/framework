<?php

namespace Lightpack\AI;

use Lightpack\AI\AI;

class Agent
{
    protected array $tools = [];
    protected AI $ai;
    
    public function __construct(AI $ai)
    {
        $this->ai = $ai;
    }
    
    public function tool(string $name, callable $fn, ?string $description = null): self
    {
        $this->tools[$name] = [
            'fn' => $fn,
            'description' => $description ?? "Tool: {$name}"
        ];
        
        return $this;
    }
    
    public function ask(string $query, array $context = []): string
    {
        $plan = $this->planTools($query, $context);
        
        $results = $this->executeTools($plan['tools'], $query);
        
        return $this->generateAnswer($query, $results, $context);
    }
    
    public function conversation(string $sessionId): Conversation
    {
        return new Conversation($this, $sessionId);
    }
    
    protected function planTools(string $query, array $context): array
    {
        if (empty($this->tools)) {
            return ['tools' => [], 'reasoning' => 'No tools available'];
        }
        
        $toolList = $this->getToolDescriptions();
        
        $prompt = "Query: {$query}\n\nAvailable tools:\n{$toolList}";
        
        if (!empty($context)) {
            $prompt .= "\n\nContext: " . json_encode($context, JSON_PRETTY_PRINT);
        }
        
        return $this->ai->task()
            ->prompt($prompt)
            ->expect([
                'tools' => ['array', 'Tool names to use (empty array if none needed)'],
                'reasoning' => ['string', 'Brief explanation of tool selection']
            ])
            ->run();
    }
    
    protected function executeTools(array $toolNames, string $query): array
    {
        $results = [];
        
        foreach ($toolNames as $name) {
            if (isset($this->tools[$name])) {
                try {
                    $results[$name] = $this->tools[$name]['fn']($query);
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
            $descriptions[] = "- {$name}: {$tool['description']}";
        }
        
        return implode("\n", $descriptions);
    }
    
    public function getTools(): array
    {
        return array_keys($this->tools);
    }
}
