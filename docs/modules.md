# Modules

Lightpack supports a modular architecture that allows you to organize your application into self-contained, reusable modules. Modules are completely **optional** - simple applications work perfectly fine without them.

## When to Use Modules

- **Large applications** with distinct feature areas (Blog, Shop, Forum, etc.)
- **Team collaboration** where different teams work on different features
- **Reusable packages** that can be shared across projects
- **Third-party integrations** that need to be isolated

For small to medium applications, the standard `/app` directory structure is recommended.

## Module Structure

```
modules/
└── Blog/
    ├── Controllers/
    │   └── PostController.php
    ├── Models/
    │   └── Post.php
    ├── Views/
    │   └── posts/
    │       └── index.php
    ├── Database/
    │   └── Migrations/
    │       └── 001_create_posts_table.php
    ├── Tests/
    │   ├── Feature/
    │   └── Unit/
    ├── Providers/
    │   └── BlogProvider.php
    ├── routes.php
    ├── events.php
    ├── commands.php
    └── schedules.php
```

## Creating a Module

### 1. Create Module Directory

```bash
mkdir -p modules/Blog/{Controllers,Models,Views,Database/Migrations,Providers}
```

### 2. Create Module Provider

**modules/Blog/Providers/BlogProvider.php:**

```php
<?php

namespace Modules\Blog\Providers;

use Lightpack\Modules\BaseModuleProvider;

class BlogProvider extends BaseModuleProvider
{
    protected string $modulePath = __DIR__ . '/..';
    protected string $namespace = 'blog';
}
```

That's it! The base class handles loading routes, events, commands, schedules, and views automatically.

### 3. Register Module

**boot/modules.php:**

```php
<?php

return [
    \Modules\Blog\Providers\BlogProvider::class,
];
```

### 4. Update Composer Autoloading

**composer.json:**

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Modules\\": "modules/"
        }
    }
}
```

Then run:

```bash
composer dump-autoload
```

## Module Components

### Routes

**modules/Blog/routes.php:**

```php
<?php

$route = app('route');

$route->group(['prefix' => 'blog'], function($route) {
    $route->get('/posts', \Modules\Blog\Controllers\PostController::class, 'index');
    $route->get('/posts/:id', \Modules\Blog\Controllers\PostController::class, 'show');
});
```

Routes are automatically loaded when the module provider is registered.

### Controllers

**modules/Blog/Controllers/PostController.php:**

```php
<?php

namespace Modules\Blog\Controllers;

use Modules\Blog\Models\Post;

class PostController
{
    public function index()
    {
        $posts = Post::query()->all();
        return template('blog::posts/index', ['posts' => $posts]);
    }
    
    public function show($id)
    {
        $post = new Post($id);
        return template('blog::posts/show', ['post' => $post]);
    }
}
```

### Views

**modules/Blog/Views/posts/index.php:**

```php
<h1>Blog Posts</h1>

<?php foreach($posts as $post): ?>
    <article>
        <h2><?= $post->title ?></h2>
        <p><?= $post->excerpt ?></p>
    </article>
<?php endforeach; ?>
```

**Rendering module views:**

```php
// From controller
template('blog::posts/index', $data);

// From another view
<?= $this->include('blog::widgets/sidebar') ?>
```

### Models

**modules/Blog/Models/Post.php:**

```php
<?php

namespace Modules\Blog\Models;

use Lightpack\Database\Lucid\Model;

class Post extends Model
{
    protected $table = 'posts';
    protected $primaryKey = 'id';
}
```

### Migrations

**modules/Blog/Database/Migrations/001_create_posts_table.php:**

```php
<?php

use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        $this->schema->create('posts', function($table) {
            $table->id();
            $table->varchar('title', 255);
            $table->text('content');
            $table->varchar('slug', 255)->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->schema->drop('posts');
    }
};
```

**Running migrations:**

```bash
php console migrate:up
```

Output:
```
Running migrations: app
  ✓ Already up-to-date

Running migrations: blog
  ✓ 001_create_posts_table.php
```

Module migrations are automatically discovered from `modules/*/Database/Migrations/`.

### Events

**modules/Blog/events.php:**

```php
<?php

return [
    'post.created' => [
        \Modules\Blog\Listeners\NotifySubscribers::class,
        \Modules\Blog\Listeners\IndexInSearch::class,
    ],
    'post.published' => [
        \Modules\Blog\Listeners\SendNotification::class,
    ],
];
```

### Commands

**modules/Blog/commands.php:**

```php
<?php

return [
    'blog:publish' => \Modules\Blog\Commands\PublishPostCommand::class,
    'blog:cleanup' => \Modules\Blog\Commands\CleanupDraftsCommand::class,
];
```

### Schedules

**modules/Blog/schedules.php:**

```php
<?php

$schedule = app('schedule');

$schedule->command('blog:publish-scheduled-posts')
    ->everyFiveMinutes();

$schedule->command('blog:cleanup-drafts')
    ->weekly()
    ->sundays()
    ->at('02:00');
```

## Advanced: Custom Services

If your module needs to register custom services, override the `registerServices()` method:

**modules/Blog/Providers/BlogProvider.php:**

```php
<?php

namespace Modules\Blog\Providers;

use Lightpack\Modules\BaseModuleProvider;
use Lightpack\Container\Container;
use Modules\Blog\Services\BlogService;

class BlogProvider extends BaseModuleProvider
{
    protected string $modulePath = __DIR__ . '/..';
    protected string $namespace = 'blog';
    
    protected function registerServices(Container $container): void
    {
        $container->register('blog.service', function($c) {
            return new BlogService($c->get('db'));
        });
        
        $container->register('blog.repository', function($c) {
            return new \Modules\Blog\Repositories\PostRepository($c->get('db'));
        });
    }
}
```

## Testing Modules

### Module Test Structure

```
modules/Blog/Tests/
├── Feature/
│   └── PostControllerTest.php
└── Unit/
    └── PostModelTest.php
```

### phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="App">
            <directory>tests</directory>
        </testsuite>
        
        <testsuite name="Blog">
            <directory>modules/Blog/Tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Running Tests

```bash
# All tests
vendor/bin/phpunit

# Only Blog module tests
vendor/bin/phpunit --testsuite=Blog
```

## Enabling/Disabling Modules

Simply add or remove the module provider from `boot/modules.php`:

```php
<?php

return [
    \Modules\Blog\Providers\BlogProvider::class,
    // \Modules\Shop\Providers\ShopProvider::class,  // Disabled
];
```

## Best Practices

1. **Keep modules independent** - Avoid tight coupling between modules
2. **Use route prefixes** - Prevent route conflicts (`/blog/posts` vs `/posts`)
3. **Namespace views** - Always use `module::view` syntax
4. **Document dependencies** - If a module requires another, document it
5. **Test in isolation** - Each module should have its own test suite

## Module Loading Order

Modules are loaded in the order they appear in `boot/modules.php`. If Module B depends on Module A, list Module A first:

```php
<?php

return [
    \Modules\Core\Providers\CoreProvider::class,     // Load first
    \Modules\Blog\Providers\BlogProvider::class,     // Depends on Core
];
```

## Complete Example

See the example Blog module structure in the framework repository for a complete working example.
