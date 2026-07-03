# Short URL

Turn long URLs into short, shareable links that live on your own domain.

```php
$short = short_url('https://example.com/products/123?utm_source=email');

echo $short->shortUrl(); // https://yourapp.com/s/xK9mP
```

No API keys, no third-party services, no analytics dashboards.

## When to use it

- Shorten long links for SMS, emails, or social posts where space matters.
- Use a readable, branded code instead of a random string.
- Create a temporary link that stops working after a date.
- Disable a link without deleting it.
- Hide long query strings from end users.

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

Pass a URL to get a saved, ready-to-use short link:

```php
$short = short_url('https://example.com/products/123');

echo $short->shortUrl();  // https://yourapp.com/s/xK9mP
echo $short->code;        // xK9mP
```

Or call the helper without a URL to build the model manually:

```php
$short = short_url();
$short->url = 'https://example.com';
$short->save();
```

### Custom short codes

Use a custom code for readable, memorable, or branded links:

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

After expiry, the short URL returns 404.

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

## Redirects

The included controller does one thing: look up the code and redirect. If the link is disabled or expired, it returns 404.

```php
$route->get('/s/:code', \Lightpack\ShortUrl\ShortUrlController::class, 'redirect');
```

There is no click tracking. The redirect is a single `SELECT` and an HTTP redirect.

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
| `$short->expiresIn(string $modifier)` | Fluent helper to set a relative expiry before saving. |

## Database schema

```sql
short_urls
    id               bigint unsigned
    code             varchar(32) unique
    url              text
    is_active        tinyint default 1
    expires_at       datetime nullable
    created_at       datetime
    updated_at       datetime
```
