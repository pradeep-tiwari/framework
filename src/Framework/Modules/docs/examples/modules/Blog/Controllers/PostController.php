<?php

namespace Modules\Blog\Controllers;

use Modules\Blog\Models\Post;

class PostController
{
    public function index()
    {
        $posts = Post::query()->all();
        
        return template('blog::posts/index', [
            'posts' => $posts
        ]);
    }
    
    public function show($slug)
    {
        $post = Post::query()
            ->where('slug', $slug)
            ->one();
        
        if (!$post) {
            return redirect('/blog/posts');
        }
        
        return template('blog::posts/show', [
            'post' => $post
        ]);
    }
    
    public function create()
    {
        return template('blog::posts/create');
    }
    
    public function store()
    {
        $request = app('request');
        
        $post = new Post();
        $post->title = $request->post('title');
        $post->content = $request->post('content');
        $post->slug = $this->generateSlug($request->post('title'));
        $post->save();
        
        return redirect('/blog/posts/' . $post->slug);
    }
    
    private function generateSlug(string $title): string
    {
        return strtolower(str_replace(' ', '-', $title));
    }
}
