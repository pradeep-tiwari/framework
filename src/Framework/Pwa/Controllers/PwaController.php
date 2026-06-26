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
            $existing = PwaSubscription::query()
                ->where('endpoint', $data['endpoint'])
                ->one();

            if ($existing) {
                $existing->p256dh = $data['keys']['p256dh'];
                $existing->auth = $data['keys']['auth'];
                $existing->user_id = $data['user_id'];
                $existing->save();
                $subscription = $existing;
            } else {
                $subscription = new PwaSubscription;
                $subscription->endpoint = $data['endpoint'];
                $subscription->p256dh = $data['keys']['p256dh'];
                $subscription->auth = $data['keys']['auth'];
                $subscription->user_id = $data['user_id'];
                $subscription->save();
            }

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

        PwaSubscription::query()->where('endpoint', $endpoint)->delete();

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
