<?php

namespace Lightpack\AI\Agent;

use Lightpack\AI\AI;
use Lightpack\AI\TaskBuilder;

/**
 * IntentMatcher: Uses AI to match user query to registered intents.
 * Similar to Dialogflow's intent recognition.
 */
class IntentMatcher
{
    public function __construct(
        protected AI $provider
    ) {}
    
    /**
     * Match user query to the best intent.
     * 
     * @param string $query User's question
     * @param array $intents Array of Intent objects
     * @return array ['intent' => string, 'confidence' => float, 'reasoning' => string]
     */
    public function match(string $query, array $intents): array
    {
        if (empty($intents)) {
            return [
                'intent' => 'none',
                'confidence' => 0.0,
                'reasoning' => 'No intents registered'
            ];
        }
        
        // Build intent descriptions for AI
        $intentList = $this->describeIntents($intents);
        
        $prompt = "Match the user query to the most appropriate intent.\n\n";
        $prompt .= "User Query: {$query}\n\n";
        $prompt .= "Available Intents:\n{$intentList}\n\n";
        $prompt .= "IMPORTANT:\n";
        $prompt .= "- Select the best matching intent name (or 'none' if no good match)\n";
        $prompt .= "- Provide a confidence score as a NUMBER between 0.0 (no match) and 1.0 (perfect match)\n";
        $prompt .= "- Explain your reasoning\n\n";
        $prompt .= "Example response format:\n";
        $prompt .= '{"intent": "search_products", "confidence": 0.95, "reasoning": "User wants to find products"}';
        
        $result = (new TaskBuilder($this->provider))
            ->prompt($prompt)
            ->expect([
                'intent' => ['string', 'Name of the matched intent or "none"'],
                'confidence' => ['number', 'Confidence score 0.0-1.0'],
                'reasoning' => ['string', 'Why this intent was selected']
            ])
            ->temperature(0.2)
            ->run();
        
        logger()->info('IntentMatcher: AI response', [
            'success' => $result['success'],
            'raw' => $result['raw'] ?? 'no raw',
            'data' => $result['data'] ?? 'no data',
            'errors' => $result['errors'] ?? []
        ]);
        
        if (!$result['success']) {
            return [
                'intent' => 'none',
                'confidence' => 0.0,
                'reasoning' => 'Intent matching failed'
            ];
        }
        
        return $result['data'];
    }
    
    /**
     * Describe intents for AI matching.
     */
    protected function describeIntents(array $intents): string
    {
        $descriptions = [];
        
        foreach ($intents as $intent) {
            $patterns = implode(', ', array_map(fn($p) => "\"{$p}\"", $intent->patterns));
            $desc = "- {$intent->name}: {$intent->description}\n";
            $desc .= "  Patterns: {$patterns}";
            $descriptions[] = $desc;
        }
        
        return implode("\n", $descriptions);
    }
}
