# Lightpack Module Examples

This directory contains example module structures to help you get started with Lightpack modules.

## Blog Module Example

The `modules/Blog` directory demonstrates a complete module structure with:

- **Provider**: `BlogProvider.php` - Bootstraps the module
- **Routes**: `routes.php` - Module-specific routes under `/blog` prefix
- **Controller**: `PostController.php` - Handles blog post requests
- **Model**: `Post.php` - Blog post model
- **Views**: Namespaced views using `blog::` prefix
- **Events**: Event listeners for blog-related events

## Quick Start

### 1. Copy the Example Module

```bash
# In your Lightpack application
cp -r vendor/lightpack/framework/docs/examples/modules ./
cp vendor/lightpack/framework/docs/examples/boot/modules.php ./boot/
```

### 2. Update Composer Autoloading

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

```bash
composer dump-autoload
```

### 3. Create the Database Table

Create migration: `database/migrations/001_create_posts_table.php`

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

Run migrations:
```bash
php console migrate:up
```

### 4. Test the Module

Visit: `http://your-app.test/blog/posts`

## Creating Your Own Module

See the full documentation in `docs/modules.md` for detailed instructions on creating custom modules.

## Module Structure Reference

```
modules/YourModule/
├── Controllers/          # Module controllers
├── Models/              # Module models
├── Views/               # Module views (namespaced)
├── Database/
│   └── Migrations/      # Module-specific migrations
├── Tests/               # Module tests
├── Providers/
│   └── YourModuleProvider.php
├── routes.php           # Module routes
├── events.php           # Module events
├── commands.php         # Module console commands
└── schedules.php        # Module scheduled tasks
```
