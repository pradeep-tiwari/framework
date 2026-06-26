<?php

namespace Lightpack\Pwa\WebPush;

/**
 * Developer-facing API for sending Web Push notifications.
 *
 * Provides a fluent builder for composing notification payloads
 * and delegates the actual encryption (RFC 8291) and HTTP delivery
 * to {@see WebPushEngine}.
 *
 * Single subscription:
 *   (new WebPush)->title('Hello')->body('...')->to($sub)->send();
 *
 * Broadcast to all subscribers:
 *   (new WebPush)->broadcast('Sale!', ['body' => '20% off']);
 */
class WebPush
{
    protected array $config;
    protected ?array $subscription = null;
    protected array $payload = [];

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? $this->loadConfig();
        $this->validateConfig();
    }

    /**
     * Set target subscription
     */
    public function to(array $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    /**
     * Set notification title
     */
    public function title(string $title): self
    {
        $this->payload['title'] = $title;

        return $this;
    }

    /**
     * Set notification body
     */
    public function body(string $body): self
    {
        $this->payload['body'] = $body;

        return $this;
    }

    /**
     * Set notification icon
     */
    public function icon(string $icon): self
    {
        $this->payload['icon'] = $icon;

        return $this;
    }

    /**
     * Set notification badge
     */
    public function badge(string $badge): self
    {
        $this->payload['badge'] = $badge;

        return $this;
    }

    /**
     * Set notification data
     */
    public function data(array $data): self
    {
        $this->payload['data'] = $data;

        return $this;
    }

    /**
     * Set require interaction flag
     */
    public function requireInteraction(bool $require = true): self
    {
        $this->payload['requireInteraction'] = $require;

        return $this;
    }

    /**
     * Set vibration pattern
     */
    public function vibrate(array $pattern): self
    {
        $this->payload['vibrate'] = $pattern;

        return $this;
    }

    /**
     * Set notification actions
     */
    public function actions(array $actions): self
    {
        $this->payload['actions'] = $actions;

        return $this;
    }

    /**
     * Set notification tag
     */
    public function tag(string $tag): self
    {
        $this->payload['tag'] = $tag;

        return $this;
    }

    /**
     * Send push notification
     */
    public function send(): bool
    {
        if (! $this->subscription) {
            throw new \RuntimeException('No subscription set. Use to() method first.');
        }

        $engine = new WebPushEngine($this->config);

        $result = $engine
            ->to($this->subscription)
            ->setPayload($this->payload)
            ->send();

        $this->payload = [];
        $this->subscription = null;

        return $result;
    }

    /**
     * Broadcast to all subscribers
     */
    public function broadcast(string $title, array $options = []): int
    {
        $this->payload = [];
        $this->subscription = null;
        $this->title($title);

        foreach (['body', 'icon', 'badge', 'data', 'requireInteraction', 'vibrate', 'actions', 'tag'] as $field) {
            if (isset($options[$field])) {
                $this->$field($options[$field]);
            }
        }

        $subscriptions = PwaSubscription::allActive();
        $payload = $this->payload;
        $sent = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $this->payload = $payload;
                $this->to($subscription)->send();
                $sent++;
            } catch (\RuntimeException $e) {
                if ($e->getCode() === 410 || $e->getCode() === 404) {
                    PwaSubscription::removeByEndpoint($subscription['endpoint']);
                }
            }
        }

        return $sent;
    }

    /**
     * Load configuration
     */
    protected function loadConfig(): array
    {
        $config = app('config');

        return [
            'vapid_subject' => $config->get('pwa.vapid_subject'),
            'vapid_public_key' => $config->get('pwa.vapid_public_key'),
            'vapid_private_key' => $config->get('pwa.vapid_private_key'),
        ];
    }

    /**
     * Validate configuration
     */
    protected function validateConfig(): void
    {
        $required = ['vapid_subject', 'vapid_public_key', 'vapid_private_key'];

        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new \RuntimeException("Missing required config: {$key}");
            }
        }
    }
}
