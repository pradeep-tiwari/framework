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
}
