<?php

namespace Lightpack\Cable\Drivers;

use Lightpack\Cable\CableDriverInterface;
use Lightpack\Http\Http;
use Lightpack\Debug\Debug;

/**
 * Mercure Driver for Cable
 * 
 * This driver publishes events to a Mercure hub using HTTP POST.
 * Note: Message retrieval (getMessages) is handled client-side via SSE.
 */
class MercureCableDriver implements CableDriverInterface
{
    /**
     * @var string
     */
    protected $hubUrl;

    /**
     * @var string|null
     */
    protected $jwt;

    /**
     * Create a new Mercure driver
     * @param string $hubUrl The Mercure hub publish URL (e.g. http://localhost:3000/.well-known/mercure)
     * @param string|null $jwt The JWT for authentication (if required)
     */
    public function __construct(string $hubUrl, ?string $jwt = null)
    {
        $this->hubUrl = rtrim($hubUrl, '/');
        $this->jwt = $jwt;
    }

    /**
     * Emit an event to a channel (topic)
     * @param string $channel The channel/topic
     * @param string $event The event name
     * @param array $payload The event payload
     */
    public function emit(string $channel, string $event, array $payload): void
    {
        $data = [
            'topic' => $this->formatTopic($channel),
            'data' => json_encode([
                'event' => $event,
                'payload' => $payload,
                'timestamp' => time(),
            ]),
        ];

        $http = new Http();
        $http->headers(['Content-Type' => 'application/x-www-form-urlencoded']);

        if ($this->jwt) {
            $http->token($this->jwt);
        }

        // Mercure expects form-encoded data
        $http->timeout(2)->form()->post($this->hubUrl, $data);

        // Error logging
        if ($http->failed()) {
            logger()->error('Cable: Mercure emit failed', [
                'driver' => 'MercureCableDriver',
                'action' => 'emit',
                'channel' => $channel,
                'event' => $event,
                'status' => $http->status(),
                'error' => $http->error(),
                'response' => $http->body(),
            ]);
        }
    }

    /**
     * Mercure does not support server-side message retrieval; handled via SSE in JS.
     * This method returns an empty array and should not be used for polling.
     */
    public function getMessages(string $channel, ?int $lastId = null): array
    {
        // Not supported: Mercure clients subscribe via SSE directly
        return [];
    }

    /**
     * Mercure handles message expiry; cleanup is a no-op.
     */
    public function cleanup(int $olderThanSeconds = 86400): void
    {
        // No-op
    }

    /**
     * Format the topic string for Mercure (e.g., "/cable/{channel}")
     */
    protected function formatTopic(string $channel): string
    {
        // You may customize topic naming here
        return '/cable/' . urlencode($channel);
    }
}
