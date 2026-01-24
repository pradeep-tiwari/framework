<?php

namespace Lightpack\AI\Agent;

use Lightpack\AI\AI;
use Lightpack\AI\TaskBuilder;

/**
 * Agent: Intent-based AI agent inspired by Dialogflow.
 * Flow: Intent Recognition → Entity Extraction → Tool Execution → Response Generation
 */
class Agent
{
    protected array $intents = [];
    protected array $tools = [];
    protected ?string $systemPrompt = null;
    protected float $temperature = 0.3;
    protected float $intentThreshold = 0.6;
    
    protected IntentMatcher $intentMatcher;
    protected EntityExtractor $entityExtractor;
    
    public function __construct(
        protected AI $provider
    ) {
        $this->intentMatcher = new IntentMatcher($provider);
        $this->entityExtractor = new EntityExtractor($provider);
    }
    
    /**
     * Register an intent with its tool.
     */
    public function intent(
        string $name,
        array $patterns,
        string $tool,
        array $entities = [],
        ?string $description = null
    ): self {
        $this->intents[$name] = Intent::create($name, $patterns, $tool, $entities, $description);
        return $this;
    }
    
    /**
     * Register a tool function.
     */
    public function tool(string $name, callable $fn, ?string $description = null): self
    {
        $this->tools[$name] = [
            'fn' => $fn,
            'description' => $description ?? "Tool: {$name}"
        ];
        return $this;
    }
    
    /**
     * Set system prompt for response generation.
     */
    public function system(string $prompt): self
    {
        $this->systemPrompt = $prompt;
        return $this;
    }
    
    /**
     * Set temperature for AI responses.
     */
    public function temperature(float $temp): self
    {
        $this->temperature = $temp;
        return $this;
    }
    
    /**
     * Set minimum confidence threshold for intent matching.
     */
    public function intentThreshold(float $threshold): self
    {
        $this->intentThreshold = $threshold;
        return $this;
    }
    
    /**
     * Ask a question and get a response.
     */
    public function ask(string $query): AgentResult
    {
        try {
            // 1. Match intent
            $intentMatch = $this->intentMatcher->match($query, $this->intents);
            logger()->info('Agent: Intent matched', ['intent' => $intentMatch['intent'], 'confidence' => $intentMatch['confidence']]);
            
            // Check confidence threshold
            if ($intentMatch['confidence'] < $this->intentThreshold || $intentMatch['intent'] === 'none') {
                logger()->info('Agent: Using conversational mode', ['reason' => 'Low confidence or no intent']);
                return $this->conversationalResponse($query, $intentMatch);
            }
            
            $intent = $this->intents[$intentMatch['intent']];
            
            // 2. Extract entities
            $entities = $this->entityExtractor->extract($query, $intent->entities);
            logger()->info('Agent: Entities extracted', ['entities' => $entities]);
            
            // 3. Execute tool
            $toolResult = $this->executeTool($intent->tool, $entities);
            logger()->info('Agent: Tool executed', ['tool' => $intent->tool, 'result_type' => gettype($toolResult)]);
            
            // 4. Generate response
            $answer = $this->generateResponse($query, $toolResult);
            logger()->info('Agent: Response generated', ['answer_length' => strlen($answer)]);
            
            return new AgentResult(
                answer: $answer,
                intent: $intentMatch['intent'],
                confidence: $intentMatch['confidence'],
                entities: $entities,
                toolResult: $toolResult,
                reasoning: $intentMatch['reasoning']
            );
        } catch (\Exception $e) {
            logger()->error('Agent: Exception in ask()', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return new AgentResult(
                answer: 'I encountered an error: ' . $e->getMessage(),
                intent: 'error',
                confidence: 0.0,
                entities: [],
                toolResult: null,
                reasoning: 'Exception: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Create a conversation session.
     */
    public function conversation(string $sessionId, int $maxHistory = 10, int $ttl = 3600): Conversation
    {
        return new Conversation($this, $sessionId, $maxHistory, $ttl);
    }
    
    /**
     * Execute a tool with extracted entities.
     */
    protected function executeTool(string $toolName, array $params): mixed
    {
        if (!isset($this->tools[$toolName])) {
            return ['error' => "Tool '{$toolName}' not found"];
        }
        
        try {
            return $this->tools[$toolName]['fn']($params);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Generate response from tool result.
     */
    protected function generateResponse(string $query, mixed $toolResult): string
    {
        try {
            $context = "User Query: {$query}\n\n";
            $context .= "Tool Result:\n";
            $context .= is_array($toolResult) 
                ? json_encode($toolResult, JSON_PRETTY_PRINT) 
                : (string)$toolResult;
            $context .= "\n\nProvide a helpful, conversational answer based on the tool result:";
            
            $task = (new TaskBuilder($this->provider))
                ->prompt($context)
                ->temperature($this->temperature);
            
            if ($this->systemPrompt) {
                $task->system($this->systemPrompt);
            }
            
            $result = $task->run();
            
            // For plain text responses, success is always true if we got a response
            return $result['raw'] ?? 'I encountered an error processing your request.';
        } catch (\Exception $e) {
            logger()->error('Agent: Exception in generateResponse', ['error' => $e->getMessage()]);
            return 'I encountered an error: ' . $e->getMessage();
        }
    }
    
    /**
     * Handle conversational queries (no intent match).
     */
    protected function conversationalResponse(string $query, array $intentMatch): AgentResult
    {
        $task = (new TaskBuilder($this->provider))
            ->prompt($query)
            ->temperature($this->temperature);
        
        if ($this->systemPrompt) {
            $task->system($this->systemPrompt);
        }
        
        $result = $task->run();
        
        return new AgentResult(
            answer: $result['success'] ? $result['raw'] : 'I encountered an error.',
            intent: 'conversational',
            confidence: 0.0,
            entities: [],
            toolResult: null,
            reasoning: $intentMatch['reasoning'] ?? 'No intent matched, using conversational mode'
        );
    }
}
