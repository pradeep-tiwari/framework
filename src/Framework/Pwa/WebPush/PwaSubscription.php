<?php

namespace Lightpack\Pwa\WebPush;

use Lightpack\Database\Lucid\Model;

/**
 * Subscription - Model for PWA push subscriptions
 * 
 * Represents a user's push notification subscription.
 */
class PwaSubscription extends Model
{
    protected $table = 'pwa_subscriptions';
    protected $timestamps = true;

    protected $casts = [
        'keys' => 'array',
    ];

    /**
     * Create or update subscription
     */
    public static function createOrUpdate(array $data): self
    {
        $existing = self::query()
            ->where('endpoint', $data['endpoint'])
            ->one();

        if ($existing) {
            $existing->p256dh = $data['keys']['p256dh'];
            $existing->auth = $data['keys']['auth'];
            $existing->user_id = $data['user_id'] ?? null;
            $existing->save();
            return $existing;
        }

        $subscription = new self();
        $subscription->endpoint = $data['endpoint'];
        $subscription->p256dh = $data['keys']['p256dh'];
        $subscription->auth = $data['keys']['auth'];
        $subscription->user_id = $data['user_id'] ?? null;
        $subscription->save();

        return $subscription;
    }

    /**
     * Get subscription as array for WebPush
     */
    public function toArray(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->p256dh,
                'auth' => $this->auth,
            ],
        ];
    }

    /**
     * Get all subscriptions for a user
     */
    public static function forUser(int $userId): array
    {
        return self::query()
            ->where('user_id', $userId)
            ->all()
            ->map(fn($sub) => $sub->toArray())
            ->toArray();
    }

    /**
     * Remove subscription by endpoint
     */
    public static function removeByEndpoint(string $endpoint): bool
    {
        $subscription = self::query()
            ->where('endpoint', $endpoint)
            ->one();

        if ($subscription) {
            return $subscription->delete();
        }

        return false;
    }

    /**
     * Get all active subscriptions
     */
    public static function allActive(): array
    {
        return self::query()
            ->all()
            ->map(fn($sub) => $sub->toArray())
            ->toArray();
    }
}
