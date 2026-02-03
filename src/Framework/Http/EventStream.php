<?php

namespace Lightpack\Http;

class EventStream extends Response
{
    public function __construct()
    {
        // Set SSE headers by default
        $this->setHeader('Content-Type', 'text/event-stream')
             ->setHeader('Cache-Control', 'no-cache')
             ->setHeader('Connection', 'keep-alive')
             ->setHeader('X-Accel-Buffering', 'no');
    }
    
    /**
     * Push an event to the SSE stream.
     * 
     * @param string $type Event type (e.g., 'chunk', 'done', 'error')
     * @param array $data Event data to send
     * @return void
     */
    public function push(string $type, array $data = []): void
    {
        $payload = array_merge(['type' => $type], $data);
        echo "data: " . json_encode($payload) . "\n\n";
        
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
    
    /**
     * Set up streaming with a callback that receives this EventStream instance.
     * 
     * @param callable $callback Callback that receives EventStream instance
     * @return self
     */
    public function using(callable $callback): self
    {
        $this->stream(function() use ($callback) {
            $callback($this);
        });
        
        return $this;
    }
}
