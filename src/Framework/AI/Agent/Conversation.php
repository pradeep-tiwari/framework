<?php

namespace Lightpack\AI\Agent;

/**
 * Conversation: Manages conversation context and history.
 * Similar to Dialogflow's context management.
 */
class Conversation
{
    protected array $history = [];
    
    public function __construct(
        protected Agent $agent,
        protected string $sessionId,
        protected int $maxHistory = 10,
        protected int $ttl = 3600
    ) {
        $this->loadHistory();
    }
    
    /**
     * Ask a question with conversation context.
     */
    public function ask(string $query): AgentResult
    {
        // Get response from agent
        $result = $this->agent->ask($query);
        
        // Save to history
        $this->addToHistory($query, $result->answer());
        
        return $result;
    }
    
    /**
     * Get conversation history.
     */
    public function getHistory(): array
    {
        return $this->history;
    }
    
    /**
     * Clear conversation history.
     */
    public function clear(): self
    {
        $this->history = [];
        $this->saveHistory();
        return $this;
    }
    
    /**
     * Forget conversation (delete from cache).
     */
    public function forget(): self
    {
        cache()->delete($this->getCacheKey());
        $this->history = [];
        return $this;
    }
    
    /**
     * Add turn to history.
     */
    protected function addToHistory(string $query, string $response): void
    {
        $this->history[] = [
            'user' => $query,
            'assistant' => $response,
            'timestamp' => time()
        ];
        
        // Limit history size
        if (count($this->history) > $this->maxHistory) {
            $this->history = array_slice($this->history, -$this->maxHistory);
        }
        
        $this->saveHistory();
    }
    
    /**
     * Load history from cache.
     */
    protected function loadHistory(): void
    {
        $cached = cache()->get($this->getCacheKey());
        if ($cached) {
            $decoded = json_decode($cached, true);
            $this->history = is_array($decoded) ? $decoded : [];
        }
    }
    
    /**
     * Save history to cache.
     */
    protected function saveHistory(): void
    {
        cache()->set(
            $this->getCacheKey(),
            json_encode($this->history),
            $this->ttl
        );
    }
    
    /**
     * Get cache key for this conversation.
     */
    protected function getCacheKey(): string
    {
        return "agent:conversation:{$this->sessionId}";
    }
}
