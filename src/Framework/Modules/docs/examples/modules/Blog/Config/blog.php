<?php

/**
 * Blog Module Configuration
 * 
 * Access via: app('config')->get('modules.blog.blog.posts_per_page')
 */

return [
    'posts_per_page' => 10,
    'allow_comments' => true,
    'cache_duration' => 3600, // 1 hour
    'featured_posts_count' => 5,
];
