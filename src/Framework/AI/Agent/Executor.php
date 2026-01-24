<?php

namespace Lightpack\AI\Agent;

/**
 * Executor: Executes planned tools and handles errors.
 * Responsible for calling tool functions and limiting results.
 */
class Executor
{
    /**
     * Execute the planned tools.
     * 
     * @param array $plan Plan from Planner with tools and parameters
     * @param array $tools Available tools with their functions
     * @return array Tool results (key: tool name, value: result or error)
     */
    public function execute(array $plan, array $tools): array
    {
        // If no tools needed, return empty results
        if (empty($plan['needs_tools']) || empty($plan['tools'])) {
            return [];
        }
        
        $results = [];
        
        foreach ($plan['tools'] as $toolName) {
            if (!isset($tools[$toolName])) {
                $results[$toolName] = [
                    'error' => "Tool '{$toolName}' not found in registered tools"
                ];
                continue;
            }
            
            try {
                $tool = $tools[$toolName];
                $params = $plan['parameters'][$toolName] ?? [];
                
                // Execute the tool function
                $result = $tool['fn']($params);
                
                // Limit results to prevent information overload
                $results[$toolName] = $this->limitResult($result);
                
            } catch (\Exception $e) {
                $results[$toolName] = [
                    'error' => $e->getMessage(),
                    'type' => get_class($e)
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Limit result size to prevent overload.
     * Arrays are limited to 10 items max.
     */
    protected function limitResult(mixed $result): mixed
    {
        if (is_array($result) && count($result) > 10) {
            return array_slice($result, 0, 10);
        }
        
        return $result;
    }
    
    /**
     * Format tool results for display in prompts.
     */
    public function formatResults(array $results): string
    {
        if (empty($results)) {
            return 'No tool results available';
        }
        
        $formatted = [];
        
        foreach ($results as $toolName => $result) {
            $formatted[] = "Tool: {$toolName}\n" . json_encode($result, JSON_PRETTY_PRINT);
        }
        
        return implode("\n\n", $formatted);
    }
}
