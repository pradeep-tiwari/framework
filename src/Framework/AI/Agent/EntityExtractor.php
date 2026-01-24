<?php

namespace Lightpack\AI\Agent;

use Lightpack\AI\AI;
use Lightpack\AI\TaskBuilder;

/**
 * EntityExtractor: Extracts parameters from user query.
 * Similar to Dialogflow's entity extraction.
 */
class EntityExtractor
{
    public function __construct(
        protected AI $provider
    ) {}
    
    /**
     * Extract entities from user query based on intent requirements.
     * 
     * @param string $query User's question
     * @param array $entitySchema Expected entities with types
     * @return array Extracted entity values
     */
    public function extract(string $query, array $entitySchema): array
    {
        if (empty($entitySchema)) {
            return [];
        }
        
        $prompt = "Extract the following information from the user query.\n\n";
        $prompt .= "User Query: {$query}\n\n";
        $prompt .= "Extract these entities:\n";
        
        foreach ($entitySchema as $entity => $type) {
            $typeDesc = is_array($type) ? $type[1] : "Type: {$type}";
            $prompt .= "- {$entity}: {$typeDesc}\n";
        }
        
        $prompt .= "\nIf an entity is not mentioned, set it to null.";
        
        $result = (new TaskBuilder($this->provider))
            ->prompt($prompt)
            ->expect($entitySchema)
            ->temperature(0.1)
            ->run();
        
        if (!$result['success']) {
            return [];
        }
        
        // Filter out null values
        return array_filter($result['data'], fn($v) => $v !== null);
    }
}
