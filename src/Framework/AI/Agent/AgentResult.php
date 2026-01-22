<?php

namespace Lightpack\AI\Agent;

class AgentResult
{
    protected string $answer;
    protected array $toolResults;
    protected array $toolsUsed;
    protected string $reasoning;
    
    public function __construct(
        string $answer,
        array $toolResults = [],
        array $toolsUsed = [],
        string $reasoning = ''
    ) {
        $this->answer = $answer;
        $this->toolResults = $toolResults;
        $this->toolsUsed = $toolsUsed;
        $this->reasoning = $reasoning;
    }
    
    public function answer(): string
    {
        return $this->answer;
    }
    
    public function toolResults(): array
    {
        return $this->toolResults;
    }
    
    public function toolResult(string $toolName): mixed
    {
        return $this->toolResults[$toolName] ?? null;
    }
    
    public function toolsUsed(): array
    {
        return $this->toolsUsed;
    }
    
    public function reasoning(): string
    {
        return $this->reasoning;
    }
    
    public function usedTool(string $toolName): bool
    {
        return in_array($toolName, $this->toolsUsed);
    }
    
    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'tool_results' => $this->toolResults,
            'tools_used' => $this->toolsUsed,
            'reasoning' => $this->reasoning
        ];
    }
    
    public function __toString(): string
    {
        return $this->answer;
    }
}
