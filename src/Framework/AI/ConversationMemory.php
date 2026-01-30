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
}
