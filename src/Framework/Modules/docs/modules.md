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

### Quick Start with CLI

The fastest way to create a module is using the CLI command:

```bash
php console create:module Blog
```

This creates the complete module structure:
- Provider with proper namespace
- Routes, events, commands files
- Directory structure (Controllers, Models, Views, etc.)
- Config and Assets directories

Then follow the instructions to:
1. Add provider to `boot/modules.php`
2. Update `composer.json` autoload
3. Run `composer dump-autoload`

### Manual Creation

If you prefer to create modules manually:

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

## CLI Commands for Modules

All Lightpack code generation commands support the `--module` flag to create files within modules.

### Creating Module Components

```bash
# Controllers
php console create:controller PostController --module=Blog
→ modules/Blog/Controllers/PostController.php

# Models
php console create:model Post --module=Blog --table=posts
→ modules/Blog/Models/Post.php

# Migrations
php console create:migration create_posts_table --module=Blog
→ modules/Blog/Database/Migrations/20231112_create_posts_table.php

# Events
php console create:event PostPublished --module=Blog
→ modules/Blog/Listeners/PostPublished.php

# Commands
php console create:command PublishPosts --module=Blog
→ modules/Blog/Commands/PublishPosts.php

# Requests
php console create:request StorePostRequest --module=Blog
→ modules/Blog/Requests/StorePostRequest.php

# Transformers
php console create:transformer PostTransformer --module=Blog
→ modules/Blog/Transformers/PostTransformer.php

# Seeders
php console create:seeder PostsSeeder --module=Blog
→ modules/Blog/Database/Seeders/PostsSeeder.php

# Filters
php console create:filter AuthFilter --module=Blog
→ modules/Blog/Filters/AuthFilter.php

# Jobs
php console create:job ProcessPost --module=Blog
→ modules/Blog/Jobs/ProcessPost.php

# Mails
php console create:mail PostPublishedMail --module=Blog
→ modules/Blog/Mails/PostPublishedMail.php

# Providers
php console create:provider CacheProvider --module=Blog
→ modules/Blog/Providers/CacheProvider.php
```

**All commands:**
- Automatically create directories if they don't exist
- Use proper `Modules\{Module}\{Type}` namespace
- Support nested paths (e.g., `Admin/PostController`)
- Work without `--module` flag for app-level files

### Publishing Module Assets

```bash
php console module:publish-assets Blog
```

Copies assets from `modules/Blog/Assets/` to `public/modules/blog/`.

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

**Migration Behavior:**

Migrations from all sources (app + modules) run in **chronological order** based on timestamp:

```bash
# Example with multiple modules
database/migrations/
  20231101_create_users_table.php
  20231102_create_roles_table.php

modules/Blog/Database/Migrations/
  20231103_create_posts_table.php

modules/Shop/Database/Migrations/
  20231104_create_products_table.php

# Running migrate:up
php console migrate:up

Running migrations: app
  ✓ 20231101_create_users_table
  ✓ 20231102_create_roles_table

Running migrations: blog
  ✓ 20231103_create_posts_table

Running migrations: shop
  ✓ 20231104_create_products_table

# All 4 migrations are in batch 1
```

**Rollback:**

Rollback happens in **reverse order** (LIFO - Last In, First Out):

```bash
# Rollback last batch
php console migrate:down

Rolled back migrations:
  ✓ 20231104_create_products_table  (last in, first out)
  ✓ 20231103_create_posts_table
  ✓ 20231102_create_roles_table
  ✓ 20231101_create_users_table

# Rollback last N batches
php console migrate:down --steps=2

# Rollback all migrations
php console migrate:down --all
```

**Important:**
- Use proper timestamps to ensure correct migration order
- Consider foreign key dependencies when creating migrations
- If a module is removed, its migrations in DB will be skipped with a warning during rollback

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

### Config

**modules/Blog/Config/blog.php:**

```php
<?php

return [
    'posts_per_page' => 10,
    'allow_comments' => true,
    'cache_duration' => 3600,
];
```

**Accessing module config:**

```php
// In controller
$perPage = app('config')->get('modules.blog.blog.posts_per_page');

// Or
$config = app('config')->get('modules.blog.blog');
$perPage = $config['posts_per_page'];
```

Config files are automatically loaded from `modules/{Module}/Config/` and stored under `modules.{namespace}.{filename}`.

### Assets

Modules can have CSS, JavaScript, images, and other assets in the `Assets` directory.

**modules/Blog/Assets/css/blog.css:**

```css
.blog-posts {
    max-width: 800px;
    margin: 0 auto;
}
```

**Publishing assets:**

```bash
php console module:publish-assets Blog
```

This copies assets from `modules/Blog/Assets/` to `public/modules/blog/`.

**Using published assets in views:**

```php
<link rel="stylesheet" href="/modules/blog/css/blog.css">
<script src="/modules/blog/js/blog.js"></script>
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
