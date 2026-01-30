<?php

namespace Lightpack\AI;

/**
 * Stores turn-by-turn history for agent execution.
 * Handles storing, retrieving, and formatting conversation turns.
 */
class TurnHistory
{
    private array $turns = [];

    /**
     * Add a turn to history.
     * 
     * @param string $role 'user' or 'assistant'
     * @param string $content The message content
     * @param int $turn Turn number
     * @param array $toolsUsed Tools used in this turn (for assistant only)
     */
    public function addTurn(string $role, string $content, int $turn, array $toolsUsed = []): void
    {
        $entry = [
            'role' => $role,
            'content' => $content,
            'turn' => $turn
        ];

        if ($role === 'assistant') {
            $entry['tools_used'] = $toolsUsed;
        }

        $this->turns[] = $entry;
    }

    /**
     * Get all turns.
     */
    public function getAllTurns(): array
    {
        return $this->turns;
    }

    /**
     * Get recent N turns.
     */
    public function getRecentTurns(int $n): array
    {
        return array_slice($this->turns, -$n);
    }

    /**
     * Build context string from turn history for prompts.
     * 
     * @param int|null $recentOnly If set, only include last N turns
     * @return string Formatted context string
     */
    public function formatForPrompt(?int $recentOnly = null): string
    {
        $turns = $recentOnly ? $this->getRecentTurns($recentOnly) : $this->turns;

        if (empty($turns)) {
            return '';
        }

        $contextParts = [];
        
        foreach ($turns as $entry) {
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
