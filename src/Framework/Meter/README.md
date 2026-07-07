# Meter

A lightweight usage and quota tracker for Lightpack. Track cumulative counters with optional reset periods for allowances, credits, and billing-aware limits.

## Meter vs Limiter

Use **Meter** when you need to count usage over time and enforce a quota. Use **Limiter** when you need to throttle requests in a sliding time window.

| Feature | Meter | Limiter |
|---------|-------|---------|
| Purpose | Usage quotas and credits | Rate throttling |
| Counter | Cumulative, can be reset | Hits within a TTL window |
| Periods | hourly, daily, weekly, monthly, yearly | TTL-based windows |
| Consume | Atomic `consume($amount, $limit)` | `attempt()` until blocked |
| Events | `meter.exceeded`, `meter.reset` | No events |

## Quick Start

```php
use function Lightpack\Meter\meter;

$meter = meter('api_calls');
$meter->hit();

if ($meter->available(100)) {
    $meter->consume(1, 100);
}
```

## Configuration

Create a config file:

```bash
php lucent create:config meter
```

`config/meter.php`:

```php
return [
    'meter' => [
        'driver' => get_env('METER_DRIVER', 'database'),
        'database' => [
            'table' => 'meters',
        ],
    ],
];
```

Available drivers: `database`, `memory`.

## Database Migration

```bash
php lucent create:migration meter
php lucent migrate:up
```

Creates the `meters` table with `key`, `value`, and timestamps.

## API

### hit($amount = 1)

Increment the meter by the given amount.

```php
meter('emails')->hit();
meter('emails')->hit(5);
```

### value()

Get the current meter value.

```php
$used = meter('emails')->value();
```

### remaining($limit)

Get remaining allowance against a limit.

```php
$remaining = meter('emails')->remaining(100);
```

### available($limit)

Check if the meter is below the limit.

```php
if (meter('emails')->available(100)) {
    // send email
}
```

### consume($amount, $limit)

Increment the meter only if the resulting value stays within the limit. Returns `true` on success, `false` if the limit would be exceeded. Fires a `meter.exceeded` event on failure.

```php
if (meter('emails')->consume(1, 100)) {
    // send email
} else {
    // quota exceeded
}
```

### reset()

Reset the meter value to zero. Fires a `meter.reset` event.

```php
meter('emails')->reset();
```

## Periods

Track usage within automatic reset windows:

```php
meter('api_calls', 'hourly')->hit();
meter('api_calls', 'daily')->hit();
meter('api_calls', 'weekly')->hit();
meter('api_calls', 'monthly')->hit();
meter('api_calls', 'yearly')->hit();
```

Each period uses a different storage key, so counters are isolated by window.

## Events

Subscribe to meter events in your event provider:

```php
$event->subscribe('meter.exceeded', App\Events\NotifyQuotaExceeded::class);
$event->subscribe('meter.reset', App\Events\LogMeterReset::class);
```

### MeterExceeded

```php
public readonly string $name;
public readonly ?string $period;
public readonly string $key;
public readonly int $limit;
public readonly int $attempted;
public readonly int $current;
```

### MeterReset

```php
public readonly string $name;
public readonly ?string $period;
public readonly string $key;
```

## MeterFilter

Enforce meter quotas on HTTP routes:

```php
$route->get('/api/endpoint', [ApiController::class, 'index'])
    ->filter('meter:api_calls,100');
```

The filter resolves the meter key by authenticated user (`user:{id}`) or IP address (`ip:{ip}`). When the quota is exceeded, it throws a `TooManyRequestsException` with a `429` status and these headers:

- `X-MeterLimit-Limit`
- `X-MeterLimit-Remaining: 0`

## Console Command

Check a meter value from the command line:

```bash
php lucent meter:status api_calls --limit=100
php lucent meter:status api_calls --limit=100 --reset
```

## Drivers

### Database Driver

Stores counters in a database table. Supports concurrent increments via `INSERT ... ON DUPLICATE KEY UPDATE`.

### Memory Driver

In-memory driver. Useful for testing or single-request counting. Counters are lost at the end of the request.

## Manual Driver Selection

```php
$manager = app('meter.manager');
$meter = new \Lightpack\Meter\Meter($manager->driver('memory'), 'api_calls');
```

## Testing

The Meter test suite covers the core meter API, both drivers, and the HTTP filter:

```bash
./vendor/bin/phpunit --testsuite Meter
```
