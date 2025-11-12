<?php

/**
 * Blog Module Events
 * 
 * Register event listeners for blog-related events.
 */

return [
    'post.created' => [
        \Modules\Blog\Listeners\NotifySubscribers::class,
    ],
    
    'post.published' => [
        \Modules\Blog\Listeners\SendNotification::class,
    ],
];
