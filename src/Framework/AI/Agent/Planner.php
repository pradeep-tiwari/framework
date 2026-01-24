<?php

namespace Lightpack\AI\Agent;

use Lightpack\AI\AI;
use Lightpack\AI\TaskBuilder;

/**
 * Planner: Decides which tools to use based on user query.
 * Uses TaskBuilder internally for structured output extraction.
 */
class Planner
{
    public function __construct(
        protected AI $provider
    ) {}
    
    /**
     * Plan which tools to use for the given query.
     * 
     * @param string $prompt User's query
     * @param array $tools Available tools
     * @param array $context Additional context (conversation history, etc.)
     * @return array Plan with needs_tools, tools, parameters, reasoning
     */
    public function plan(string $prompt, array $tools, array $context = []): array
    {
        $toolList = $this->describeTools($tools);
        $planPrompt = $this->buildPlanningPrompt($prompt, $toolList, $context);
        
        // Use TaskBuilder for planning
        $planner = new TaskBuilder($this->provider);
        $result = $planner
            ->prompt($planPrompt)
            ->expect([
                'needs_tools' => ['bool', 'Whether tools are needed (false for greetings/casual chat)'],
                'tools' => ['array', 'Tool names to use (empty array if needs_tools is false)'],
                'parameters' => ['object', 'Parameters for each tool (key: tool name, value: parameters object)'],
                'reasoning' => ['string', 'Brief explanation of tool selection decision']
            ])
            ->temperature(0.2) // Low temperature for deterministic planning
            ->run();
        
        if (!$result['success']) {
            return [
                'needs_tools' => false,
                'tools' => [],
                'parameters' => [],
                'reasoning' => 'Planning failed: ' . implode(', ', $result['errors'])
            ];
        }
        
        return $result['data'];
    }
    
    /**
     * Build the planning prompt with rules and context.
     */
    protected function buildPlanningPrompt(string $query, string $toolList, array $context): string
    {
        $prompt = "TOOL SELECTION RULES:\n";
        $prompt .= "1. ONLY use tools if the user is asking for specific information that requires data retrieval\n";
        $prompt .= "2. DO NOT use tools for: greetings ('hi', 'hello', 'hey'), thank you messages, casual conversation\n";
        $prompt .= "3. If user just says 'hi', 'hello', 'thanks', etc. → set needs_tools=false and return empty tools array\n";
        $prompt .= "4. If user asks a question that needs data (e.g., 'show me products', 'find X') → set needs_tools=true and select appropriate tools\n";
        $prompt .= "5. When in doubt, do NOT use tools - respond conversationally instead\n\n";
        
        $prompt .= "User Query: {$query}\n\n";
        $prompt .= "Available Tools:\n{$toolList}\n\n";
        
        if (!empty($context['conversation_history'])) {
            $prompt .= "Recent Conversation:\n{$context['conversation_history']}\n\n";
        }
        
        $prompt .= "Decision: Should tools be used for this query? If yes, which ones and with what parameters?";
        
        return $prompt;
    }
    
    /**
     * Describe available tools in a format the AI can understand.
     */
    protected function describeTools(array $tools): string
    {
        $descriptions = [];
        
        foreach ($tools as $name => $tool) {
            $desc = "- {$name}: {$tool['description']}";
            
            if (!empty($tool['params'])) {
                $params = [];
                foreach ($tool['params'] as $paramName => $paramType) {
                    $params[] = "  - {$paramName} ({$paramType})";
                }
                $desc .= "\n" . implode("\n", $params);
            }
            
            $descriptions[] = $desc;
        }
        
        return implode("\n", $descriptions);
    }
}
