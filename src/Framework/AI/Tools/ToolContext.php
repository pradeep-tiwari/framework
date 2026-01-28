<?php
namespace Lightpack\AI\Tools;

class ToolContext
{
    public function __construct(
        public readonly array $messages = [],
        public readonly array $toolResults = [],
        public readonly array $metadata = []
    ) {}
    
    public function lastUserMessage(): ?string
    {
        for ($i = count($this->messages) - 1; $i >= 0; $i--) {
            if (($this->messages[$i]['role'] ?? null) === 'user') {
                return $this->messages[$i]['content'] ?? null;
            }
        }
        
        return null;
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
    
    public function has(string $key): bool
    {
        return isset($this->metadata[$key]);
    }
}
