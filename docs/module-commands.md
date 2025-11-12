# Module Commands

All Lightpack code generation commands now support the `--module` flag to create files within modules instead of the main app directory.

## Usage

```bash
php console <command> <name> --module=<ModuleName>
```

## Supported Commands

### Create Controller

```bash
# App controller
php console create:controller PostController

# Module controller
php console create:controller PostController --module=Blog

# Namespaced module controller
php console create:controller Admin\PostController --module=Blog
```

**Output:**
- App: `app/Controllers/PostController.php` with namespace `App\Controllers`
- Module: `modules/Blog/Controllers/PostController.php` with namespace `Modules\Blog\Controllers`

---

### Create Model

```bash
# App model
php console create:model Post --table=posts --key=id

# Module model
php console create:model Post --module=Blog --table=posts

# Namespaced module model
php console create:model Admin/Post --module=Blog
```

**Output:**
- App: `app/Models/Post.php` with namespace `App\Models`
- Module: `modules/Blog/Models/Post.php` with namespace `Modules\Blog\Models`

---

### Create Migration

```bash
# App migration
php console create:migration create_posts_table

# Module migration
php console create:migration create_posts_table --module=Blog

# With support schema
php console create:migration --support=users --module=Blog
```

**Output:**
- App: `database/migrations/20231112120000_create_posts_table.php`
- Module: `modules/Blog/Database/Migrations/20231112120000_create_posts_table.php`

---

### Create Event Listener

```bash
# App event
php console create:event NotifySubscribers

# Module event
php console create:event NotifySubscribers --module=Blog
```

**Output:**
- App: `app/Events/NotifySubscribers.php` with namespace `App\Events`
- Module: `modules/Blog/Listeners/NotifySubscribers.php` with namespace `Modules\Blog\Listeners`

---

### Create Command

```bash
# App command
php console create:command PublishPosts

# Module command
php console create:command PublishPosts --module=Blog
```

**Output:**
- App: `app/Commands/PublishPosts.php` with namespace `App\Commands`
- Module: `modules/Blog/Commands/PublishPosts.php` with namespace `Modules\Blog\Commands`

---

### Create Request

```bash
# App request
php console create:request StorePostRequest

# Module request
php console create:request StorePostRequest --module=Blog
```

**Output:**
- App: `app/Requests/StorePostRequest.php` with namespace `App\Requests`
- Module: `modules/Blog/Requests/StorePostRequest.php` with namespace `Modules\Blog\Requests`

---

### Create Transformer

```bash
# App transformer
php console create:transformer PostTransformer

# Module transformer
php console create:transformer PostTransformer --module=Blog
```

**Output:**
- App: `app/Transformers/PostTransformer.php` with namespace `App\Transformers`
- Module: `modules/Blog/Transformers/PostTransformer.php` with namespace `Modules\Blog\Transformers`

---

### Create Seeder

```bash
# App seeder
php console create:seeder PostsSeeder

# Module seeder
php console create:seeder PostsSeeder --module=Blog
```

**Output:**
- App: `database/seeders/PostsSeeder.php`
- Module: `modules/Blog/Database/Seeders/PostsSeeder.php`

---

### Create Filter

```bash
# App filter
php console create:filter AuthFilter

# Module filter
php console create:filter AuthFilter --module=Blog
```

**Output:**
- App: `app/Filters/AuthFilter.php` with namespace `App\Filters`
- Module: `modules/Blog/Filters/AuthFilter.php` with namespace `Modules\Blog\Filters`

---

### Create Job

```bash
# App job
php console create:job ProcessPost

# Module job
php console create:job ProcessPost --module=Blog
```

**Output:**
- App: `app/Jobs/ProcessPost.php` with namespace `App\Jobs`
- Module: `modules/Blog/Jobs/ProcessPost.php` with namespace `Modules\Blog\Jobs`

---

### Create Mail

```bash
# App mail
php console create:mail PostPublishedMail

# Module mail
php console create:mail PostPublishedMail --module=Blog
```

**Output:**
- App: `app/Mails/PostPublishedMail.php` with namespace `App\Mails`
- Module: `modules/Blog/Mails/PostPublishedMail.php` with namespace `Modules\Blog\Mails`

---

### Create Provider

```bash
# App provider
php console create:provider CacheProvider

# Module provider
php console create:provider CacheProvider --module=Blog
```

**Output:**
- App: `app/Providers/CacheProvider.php` with namespace `App\Providers`
- Module: `modules/Blog/Providers/CacheProvider.php` with namespace `Modules\Blog\Providers`

---

## Complete Workflow Example

```bash
# 1. Create module structure
php console create:module Blog

# 2. Add to boot/modules.php and composer.json, then:
composer dump-autoload

# 3. Create module components
php console create:controller PostController --module=Blog
php console create:model Post --module=Blog --table=posts
php console create:migration create_posts_table --module=Blog
php console create:event PostCreated --module=Blog
php console create:command PublishScheduledPosts --module=Blog

# 4. Run migrations
php console migrate:up

# 5. Publish assets (if any)
php console module:publish-assets Blog
```

## Directory Structure After Commands

```
modules/Blog/
├── Controllers/
│   └── PostController.php       ← create:controller --module=Blog
├── Models/
│   └── Post.php                 ← create:model --module=Blog
├── Database/
│   └── Migrations/
│       └── 20231112_create_posts_table.php  ← create:migration --module=Blog
├── Listeners/
│   └── PostCreated.php          ← create:event --module=Blog
├── Commands/
│   └── PublishScheduledPosts.php ← create:command --module=Blog
├── Views/
├── Config/
├── Assets/
├── Providers/
│   └── BlogProvider.php
├── routes.php
├── events.php
└── commands.php
```

## Benefits

1. **Organized Code** - All module code stays within the module directory
2. **Proper Namespacing** - Automatically uses `Modules\{Module}\{Type}` namespace
3. **Auto-Discovery** - Migrations, events, commands are auto-discovered
4. **Consistent Structure** - Same command patterns for app and modules
5. **No Manual Setup** - Directories created automatically

## Notes

- The `--module` flag works with all existing command options
- Directories are created automatically if they don't exist
- Namespaces are computed correctly for nested structures
- All commands maintain backward compatibility (work without `--module` flag)
