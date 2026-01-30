<?php

namespace Lightpack\AI;

use Lightpack\AI\Tools\ToolExecutor;

/**
 * Handles multi-turn agent execution logic.
 * Extracted from TaskBuilder to separate agent concerns from single-turn task execution.
 */
class AgentExecutor
{
    private int $maxTurns;
    private ?string $goal;
    private ConversationMemory $memory;
    private array $tools;
    private array $context;
    private $taskExecutor;

    public function __construct(
        int $maxTurns,
        ?string $goal,
        array $tools,
        array $context,
        callable $taskExecutor
    ) {
        $this->maxTurns = $maxTurns;
        $this->goal = $goal;
        $this->tools = $tools;
        $this->context = $context;
        $this->taskExecutor = $taskExecutor;
        $this->memory = new ConversationMemory();
    }

    /**
     * Run the agent loop until goal is achieved or max turns reached.
     * 
     * @param string $originalPrompt The initial user prompt
     * @return array Result with agent_turns, agent_memory, goal_achieved
     */
    public function run(string $originalPrompt): array
    {
        $currentTurn = 0;
        $allToolsUsed = [];
        $allToolResults = [];
        
        // Initialize memory with user's request
        if ($originalPrompt) {
            $this->memory->add('user', $originalPrompt, 0);
        }
        
        while ($currentTurn < $this->maxTurns) {
            // Execute single turn
            $result = ($this->taskExecutor)();
            
            // Accumulate tools used across all turns
            if (!empty($result['tools_used'])) {
                $allToolsUsed = array_merge($allToolsUsed, $result['tools_used']);
            }
            if (!empty($result['tool_results'])) {
                $allToolResults = array_merge($allToolResults, $result['tool_results']);
            }
            
            // Store turn result in memory
            $this->memory->add(
                'assistant',
                $result['raw'] ?? '',
                $currentTurn + 1,
                $result['tools_used'] ?? []
            );
            
            // Check if goal achieved or task complete
            if ($this->isTaskComplete($result)) {
                return array_merge($result, [
                    'agent_turns' => $currentTurn + 1,
                    'agent_memory' => $this->memory->getAll(),
                    'goal_achieved' => true,
                    'tools_used' => $allToolsUsed,
                    'tool_results' => $allToolResults,
                ]);
            }
            
            $currentTurn++;
        }
        
        // Max turns reached without completion
        $recentEntries = $this->memory->getRecent(1);
        $lastEntry = !empty($recentEntries) ? $recentEntries[0] : [];
        
        return [
            'success' => false,
            'data' => null,
            'raw' => $lastEntry['content'] ?? '',
            'errors' => ['Agent reached maximum turns without achieving goal'],
            'agent_turns' => $currentTurn,
            'agent_memory' => $this->memory->getAll(),
            'goal_achieved' => false,
            'tools_used' => $allToolsUsed,
            'tool_results' => $allToolResults,
        ];
    }

    /**
     * Check if the task is complete.
     */
    protected function isTaskComplete(array $result): bool
    {
        // If no tools were used and we got a successful response, task is complete
        if (empty($result['tools_used']) && $result['success']) {
            return true;
        }
        
        // If we have a substantive response with no more tool calls needed, we're done
        if (!empty($result['raw']) && empty($result['tools_used'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the current memory.
     */
    public function getMemory(): array
    {
        return $this->memory->getAll();
    }

    /**
     * Build context string from memory for next turn.
     */
    public function buildMemoryContext(): string
    {
        return $this->memory->buildContext();
    }

    /**
     * Prepare prompt for next turn with context and goal.
     */
    public function prepareNextTurnPrompt(): string
    {
        $memoryContext = $this->buildMemoryContext();
        
        if ($this->goal) {
            return "Goal: {$this->goal}\n\n"
                . "Previous Context:\n{$memoryContext}\n\n"
                . "Continue working towards the goal. What should you do next?";
        }
        
        return "Previous Context:\n{$memoryContext}\n\n"
            . "Continue with the task. What should you do next?";
    }
}
