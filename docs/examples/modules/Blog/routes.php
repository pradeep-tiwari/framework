<?php

/**
 * Blog Module Routes
 * 
 * These routes are automatically loaded by BlogProvider.
 */

$route = app('route');

// Group all blog routes under /blog prefix
$route->group(['prefix' => 'blog'], function($route) {
    // List all posts
    $route->get('/posts', \Modules\Blog\Controllers\PostController::class, 'index')
        ->name('blog.posts.index');
    
    // Show single post
    $route->get('/posts/:slug', \Modules\Blog\Controllers\PostController::class, 'show')
        ->name('blog.posts.show');
    
    // Create post (requires auth)
    $route->get('/posts/create', \Modules\Blog\Controllers\PostController::class, 'create')
        ->filter('auth')
        ->name('blog.posts.create');
    
    $route->post('/posts', \Modules\Blog\Controllers\PostController::class, 'store')
        ->filter('auth')
        ->name('blog.posts.store');
});
