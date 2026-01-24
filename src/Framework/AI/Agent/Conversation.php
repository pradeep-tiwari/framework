<?php

namespace Lightpack\AI\Agent;

use Lightpack\AI\TaskBuilder;

/**
 * Conversation: Manages conversation sessions for TaskBuilder with Agent.
 * Handles conversation history and context management.
 */
class Conversation
{
    protected array $history = [];
    
    public function __construct(
        protected Agent $agent,
        protected TaskBuilder $task,
        protected string $sessionId,
        protected int $maxHistoryLength = 10,
        protected int $ttl = 3600
    ) {
        $this->loadHistory();
    }
    
    /**
     * Ask a question with conversation context.
     * 
     * @param string $query User's question
     * @return string Agent's answer
     */
    public function ask(string $query): string
    {
        // Clone task to avoid state pollution
        $taskClone = clone $this->task;
        
        // Add conversation history as messages
        foreach ($this->history as $turn) {
            $taskClone->message('user', $turn['user']);
            $taskClone->message('assistant', $turn['assistant']);
        }
        
        // Add current query
        $taskClone->message('user', $query);
        
        // Execute through agent
        $result = $this->agent->execute($taskClone);
        
        // Save to history
        $this->addToHistory($query, $result['raw']);
        
        return $result['raw'];
    }
    
    /**
     * Get detailed result with metadata.
     */
    public function askDetailed(string $query): array
    {
        $taskClone = clone $this->task;
        
        foreach ($this->history as $turn) {
            $taskClone->message('user', $turn['user']);
            $taskClone->message('assistant', $turn['assistant']);
        }
        
        $taskClone->message('user', $query);
        
        $result = $this->agent->execute($taskClone);
        
        $this->addToHistory($query, $result['raw']);
        
        return $result;
    }
    
    /**
     * Clear conversation history but keep session.
     */
    public function clear(): self
    {
        $this->history = [];
        $this->saveHistory();
        return $this;
    }
    
    /**
     * Forget conversation completely (delete from cache).
     */
    public function forget(): self
    {
        $this->history = [];
        cache()->delete($this->getCacheKey());
        return $this;
    }
    
    /**
     * Get conversation history.
     */
    public function getHistory(): array
    {
        return $this->history;
    }
    
    /**
     * Add a turn to conversation history.
     */
    protected function addToHistory(string $query, string $answer): void
    {
        $this->history[] = [
            'user' => $query,
            'assistant' => $answer,
            'timestamp' => time()
        ];
        
        // Limit history length
        if (count($this->history) > $this->maxHistoryLength) {
            $this->history = array_slice($this->history, -$this->maxHistoryLength);
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
     * Get cache key for this session.
     */
    protected function getCacheKey(): string
    {
        return "agent:conversation:{$this->sessionId}";
    }
}
