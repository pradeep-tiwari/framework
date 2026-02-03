<?php

namespace Lightpack\Http;

class EventStream
{
    /**
     * Push an event to the SSE stream.
     * 
     * @param string $event Event type (e.g., 'start', 'progress', 'done', 'error')
     * @param array $data Event data to send
     * @return void
     */
    public function push(string $event, array $data = []): void
    {
        $payload = array_merge(['event' => $event], $data);
        echo "data: " . json_encode($payload) . "\n\n";
        
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
