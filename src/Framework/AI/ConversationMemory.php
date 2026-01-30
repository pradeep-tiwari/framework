<?php

namespace Lightpack\AI;

/**
 * Manages conversation memory for agent execution.
 * Handles storing, retrieving, and formatting conversation history.
 */
class ConversationMemory
{
    private array $entries = [];
    private int $maxEntries;

    public function __construct(int $maxEntries = 100)
    {
        $this->maxEntries = $maxEntries;
    }

    /**
     * Add a memory entry.
     * 
     * @param string $role 'user' or 'assistant'
     * @param string $content The message content
     * @param int $turn Turn number
     * @param array $toolsUsed Tools used in this turn (for assistant only)
     */
    public function add(string $role, string $content, int $turn, array $toolsUsed = []): void
    {
        $entry = [
            'role' => $role,
            'content' => $content,
            'turn' => $turn
        ];

        if ($role === 'assistant') {
            $entry['tools_used'] = $toolsUsed;
        }

        $this->entries[] = $entry;

        // Enforce max entries limit (keep first entry + recent entries)
        if (count($this->entries) > $this->maxEntries) {
            $firstEntry = array_shift($this->entries);
            $this->entries = array_slice($this->entries, -($this->maxEntries - 1));
            array_unshift($this->entries, $firstEntry);
        }
    }

    /**
     * Get all memory entries.
     */
    public function getAll(): array
    {
        return $this->entries;
    }

    /**
     * Get recent N entries.
     */
    public function getRecent(int $n): array
    {
        return array_slice($this->entries, -$n);
    }

    /**
     * Get entry count.
     */
    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * Clear all memory.
     */
    public function clear(): void
    {
        $this->entries = [];
    }

    /**
     * Build context string from memory for prompts.
     * 
     * @param int|null $recentOnly If set, only include last N entries
     * @return string Formatted context string
     */
    public function buildContext(?int $recentOnly = null): string
    {
        $entries = $recentOnly ? $this->getRecent($recentOnly) : $this->entries;

        if (empty($entries)) {
            return '';
        }

        $contextParts = [];
        
        foreach ($entries as $entry) {
            $role = $entry['role'] ?? 'unknown';
            $content = $entry['content'] ?? '';
            $turn = $entry['turn'] ?? 0;
            $toolsUsed = $entry['tools_used'] ?? [];
            
            if ($role === 'user') {
                $contextParts[] = "User (Turn {$turn}): {$content}";
            } else {
                $toolInfo = empty($toolsUsed) ? '' : ' [Used tools: ' . implode(', ', $toolsUsed) . ']';
                $contextParts[] = "Assistant (Turn {$turn}){$toolInfo}: {$content}";
            }
        }
        
        return implode("\n\n", $contextParts);
    }

    /**
     * Compress memory by removing less important entries.
     * Keeps first entry (original request) and recent entries.
     * 
     * @param int $keepRecent Number of recent entries to keep
     */
    public function compress(int $keepRecent = 5): void
    {
        if (count($this->entries) <= $keepRecent + 1) {
            return; // Nothing to compress
        }

        $firstEntry = array_shift($this->entries);
        $this->entries = array_slice($this->entries, -$keepRecent);
        array_unshift($this->entries, $firstEntry);
    }

    /**
     * Get summary of memory (useful for debugging).
     */
    public function getSummary(): array
    {
        return [
            'total_entries' => count($this->entries),
            'user_messages' => count(array_filter($this->entries, fn($e) => $e['role'] === 'user')),
            'assistant_messages' => count(array_filter($this->entries, fn($e) => $e['role'] === 'assistant')),
            'tools_used' => $this->getToolsUsedSummary(),
        ];
    }

    /**
     * Get summary of tools used across all turns.
     */
    protected function getToolsUsedSummary(): array
    {
        $toolCounts = [];
        
        foreach ($this->entries as $entry) {
            if (isset($entry['tools_used'])) {
                foreach ($entry['tools_used'] as $tool) {
                    $toolCounts[$tool] = ($toolCounts[$tool] ?? 0) + 1;
                }
            }
        }
        
        return $toolCounts;
    }
}
