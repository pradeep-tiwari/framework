# Short URL

Self-hosted URL shortener built into Lightpack. No external services, no API keys.

## Setup

Create the database table:

```bash
php console create:migration --support=shorturls
php console migrate:up
```

Add the redirect route to `routes.php`:

```php
$route->get('/s/:code', \Lightpack\ShortUrl\ShortUrlController::class, 'redirect');
```

## Creating Short URLs

```php
$short = short_url();
$short->code = 'xK9mP';           // auto-generated if omitted
$short->url = 'https://example.com/products/123?utm_source=email';
$short->expires_at = '+7 days';   // optional
$short->save();

echo $short->shortUrl(); // https://yourapp.com/s/xK9mP
```

With a custom code:

```php
$short = short_url();
$short->code = 'summer-sale';
$short->url = 'https://example.com/sale';
$short->save();
```

## What You Get

- **Click tracking** — `hits` counter and `last_clicked_at` timestamp
- **Expiration** — optional `expires_at` with automatic pruning
- **Unique codes** — enforced at the database level
- **56.8 billion possible codes** — 6-character base62 alphabet
- **Zero configuration** — works out of the box after migration

## Pruning Expired URLs

```bash
# Preview what will be deleted
php console shorturl:prune --days=30

# Delete without confirmation
php console shorturl:prune --days=30 --force
```

## API

### ShortUrl Model

| Method | Returns | Description |
|---|---|---|
| `isExpired()` | `bool` | True if `expires_at` is in the past |
| `recordClick()` | `void` | Increment `hits` and set `last_clicked_at` |
| `shortUrl()` | `string` | Full short URL with APP_URL |

### Helper

```php
short_url(); // Returns new ShortUrl model instance
```

## Database Schema

```sql
short_urls
    id               bigint unsigned
    code             varchar(32) unique
    url              text
    hits             bigint unsigned default 0
    last_clicked_at  datetime nullable
    expires_at       datetime nullable
    created_at       datetime
    updated_at       datetime
```
