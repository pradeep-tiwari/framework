<?php

namespace Lightpack\AI\Agent;

/**
 * AgentResult: Encapsulates the result of an agent query.
 */
class AgentResult
{
    public function __construct(
        public string $answer,
        public string $intent,
        public float $confidence,
        public array $entities,
        public mixed $toolResult,
        public string $reasoning
    ) {}
    
    /**
     * Get the answer text.
     */
    public function answer(): string
    {
        return $this->answer;
    }
    
    /**
     * Get matched intent name.
     */
    public function intent(): string
    {
        return $this->intent;
    }
    
    /**
     * Get intent confidence score.
     */
    public function confidence(): float
    {
        return $this->confidence;
    }
    
    /**
     * Get extracted entities.
     */
    public function entities(): array
    {
        return $this->entities;
    }
    
    /**
     * Get tool execution result.
     */
    public function toolResult(): mixed
    {
        return $this->toolResult;
    }
    
    /**
     * Get reasoning for intent match.
     */
    public function reasoning(): string
    {
        return $this->reasoning;
    }
    
    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'intent' => $this->intent,
            'confidence' => $this->confidence,
            'entities' => $this->entities,
            'tool_result' => $this->toolResult,
            'reasoning' => $this->reasoning
        ];
    }
    
    /**
     * String representation returns the answer.
     */
    public function __toString(): string
    {
        return $this->answer;
    }
}
