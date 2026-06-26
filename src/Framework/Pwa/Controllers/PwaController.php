<?php

namespace Lightpack\Pwa\Controllers;

use Lightpack\Pwa\WebPush\PwaSubscription;

/**
 * PwaController - Handles PWA subscription management endpoints
 */
class PwaController
{
    /**
     * Store a new push subscription
     */
    public function subscribe()
    {
        $data = request()->input();

        if (empty($data['endpoint']) || empty($data['keys']['p256dh']) || empty($data['keys']['auth'])) {
            return response()->json(['error' => 'Invalid subscription data'], 400);
        }

        $data['user_id'] = auth()->id();

        try {
            $subscription = PwaSubscription::createOrUpdate($data);

            return response()->json([
                'success' => true,
                'id' => $subscription->id,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to save subscription'], 500);
        }
    }

    /**
     * Remove a push subscription by endpoint
     */
    public function unsubscribe()
    {
        $endpoint = request()->input('endpoint');

        if (empty($endpoint)) {
            return response()->json(['error' => 'Endpoint required'], 400);
        }

        PwaSubscription::removeByEndpoint($endpoint);

        return response()->json(['success' => true]);
    }

    /**
     * Check subscription status for the current user
     */
    public function status()
    {
        $endpoint = request()->input('endpoint');

        if (empty($endpoint)) {
            return response()->json(['subscribed' => false]);
        }

        $subscription = PwaSubscription::query()
            ->where('endpoint', $endpoint)
            ->one();

        return response()->json(['subscribed' => $subscription !== null]);
    }
}
