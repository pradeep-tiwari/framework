<?php

/**
 * Blog Module Events
 * 
 * Register event listeners for blog-related events.
 */

return [
    'post.created' => [
        \Modules\Blog\Events\NotifySubscribers::class,
    ],
    
    'post.published' => [
        \Modules\Blog\Events\SendNotification::class,
    ],
];
