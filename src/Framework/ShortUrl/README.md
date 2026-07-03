# Short URL

Turn long URLs into short, shareable links that live on your own domain.

```php
$short = short_url('https://example.com/products/123?utm_source=email');

echo $short->shortUrl(); // https://yourapp.com/s/xK9mP
```

No API keys, no third-party services, no external dashboards.

## When to use it

- Share clean links in marketing emails, SMS, or social posts.
- Hide internal tracking parameters from end users.
- Count how many times a link is clicked and when it was last used.
- Temporarily disable a link without deleting it.
- Auto-expire campaign links after a date.

## Setup

Create the database table and add the redirect route:

```bash
php console create:migration --support=shorturls
php console migrate:up
```

```php
$route->get('/s/:code', \Lightpack\ShortUrl\ShortUrlController::class, 'redirect');
```

## Creating links

The helper is the fastest way to create a short link. Pass a URL and you get a saved, ready-to-use model back:

```php
$short = short_url('https://example.com/products/123');

echo $short->shortUrl();  // https://yourapp.com/s/xK9mP
echo $short->code;        // xK9mP
```

If you need more control, call the helper without arguments to get an empty model:

```php
$short = short_url();
$short->url = 'https://example.com';
$short->save();
```

### Custom short codes

Use a custom code when the link must be readable, memorable, or branded:

```php
$short = short_url('https://example.com/sale', ['code' => 'sale']);

// https://yourapp.com/s/sale
```

If the code is already taken, the helper throws an exception so you can handle it.

### Expiring links

Most links never expire. That is the default. When a campaign should end, pass an expiry as a relative string or a `DateTime`:

```php
$short = short_url('https://example.com/campaign', [
    'code' => 'summer',
    'expires_at' => '+7 days',
]);

// or later
$short->expiresIn('+7 days')->save();
```

After expiry, the short URL returns a 404.

### Disabling a link

Set `is_active` to false to stop the link from working without deleting it:

```php
$short->is_active = false;
$short->save();
```

Disabled links return 404.

### URL validation

Invalid URLs are rejected immediately:

```php
short_url('not-a-url'); // throws InvalidArgumentException
```

## Redirects and click tracking

The included controller handles redirects and records every click. Each redirect:

- Increments the `hits` counter.
- Updates `last_clicked_at` to the current time.
- Returns 404 if the link is disabled or expired.

You can also record clicks manually:

```php
$short->recordClick();
```

`hits` tells you total clicks. `last_clicked_at` tells you the most recent activity.

## Cleaning up expired links

```bash
# Preview what will be deleted
php console shorturl:prune --days=30

# Delete without confirmation
php console shorturl:prune --days=30 --force
```

## API reference

| Helper / method | Purpose |
|---|---|
| `short_url(?string $url, array $attributes)` | Create a short URL. With a URL, it saves and returns a hydrated model. Without a URL, it returns an empty model. |
| `$short->shortUrl()` | Full shareable URL, e.g. `https://yourapp.com/s/abc123`. |
| `$short->isActive()` | True if the link is enabled and not expired. |
| `$short->isExpired()` | True if the link has passed its expiry date. |
| `$short->recordClick()` | Increment the click counter and update `last_clicked_at`. |
| `$short->expiresIn(string $modifier)` | Fluent helper to set a relative expiry before saving. |

## Database schema

```sql
short_urls
    id               bigint unsigned
    code             varchar(32) unique
    url              text
    is_active        tinyint default 1
    hits             bigint unsigned default 0
    last_clicked_at  datetime nullable
    expires_at       datetime nullable
    created_at       datetime
    updated_at       datetime
```
