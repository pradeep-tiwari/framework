<?php
namespace Lightpack\AI\Tools;

class ToolContext
{
    public function __construct(
        public readonly array $metadata = []
    ) {}
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
    
    public function has(string $key): bool
    {
        return isset($this->metadata[$key]);
    }
}
