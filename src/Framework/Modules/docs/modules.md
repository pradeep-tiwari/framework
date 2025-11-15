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
    ├── Filters/
    │   └── AuthFilter.php
    ├── Tests/
    │   ├── Feature/
    │   └── Unit/
    ├── Providers/
    │   └── BlogProvider.php
    ├── module.json
    ├── routes.php
    ├── events.php
    ├── commands.php
    ├── filters.php
    └── schedules.php
```

## Module Metadata (module.json)

Each module can optionally include a `module.json` file that provides metadata about the module. This file is automatically created when using `php console create:module`.

**modules/Blog/module.json:**

```json
{
    "name": "Blog",
    "display_name": "Blog Management",
    "description": "Manage blog posts, categories, and comments",
    "version": "1.0.0",
    "namespace": "blog",
    "author": "Your Name",
    "depends": []
}
```

### Metadata Fields

- **name** - Module identifier (matches directory name)
- **display_name** - Human-readable name for UI
- **description** - Brief description of module functionality
- **version** - Semantic version (e.g., "1.2.3")
- **namespace** - URL-safe namespace for routes/views
- **author** - Module author name/email
- **depends** - Array of required module names

### Use Cases

The `module.json` file enables:

- **Module discovery** - List available modules without loading PHP code
- **Dependency management** - Check requirements before installation
- **Version tracking** - Track installed versions and available updates
- **Module marketplace** - Display modules with metadata in admin UI
- **Documentation** - Standard way to describe module functionality

**Note:** The `module.json` file is **optional**. Modules work perfectly fine without it. It's primarily useful for:
- Building module management UIs
- Creating module marketplaces
- Third-party module distribution
- Automated dependency checking

## Creating a Module

### Quick Start with CLI

The fastest way to create a module is using the CLI command:

```bash
php console create:module Blog
```

This creates the complete module structure:
- `module.json` with metadata
- Provider with proper namespace
- Routes, events, commands, config, schedules, filters files
- Directory structure (Controllers, Models, Views, Database, Tests, etc.)
- Assets directories (css, js, img)

Then follow the instructions to:
1. Edit `module.json` to add description and metadata
2. Add provider to `boot/modules.php`
3. Update `composer.json` autoload (if not already done)
4. Run `composer dump-autoload`

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

That's it! The base class handles loading routes, events, commands, schedules, filters, and views automatically.

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
→ modules/Blog/Events/PostPublished.php

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

### Seeders

**modules/Blog/Database/Seeders/PostsSeeder.php:**

```php
<?php

namespace Modules\Blog\Database\Seeders;

class PostsSeeder
{
    public function seed()
    {
        $posts = [
            ['title' => 'First Post', 'content' => 'Content here...'],
            ['title' => 'Second Post', 'content' => 'More content...'],
        ];

        foreach ($posts as $post) {
            app('db')->table('posts')->insert($post);
        }
    }
}
```

**Running module seeders:**

```bash
# Run specific module seeder
php console seed PostsSeeder --module=Blog

# Run with force flag (no confirmation)
php console seed PostsSeeder --module=Blog --force

# Run app-level seeder (default)
php console seed DatabaseSeeder
```

### Filters

**modules/Blog/filters.php:**

```php
<?php

return [
    'blog.auth' => \Modules\Blog\Filters\AuthFilter::class,
    'blog.admin' => \Modules\Blog\Filters\AdminFilter::class,
];
```

Module filters are automatically merged with app-level filters during bootstrap and can be used in routes:

```php
// In modules/Blog/routes.php
$route->get('/admin/posts', [PostController::class, 'index'])->filter('blog.admin');
```

**Key Points:**
- Filter registry is built during bootstrap (before routes are matched)
- Module filters are merged with app filters in the container
- Use namespaced aliases (e.g., `blog.auth`) to avoid conflicts with app filters
- Filters must implement `Lightpack\Filters\IFilter`

### Events

**modules/Blog/events.php:**

```php
<?php

return [
    'post.created' => [
        \Modules\Blog\Events\NotifySubscribers::class,
        \Modules\Blog\Events\IndexInSearch::class,
    ],
    'post.published' => [
        \Modules\Blog\Events\SendNotification::class,
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
