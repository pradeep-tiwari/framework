<?php

/**
 * Module-specific filters.
 * 
 * Register filter aliases that can be used in module routes.
 * These are merged with app-level filters during bootstrap.
 */

return [
    'blog.auth' => \Modules\Blog\Filters\AuthFilter::class,
    'blog.admin' => \Modules\Blog\Filters\AdminFilter::class,
];
