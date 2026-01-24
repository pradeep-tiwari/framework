<?php

namespace Lightpack\AI\Agent;

/**
 * Intent: Represents a user's intention with patterns and required entities.
 * Inspired by Dialogflow's intent system.
 */
class Intent
{
    public function __construct(
        public string $name,
        public array $patterns,
        public array $entities,
        public string $tool,
        public ?string $description = null
    ) {}
    
    /**
     * Create an intent definition.
     */
    public static function create(
        string $name,
        array $patterns,
        string $tool,
        array $entities = [],
        ?string $description = null
    ): self {
        return new self($name, $patterns, $entities, $tool, $description);
    }
    
    /**
     * Get intent as array for AI matching.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'patterns' => $this->patterns,
            'description' => $this->description ?? "Intent: {$this->name}"
        ];
    }
}
