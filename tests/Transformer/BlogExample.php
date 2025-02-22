<?php

namespace Tests\Transformer;

require_once __DIR__ . '/../../vendor/autoload.php';

use Lightpack\Transformer\TransformerRegistry;

// Example Models (in real app these would be separate files)
class Post {
    public $id = 1;
    public $title = "Understanding Transformers";
    public $content = "This is a detailed post about transformers...";
    public $author;
    public $comments = [];
    public $created_at;

    public function __construct() {
        $this->created_at = new \DateTime('2025-01-01 10:00:00');
        
        // Create author
        $this->author = new User();
        
        // Create some comments
        $this->comments = [
            new Comment("Great post!", "2025-01-01 10:30:00"),
            new Comment("Very helpful", "2025-01-01 11:00:00"),
        ];
    }
}

class User {
    public $id = 1;
    public $name = "John Doe";
    public $email = "john@example.com";
    public $role = "admin";
}

class Comment {
    public $content;
    public $created_at;
    public $author;

    public function __construct($content, $date) {
        $this->content = $content;
        $this->created_at = new \DateTime($date);
        $this->author = new User(); // Simplified: same user for demo
    }
}

// Transformers
class PostTransformer extends \Lightpack\Transformer\AbstractTransformer {
    private $userTransformer;
    private $commentTransformer;

    public function __construct() {
        $this->userTransformer = new UserTransformer();
        $this->commentTransformer = new CommentTransformer();
    }

    public function transform($post): array {
        $data = [
            'id' => $post->id,
            'title' => $post->title,
        ];

        // Content only in detailed view
        if ($this->inGroups(['detailed'])) {
            $data['content'] = $post->content;
        }

        // Created at in both basic and detailed
        if ($this->inGroups(['basic', 'detailed'])) {
            $data['created_at'] = $post->created_at->format('Y-m-d H:i:s');
        }

        // Relations based on includes
        if ($this->shouldInclude('author')) {
            $data['author'] = $this->userTransformer->groups($this->groups)->transform($post->author);
        }

        if ($this->shouldInclude('comments')) {
            $data['comments'] = $this->commentTransformer->groups($this->groups)->collection($post->comments);
        }

        return $data;
    }
}

class UserTransformer extends \Lightpack\Transformer\AbstractTransformer {
    public function transform($user): array {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
        ];

        // Role only in detailed view
        if ($this->inGroups(['detailed'])) {
            $data['role'] = $user->role;
        }

        return $data;
    }
}

class CommentTransformer extends \Lightpack\Transformer\AbstractTransformer {
    private $userTransformer;

    public function __construct() {
        $this->userTransformer = new UserTransformer();
    }

    public function transform($comment): array {
        $data = [
            'content' => $comment->content,
        ];

        // Created at only in detailed view
        if ($this->inGroups(['detailed'])) {
            $data['created_at'] = $comment->created_at->format('Y-m-d H:i:s');
        }

        if ($this->shouldInclude('author')) {
            $data['author'] = $this->userTransformer->groups($this->groups)->transform($comment->author);
        }

        return $data;
    }
}

// Example Usage
$post = new Post();
$transformer = new PostTransformer();

// Show different scenarios
echo "1. Basic data (minimal fields):\n";
$result = $transformer->groups(['basic'])->transform($post);
var_export($result);

echo "\n\n2. Detailed data with author:\n";
$result = $transformer->groups(['detailed'])->with(['author'])->transform($post);
var_export($result);

echo "\n\n3. Full detailed data with all relations:\n";
$result = $transformer->groups(['detailed'])->with(['author', 'comments'])->transform($post);
var_export($result);
