<?php

namespace Lightpack\AI\Agent;

use Lightpack\Cache\Cache;

class Conversation
{
    protected Agent $agent;
    protected string $sessionId;
    protected array $history = [];
    protected int $maxHistoryLength;
    protected int $ttl;
    
    public function __construct(
        Agent $agent, 
        string $sessionId,
        int $maxHistoryLength = 10,
        int $ttl = 3600
    ) {
        $this->agent = $agent;
        $this->sessionId = $sessionId;
        $this->maxHistoryLength = $maxHistoryLength;
        $this->ttl = $ttl;
        $this->loadHistory();
    }
    
    public function ask(string $query): AgentResult
    {
        $result = $this->agent->ask($query, [
            'conversation_history' => $this->getFormattedHistory()
        ]);
        
        $this->addToHistory($query, $result->answer());
        $this->saveHistory();
        
        return $result;
    }
    
    public function getHistory(): array
    {
        return $this->history;
    }
    
    public function clear(): self
    {
        $this->history = [];
        $this->saveHistory();
        
        return $this;
    }
    
    public function forget(): self
    {
        $this->history = [];
        cache()->delete($this->getCacheKey());
        
        return $this;
    }
    
    protected function addToHistory(string $query, string $answer): void
    {
        $this->history[] = [
            'user' => $query,
            'agent' => $answer,
            'timestamp' => time()
        ];
        
        if (count($this->history) > $this->maxHistoryLength) {
            $this->history = array_slice($this->history, -$this->maxHistoryLength);
        }
    }
    
    protected function getFormattedHistory(): string
    {
        if (empty($this->history)) {
            return '';
        }
        
        $formatted = [];
        
        foreach ($this->history as $turn) {
            $formatted[] = "User: {$turn['user']}";
            $formatted[] = "Agent: {$turn['agent']}";
        }
        
        return implode("\n", $formatted);
    }
    
    protected function loadHistory(): void
    {
        $cached = cache()->get($this->getCacheKey());
        
        if ($cached) {
            $decoded = json_decode($cached, true);
            $this->history = is_array($decoded) ? $decoded : [];
        }
    }
    
    protected function saveHistory(): void
    {
        cache()->set(
            $this->getCacheKey(),
            json_encode($this->history),
            $this->ttl
        );
    }
    
    protected function getCacheKey(): string
    {
        return "agent:conversation:{$this->sessionId}";
    }
}
