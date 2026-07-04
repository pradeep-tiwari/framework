# ResourceQuery

`ResourceQuery` solves a specific, recurring problem in API development: every list endpoint in your application ends up writing the same boilerplate — reading filter parameters, applying sort conditions, eager loading relations, handling pagination, and shaping the response. The code works, but it is scattered across controllers and grows inconsistently over time.

`ResourceQuery` is a single, focused class that reads standardized HTTP query parameters from the current request and translates them into the appropriate ORM operations — all secured behind an explicit allowlist that you define per endpoint.

---

## The Idea in One Minute

Without `ResourceQuery`, a typical list endpoint looks like this:

```php
public function index()
{
    $query = User::query();

    if ($status = request()->query('status')) {
        $query->where('status', $status);
    }

    if ($sort = request()->query('sort')) {
        $query->orderBy($sort, 'ASC');
    }

    $pagination = $query->paginate();

    return response()->json($pagination->transform());
}
```

This works for two parameters. By the fifth it becomes tangled. `ResourceQuery` replaces the entire pattern:

```php
public function index()
{
    $rq = ResourceQuery::for(User::class)
        ->allowFilters(['status', 'role', 'search'])
        ->allowSorts(['name', 'email', 'created_at'])
        ->allowIncludes(['profile', 'posts'])
        ->allowFields(['name', 'email', 'created_at'])
        ->defaultSort('-created_at');

    $pagination = $rq->paginate();

    return response()->json($pagination->transform($rq->transformOptions()));
}
```

A client can now drive this endpoint entirely through the URL:

```
GET /api/users
    ?filter[status]=active
    &filter[role]=admin
    &sort=-created_at
    &include=profile,posts
    &fields=name,email
    &page=2
    &per_page=20
```

---

## Security Model: Everything Is Opt-In

This is the single most important principle of `ResourceQuery`. **No query parameter has any effect unless you explicitly allow it.**

If a client sends `?filter[password]=secret&sort=internal_score`, both parameters are silently ignored because neither appears in the allowlists you defined. There is no way for a client to filter on a column, sort by a column, load a relation, or select a field that you have not explicitly permitted.

This means you can safely expose `ResourceQuery` on any endpoint without fear that clients can probe your schema or access unintended data.

---

## Setting Up the Allowlists

### `allowFilters(array $filters)`

Declares which filter keys the client may send. Each key maps to a scope method on the model — the same `scope*()` methods used by `Model::filters()`.

```php
ResourceQuery::for(Post::class)
    ->allowFilters(['status', 'category', 'author_id', 'search']);
```

A client sends:

```
?filter[status]=published&filter[category]=news
```

The builder will call `$model->scopeStatus($builder, 'published')` and `$model->scopeCategory($builder, 'news')`. Any filter key not in the allowlist — or whose scope method does not exist on the model — is silently skipped.

The model defines the actual query logic:

```php
class Post extends Model
{
    protected function scopeStatus(Builder $query, string $value): void
    {
        $query->where('status', $value);
    }

    protected function scopeCategory(Builder $query, string $value): void
    {
        $query->where('category_slug', $value);
    }

    protected function scopeSearch(Builder $query, string $value): void
    {
        $query->where('title', 'LIKE', '%' . $value . '%');
    }
}
```

**Array-valued filters** are also supported natively. PHP parses `?filter[role][]=admin&filter[role][]=editor` into `['role' => ['admin', 'editor']]`, and `ResourceQuery` passes the array directly to your scope:

```php
protected function scopeRole(Builder $query, array|string $value): void
{
    $query->whereIn('role', (array) $value);
}
```

### `allowSorts(array $columns)`

Declares which columns the client may sort by. Unrecognised column names are ignored.

```php
ResourceQuery::for(Post::class)
    ->allowSorts(['title', 'published_at', 'view_count']);
```

**Sort format**: a single `sort` query parameter, comma-separated. Prefix a column name with `-` for descending order.

```
?sort=-published_at          → ORDER BY published_at DESC
?sort=title                  → ORDER BY title ASC
?sort=-published_at,title    → ORDER BY published_at DESC, title ASC
```

**Default sort**: when the client sends no `sort` parameter, apply a fallback:

```php
->defaultSort('-created_at')
```

The default is applied only when `?sort` is absent. A client-provided sort always wins.

### `allowIncludes(array $relations)`

Declares which relations the client may eager load. Dot-notation for nested relations is supported.

```php
ResourceQuery::for(Post::class)
    ->allowIncludes(['author', 'comments', 'comments.author', 'tags']);
```

```
?include=author,tags                    → with('author', 'tags')
?include=comments.author               → with('comments.author')
?include=author,comments,tags          → with('author', 'comments', 'tags')
```

Any relation not in the allowlist is silently dropped. This is important: a client cannot trigger loading of internal or sensitive relations.

**Default includes**: relations to always load regardless of the `?include` parameter:

```php
->defaultIncludes(['author'])
```

Request includes are merged with the defaults and deduplicated.

### `allowCounts(array $relations)`

Declares which relation row counts the client may request. Counts are loaded via efficient separate queries after the main result set is fetched — no joins, no subqueries in the main SQL.

```php
ResourceQuery::for(Article::class)
    ->allowCounts(['comments', 'likes', 'shares']);
```

```
?count=comments,likes   →   withCount(['comments', 'likes'])
```

Each counted relation adds a `relation_count` attribute to every model in the result:

```json
{
    "id": 1,
    "title": "Getting Started",
    "comments_count": 42,
    "likes_count": 108
}
```

The client cannot request counts for relations not in `allowedCounts`. Unrecognised names are silently dropped.

Note: counting and loading are independent. A client can request `?count=comments` (to know how many comments exist) without also requesting `?include=comments` (which would load all the comment rows). Use both together only when you need both the count and the actual data.

### `allowFields(array $fields)`

Declares which root-model fields the client may request in the response. This does not affect the SQL query; it controls which fields the transformer outputs.

```php
ResourceQuery::for(Post::class)
    ->allowFields(['title', 'excerpt', 'published_at', 'view_count']);
```

**Simple form** — applies to the root model:

```
?fields=title,excerpt,published_at
```

**Bracketed form** — use the model's table name to address the root model, and any relation name to address a relation:

```
?fields[posts]=title,excerpt
?fields[author]=name,avatar_url
```

Only root-model fields are validated against `allowedFields`. Relation fields are validated only against whether the relation itself is in `allowedIncludes` — the specific field names within a relation are not restricted. This is intentional: the transformer on each related model controls what its own data looks like.

---

## Executing the Query

### `paginate(?int $perPage = null): Pagination`

Executes the query and returns a `Pagination` object. The current page is read from `?page` in the request. The per-page count is resolved in this order:

1. Explicit argument passed to `paginate()`
2. `?per_page` query parameter
3. `?limit` query parameter (for backward compatibility)
4. Framework default of 15

The resolved value is capped at `maxPerPage` (default 100) to prevent clients from requesting arbitrarily large result sets.

```php
$pagination = $rq->paginate();          // reads ?per_page from request, capped at 100
$pagination = $rq->paginate(20);        // fixed at 20, capped at 100
```

```php
ResourceQuery::for(Post::class)
    ->maxPerPage(50)      // a client sending ?per_page=500 will get 50 results
    ->paginate();
```

### `all(): Collection`

Executes without pagination and returns a full `Collection`.

```php
$posts = ResourceQuery::for(Post::class)
    ->allowFilters(['status'])
    ->allowSorts(['published_at'])
    ->all();
```

Useful for small, bounded result sets (e.g., lookup lists) where pagination is unnecessary.

### `one(): ?Model`

Executes and returns a single matching model or `null`.

```php
$post = ResourceQuery::for(Post::class)
    ->allowFilters(['slug'])
    ->one();
```

### `getBuilder(): Builder`

Returns the fully configured `Builder` without executing. Use this when you need to add constraints beyond what `ResourceQuery` supports before executing:

```php
$builder = ResourceQuery::for(Post::class)
    ->allowFilters(['status'])
    ->allowSorts(['published_at'])
    ->getBuilder();

// Add your own constraints, then execute
$pagination = $builder
    ->where('author_id', auth()->id())
    ->paginate();
```

---

## Shaping the Response: `transformOptions()`

**Transformers are entirely optional.** `ResourceQuery` builds and executes the ORM query regardless of whether you have `Transformer` classes defined. `paginate()`, `all()`, `one()`, and `getBuilder()` have no transformer dependency at all.

If you are not using transformers, just serialise the result directly and ignore `transformOptions()`:

```php
$rq         = ResourceQuery::for(Article::class)->allowFilters(['status']);
$pagination = $rq->paginate();

return response()->json($pagination->toArray());   // no transformer involved
```

`transformOptions()` is only relevant when you have a `Transformer` class defined on your model and you want the client to control which fields or relations appear in the output. The method returns the `fields` and `includes` arrays parsed from the current request, ready to pass into `Pagination::transform()`, `Collection::transform()`, or `Model::transform()`.

```php
$rq         = ResourceQuery::for(Post::class)
    ->allowIncludes(['author', 'tags'])
    ->allowFields(['title', 'excerpt', 'published_at']);

$pagination = $rq->paginate();

return response()->json(
    $pagination->transform($rq->transformOptions())
);
```

`transformOptions()` never touches the database. It only parses the request parameters and validates them against the configured allowlists. You can call it before or after executing the query.

The returned array is passed directly to the existing transformer infrastructure:

```php
// What transformOptions() produces for:
// ?include=author&fields=title,excerpt&fields[author]=name,avatar_url

[
    'includes' => ['author'],
    'fields'   => [
        'self'   => ['title', 'excerpt'],
        'author' => ['name', 'avatar_url'],
    ],
]
```

When `transformOptions()` returns an empty array (no `?include` or `?fields` in the request), the transformer uses its full default output — the same result as calling `transform()` with no arguments.

---

## A Complete Controller Example

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Lightpack\Database\Lucid\ResourceQuery;

class PostController
{
    public function index()
    {
        $rq = ResourceQuery::for(Post::class)
            ->allowFilters(['status', 'category', 'search'])
            ->allowSorts(['title', 'published_at', 'view_count'])
            ->allowIncludes(['author', 'tags', 'comments'])
            ->allowFields(['title', 'excerpt', 'published_at', 'view_count'])
            ->defaultSort('-published_at')
            ->maxPerPage(50);

        $pagination = $rq->paginate();

        return response()->json(
            $pagination->transform($rq->transformOptions())
        );
    }
}
```

Sample response for `?filter[status]=published&include=author&fields=title,published_at&page=1&per_page=5`:

```json
{
    "data": [
        {
            "title": "Getting Started with Lightpack",
            "published_at": "2024-03-15",
            "author": {
                "name": "Jane Doe",
                "email": "jane@example.com"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 5,
        "total": 42,
        "total_pages": 9
    },
    "links": {
        "first": "/api/posts?page=1",
        "last": "/api/posts?page=9",
        "prev": null,
        "next": "/api/posts?page=2"
    }
}
```

---

## How It Connects to the ORM

`ResourceQuery` is not a separate query engine — it is a thin orchestrator over the ORM components Lightpack already provides.

| Query parameter | ORM operation |
|---|---|
| `?filter[key]=value` | Calls `scopeKey($builder, $value)` on the model |
| `?sort=-col,other` | `Builder::orderBy('col', 'DESC')`, `Builder::orderBy('other', 'ASC')` |
| `?include=a,b.c` | `Builder::with(['a', 'b.c'])` |
| `?count=a,b` | `Builder::withCount(['a', 'b'])` → adds `a_count`, `b_count` to each model |
| `?fields=name,email` | `Transformer::fields(['self' => ['name', 'email']])` (optional) |
| `?page=N&per_page=N` | `Builder::paginate($perPage)` which reads `?page` internally |

The scope method convention (`scope` prefix + camelCase key) is the same convention used by `Model::filters()`. If you already have scope methods defined on your models, they work with `ResourceQuery` with zero changes.

---

## Quick Reference

```php
ResourceQuery::for(ModelClass::class)

    // Allowlists — define what the client may control
    ->allowFilters(['key', ...])        // maps to scopeKey() methods
    ->allowSorts(['column', ...])       // validates sort column names
    ->allowIncludes(['relation', ...])  // validates ?include values
    ->allowCounts(['relation', ...])    // validates ?count values
    ->allowFields(['field', ...])       // validates root model ?fields (optional)

    // Server-side defaults
    ->defaultSort('-created_at')        // applied when ?sort is absent
    ->defaultIncludes(['relation'])     // always eager loaded
    ->maxPerPage(50)                    // caps ?per_page (default: 100)

    // Execution
    ->paginate(?int $perPage)           // returns Pagination
    ->paginateAndTransform(?int $perPage) // returns array (transformed pagination data)
    ->all()                             // returns Collection
    ->one()                           // returns Model|null
    ->getBuilder()                      // returns Builder for further chaining

    // Response shaping
    ->transformOptions()                // returns ['fields'=>..., 'includes'=>...]
```
