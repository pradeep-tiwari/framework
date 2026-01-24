<?php

namespace Lightpack\AI\Agent;

use Lightpack\AI\AI;
use Lightpack\AI\TaskBuilder;

/**
 * Agent: Orchestrates tool planning, execution, and result synthesis.
 * Used internally by TaskBuilder when tools are registered.
 * 
 * Delegates to:
 * - Planner: Decides which tools to use
 * - Executor: Executes tools and handles errors
 */
class Agent
{
    protected ?Planner $planner = null;
    protected ?Executor $executor = null;
    
    public function __construct(
        protected AI $provider
    ) {}
    
    /**
     * Execute TaskBuilder with tool support.
     * 
     * @param TaskBuilder $task The task to execute
     * @return array Result with tools_used and tool_results
     */
    public function execute(TaskBuilder $task): array
    {
        $tools = $this->getTaskTools($task);
        $prompt = $this->getTaskPrompt($task);
        $strict = $this->isTaskStrict($task);
        
        // 1. Plan which tools to use
        $plan = $this->getPlanner()->plan($prompt, $tools);
        
        // 2. Execute tools
        $toolResults = $this->getExecutor()->execute($plan, $tools);
        
        // 3. Prepare final prompt with tool results
        $this->preparePromptWithResults($task, $prompt, $toolResults, $strict);
        
        // 4. Run normal TaskBuilder flow (without tools to avoid recursion)
        $result = $this->runTaskWithoutTools($task);
        
        // 5. Enhance result with tool metadata
        $result['tools_used'] = array_keys($toolResults);
        $result['tool_results'] = $toolResults;
        $result['reasoning'] = $plan['reasoning'] ?? '';
        
        return $result;
    }
    
    /**
     * Create a conversation session.
     */
    public function conversation(TaskBuilder $task, string $sessionId, int $maxHistory, int $ttl): Conversation
    {
        return new Conversation($this, $task, $sessionId, $maxHistory, $ttl);
    }
    
    /**
     * Prepare TaskBuilder prompt with tool results and anti-hallucination instructions.
     */
    protected function preparePromptWithResults(TaskBuilder $task, string $originalPrompt, array $toolResults, bool $strict): void
    {
        if (empty($toolResults)) {
            // No tools used - keep original prompt (conversational mode)
            return;
        }
        
        // Build prompt with tool results
        if ($strict) {
            $prompt = "CRITICAL RULES:\n";
            $prompt .= "1. You MUST ONLY use information from the Tool Results provided below\n";
            $prompt .= "2. DO NOT invent, assume, or add any information not in the Tool Results\n";
            $prompt .= "3. If Tool Results are empty or insufficient, say 'I don't have enough information to answer that'\n";
            $prompt .= "4. Be concise and direct - limit responses to the most relevant information\n";
            $prompt .= "5. For product lists, show maximum 5 items unless specifically asked for more\n\n";
            $prompt .= "User Question: {$originalPrompt}\n\n";
            $prompt .= "Tool Results (USE ONLY THIS DATA):\n";
            $prompt .= $this->getExecutor()->formatResults($toolResults);
            $prompt .= "\n\nAnswer (using ONLY the Tool Results above):";
        } else {
            $prompt = "User Question: {$originalPrompt}\n\n";
            $prompt .= "Tool Results:\n";
            $prompt .= $this->getExecutor()->formatResults($toolResults);
            $prompt .= "\n\nProvide a helpful answer based on the tool results:";
        }
        
        $this->setTaskPrompt($task, $prompt);
    }
    
    /**
     * Run TaskBuilder without triggering tool execution again.
     */
    protected function runTaskWithoutTools(TaskBuilder $task): array
    {
        // Temporarily clear tools to prevent recursion
        $tools = $this->getTaskTools($task);
        $this->setTaskTools($task, []);
        
        // Run normal TaskBuilder flow
        $result = $task->run();
        
        // Restore tools
        $this->setTaskTools($task, $tools);
        
        return $result;
    }
    
    /**
     * Get Planner instance.
     */
    protected function getPlanner(): Planner
    {
        if ($this->planner === null) {
            $this->planner = new Planner($this->provider);
        }
        return $this->planner;
    }
    
    /**
     * Get Executor instance.
     */
    protected function getExecutor(): Executor
    {
        if ($this->executor === null) {
            $this->executor = new Executor();
        }
        return $this->executor;
    }
    
    // Helper methods for accessing TaskBuilder protected properties via reflection
    
    protected function getTaskTools(TaskBuilder $task): array
    {
        return $this->getProperty($task, 'tools');
    }
    
    protected function setTaskTools(TaskBuilder $task, array $tools): void
    {
        $this->setProperty($task, 'tools', $tools);
    }
    
    protected function getTaskPrompt(TaskBuilder $task): string
    {
        return $this->getProperty($task, 'prompt') ?? '';
    }
    
    protected function setTaskPrompt(TaskBuilder $task, string $prompt): void
    {
        $this->setProperty($task, 'prompt', $prompt);
    }
    
    protected function isTaskStrict(TaskBuilder $task): bool
    {
        return $this->getProperty($task, 'strictTools') ?? false;
    }
    
    protected function getProperty(object $obj, string $property): mixed
    {
        $reflection = new \ReflectionClass($obj);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($obj);
    }
    
    protected function setProperty(object $obj, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($obj);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($obj, $value);
    }
}
