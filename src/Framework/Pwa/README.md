# PWA (Progressive Web App) Support for Lightpack

Complete PWA implementation for Lightpack framework with manifest generation, service workers, icon management, and Web Push notifications.

---

## 📦 **Features**

- ✅ **Manifest Generation** - Auto-generate `manifest.json` with smart defaults
- ✅ **Service Worker** - Customizable caching strategies and offline support
- ✅ **Icon Generator** - Auto-generate all required icon sizes from source image
- ✅ **Web Push Notifications** - Full VAPID authentication and push notification support
- ✅ **Offline Support** - Configurable caching and offline fallback pages
- ✅ **Console Commands** - Easy setup and management via CLI

---

## 🚀 **Quick Start**

### **1. Initialize PWA**
```bash
php console pwa:init
```

This creates:
- `public/manifest.json`
- `public/sw.js` (service worker)
- `public/offline.html`
- VAPID keys (displayed in terminal)

### **2. Add VAPID Keys to `.env`**

The `pwa:init` command prints the keys — copy them into your `.env`:
```env
PWA_VAPID_SUBJECT=mailto:admin@example.com
PWA_VAPID_PUBLIC_KEY=<your-public-key>
PWA_VAPID_PRIVATE_KEY=<your-private-key>
```

Create `config/pwa.php`:
```bash
php console create:config --support=pwa
```

### **3. Create Database Migration**
```bash
php console create:migration --support=pwa
php console migrate:up
```

### **4. Add to Your Layout**
```php
<!DOCTYPE html>
<html>
<head>
    <?= pwa()->meta() ?>
</head>
<body>
    <!-- Your app content -->
    
    <?= pwa()->register() ?>
    <script src="/path/to/pwa.js"></script>
</body>
</html>
```

**Done!** Your app is now a PWA.

---

## 📋 **Console Commands**

### **Initialize PWA**
```bash
php console pwa:init [options]

Options:
  --name=<name>              App name
  --short-name=<name>        Short name for home screen
  --theme-color=<color>      Theme color (hex)
  --icon=<path>              Path to source icon
  --description=<text>       App description
```

### **Generate VAPID Keys**
```bash
php console pwa:generate-vapid
```

### **Generate Icons**
```bash
php console pwa:generate-icons <source-image>
# Or
php console pwa:generate-icons --source=<path>
```

Generates:
- `icon-72x72.png` through `icon-512x512.png`
- `icon-512x512-maskable.png`
- `favicon.png`

---

## 💻 **PHP API**

### **Basic Usage**
```php
// Get PWA instance
$pwa = pwa();

// Generate manifest
$pwa->manifest([
    'name' => 'My App',
    'short_name' => 'App',
    'theme_color' => '#4F46E5',
]);

// Generate service worker
$pwa->serviceWorker([
    'cache_name' => 'my-app-v1',
    'precache' => ['/css/app.css', '/js/app.js'],
    'runtime_cache' => [
        '/api/*' => 'network-first',
        '/img/*' => 'cache-first',
    ],
]);

// Generate icons
$icons = $pwa->generateIcons('public/img/logo.png');

// Complete setup
$pwa->init([
    'manifest' => [...],
    'service_worker' => [...],
    'icon_source' => 'public/img/logo.png',
]);
```

### **Send Push Notifications**
```php
// Send to specific user
webpush()
    ->to($subscription)
    ->title('New Message')
    ->body('You have a new message')
    ->icon('/img/notification.png')
    ->requireInteraction(true)
    ->vibrate([200, 100, 200])
    ->send();

// Broadcast to all subscribers
webpush()->broadcast('System Update', [
    'body' => 'New features available',
    'icon' => '/img/icon.png',
    'data' => ['url' => '/updates'],
]);
```

### **Manage Subscriptions**
```php
use Lightpack\Pwa\WebPush\PwaSubscription;

// Create or update subscription (endpoint is the natural key)
$existing = PwaSubscription::query()->where('endpoint', $data['endpoint'])->one();
if ($existing) {
    $existing->p256dh = $data['keys']['p256dh'];
    $existing->auth = $data['keys']['auth'];
    $existing->user_id = auth()->id();
    $existing->save();
} else {
    $subscription = new PwaSubscription;
    $subscription->endpoint = $data['endpoint'];
    $subscription->p256dh = $data['keys']['p256dh'];
    $subscription->auth = $data['keys']['auth'];
    $subscription->user_id = auth()->id();
    $subscription->save();
}

// Get user's subscriptions
$subscriptions = PwaSubscription::query()->where('user_id', $userId)->all()->toArray();

// Remove subscription
PwaSubscription::query()->where('endpoint', $endpoint)->delete();
```

---

## 🎨 **JavaScript API**

### **Basic Usage**
```javascript
// PWA instance is available globally
const pwa = window.pwa;

// Check if installable
pwa.onInstallable = (event) => {
    document.getElementById('install-btn').style.display = 'block';
};

// Trigger install
document.getElementById('install-btn').onclick = () => {
    pwa.install();
};

// Check if installed
if (pwa.isInstalled) {
    console.log('App is installed');
}
```

### **Push Notifications**
```javascript
// Subscribe to push notifications
const subscription = await pwa.subscribePush(vapidPublicKey);

// Send subscription to backend
fetch('/api/pwa/subscribe', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(subscription)
});

// Check subscription status
const isSubscribed = await pwa.isPushSubscribed();

// Unsubscribe
await pwa.unsubscribePush();
```

### **Service Worker Communication**
```javascript
// Send message to service worker
pwa.sendMessage({ type: 'CUSTOM_ACTION', data: {...} });

// Update service worker
pwa.update();

// Skip waiting and activate new worker
pwa.skipWaiting();

// Clear cache
pwa.clearCache();
```

### **Callbacks**
```javascript
pwa.onInstallable = (event) => {
    console.log('App can be installed');
};

pwa.onInstalled = () => {
    console.log('App was installed');
};

pwa.onUpdateAvailable = () => {
    console.log('New version available');
};

pwa.onOnline = () => {
    console.log('Back online');
};

pwa.onOffline = () => {
    console.log('Connection lost');
};
```

---

## ⚙️ **Configuration**

Generate `config/pwa.php` via the console:
```bash
php console create:config --support=pwa
```

The generated file (keys are accessed via `config()->get('pwa.key')`):

```php
<?php

return [
    'pwa' => [
    // Manifest settings
    'name'             => get_env('APP_NAME', 'My App'),
    'short_name'       => get_env('APP_NAME', 'App'),
    'theme_color'      => '#4F46E5',
    'background_color' => '#ffffff',
    'display'          => 'standalone',
    'description'      => '',

    // Service worker
    'cache_name'    => 'app-v1',
    'sw_url'        => '/sw.js',   // URL the SW is served from
    'sw_scope'      => '/',        // SW scope
    'precache'      => ['/css/app.css', '/js/app.js'],
    'runtime_cache' => [
        '/api/*' => 'network-first',
        '/img/*' => 'cache-first',
    ],

    // Push notifications (VAPID)
    'vapid_subject'     => get_env('PWA_VAPID_SUBJECT'),
    'vapid_public_key'  => get_env('PWA_VAPID_PUBLIC_KEY'),
    'vapid_private_key' => get_env('PWA_VAPID_PRIVATE_KEY'),
    ],
];
```

---

## 🎯 **Caching Strategies**

### **Available Strategies**

1. **cache-first** - Serve from cache, fallback to network
   - Best for: Static assets, images
   
2. **network-first** - Try network, fallback to cache
   - Best for: API calls, dynamic content
   
3. **stale-while-revalidate** - Serve cache immediately, update in background
   - Best for: Frequently updated content
   
4. **network-only** - Always fetch from network
   - Best for: Real-time data
   
5. **cache-only** - Only serve from cache
   - Best for: Pre-cached resources

### **Example Configuration**
```php
'runtime_cache' => [
    '/api/*' => 'network-first',      // API calls
    '/img/*' => 'cache-first',        // Images
    '/css/*' => 'cache-first',        // Stylesheets
    '/js/*' => 'cache-first',         // JavaScript
    '/' => 'stale-while-revalidate',  // HTML pages
],
```

---

## 📱 **Push Notification Options**

```javascript
{
    title: 'Notification Title',
    body: 'Notification body text',
    icon: '/img/icon.png',
    badge: '/img/badge.png',
    image: '/img/hero.jpg',
    vibrate: [200, 100, 200],
    requireInteraction: true,
    silent: false,
    tag: 'unique-tag',
    data: { url: '/target-page' },
    actions: [
        { action: 'view', title: 'View' },
        { action: 'dismiss', title: 'Dismiss' }
    ]
}
```

---

## 🔒 **Security**

### **VAPID Keys**
- Keep private key secret
- Never commit to version control
- Store in `.env` file
- Rotate periodically

### **HTTPS Required**
- PWAs require HTTPS in production
- Service workers won't register on HTTP
- Exception: localhost for development

### **Subscription Management**
- Validate subscriptions server-side
- Remove expired subscriptions
- Handle unsubscribe requests

---

## 📈 **Scaling Push Notifications**

### **Performance Characteristics**

The native Web Push implementation is highly efficient:

- **Encryption**: OpenSSL (C extension) - 10,000+ encryptions/second
- **Algorithm**: AES-128-GCM with ECDH - minimal overhead
- **Dependencies**: Zero external libraries - no network latency
- **Infrastructure**: FCM handles delivery at Google scale

### **Current Capacity**

| Users | Strategy | Notes |
|-------|----------|-------|
| < 1,000 | Current implementation | Works great as-is |
| 1,000 - 10,000 | Add queue system | Async processing recommended |
| 10,000+ | Queue + batching + cleanup | Production-grade orchestration |

### **Scaling Strategies**

#### **1. Queue System (1,000+ users)**

Move notification sending to background jobs:

```php
// Controller - don't send immediately
Queue::push(new SendPushNotificationJob($userId, [
    'title' => 'New Message',
    'body' => 'You have a new message',
]));

// Job class
class SendPushNotificationJob
{
    public function handle()
    {
        $subscriptions = PwaSubscription::query()->where('user_id', $this->userId)->all()->toArray();
        $subscription = $subscriptions[0] ?? null;
        if (!$subscription) return;
        
        webpush()
            ->to($subscription)
            ->title($this->data['title'])
            ->body($this->data['body'])
            ->send();
    }
}
```

#### **2. Batch Processing**

Send to multiple users efficiently:

```php
// Send to all subscribers in batches
$subscriptions = PwaSubscription::query()->all()->toArray();

foreach (array_chunk($subscriptions, 100) as $batch) {
    Queue::push(new SendBatchNotificationJob($batch, $payload));
}

// Batch job
class SendBatchNotificationJob
{
    public function handle()
    {
        foreach ($this->subscriptions as $subscription) {
            try {
                webpush()
                    ->to($subscription)
                    ->title($this->payload['title'])
                    ->body($this->payload['body'])
                    ->send();
            } catch (\Exception $e) {
                logger()->error('Push notification failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
```

#### **3. Error Handling & Cleanup**

Handle failures and remove invalid subscriptions:

```php
// In WebPushEngine::sendRequest() or custom wrapper
protected function sendWithCleanup($subscription, $payload)
{
    $result = $this->send($subscription, $payload);
    
    // Handle HTTP status codes
    switch ($result['status']) {
        case 201:
            // Success - do nothing
            break;
            
        case 404:
        case 410:
            // Subscription expired or invalid - remove it
            $subscription->delete();
            logger()->info('Removed expired subscription', [
                'endpoint' => $subscription->endpoint
            ]);
            break;
            
        case 429:
            // Rate limited - retry later
            Queue::later(60, new SendPushNotificationJob($subscription, $payload));
            break;
            
        case 500:
        case 502:
        case 503:
            // Server error - retry with backoff
            $this->retryWithBackoff($subscription, $payload);
            break;
            
        default:
            logger()->error('Push notification failed', [
                'status' => $result['status'],
                'response' => $result['response'],
            ]);
    }
    
    return $result;
}
```

#### **4. Rate Limiting**

Respect FCM rate limits to avoid 429 errors.

**FCM Quotas** ([Official Documentation](https://firebase.google.com/docs/cloud-messaging/throttling-and-quotas)):
- **Default quota**: 600,000 messages/minute (10,000/second)
- **Per-device (Android)**: 240 messages/minute, 5,000/hour
- **Collapsible messages**: 20 burst, refills 1 every 3 minutes
- **429 Response**: `RESOURCE_EXHAUSTED` when quota exceeded

```php
// Rate limiter for safe sending
class PushRateLimiter
{
    protected $maxPerSecond = 1000; // Conservative (can go up to 10k)
    protected $sent = 0;
    protected $lastReset;
    
    public function throttle()
    {
        if (time() > $this->lastReset) {
            $this->sent = 0;
            $this->lastReset = time();
        }
        
        if ($this->sent >= $this->maxPerSecond) {
            usleep(1000000); // Wait 1 second
            $this->sent = 0;
            $this->lastReset = time();
        }
        
        $this->sent++;
    }
}

// Usage in batch job
$rateLimiter = new PushRateLimiter();

foreach ($subscriptions as $subscription) {
    $rateLimiter->throttle();
    webpush()->to($subscription)->send();
}
```

**Note**: The default 600k/minute quota covers 99% of apps. You'll only hit limits if:
- Sending to same device repeatedly (240/min limit)
- Using collapsible messages excessively
- Experiencing FCM overload situations

#### **5. Monitoring & Metrics**

Track notification performance:

```php
// Track metrics
class PushMetrics
{
    public static function track($event, $data = [])
    {
        // Log to database or metrics service
        DB::table('push_metrics')->insert([
            'event' => $event,
            'data' => json_encode($data),
            'created_at' => now(),
        ]);
    }
}

// Usage
PushMetrics::track('notification_sent', [
    'user_id' => $userId,
    'status' => $httpCode,
    'duration_ms' => $duration,
]);

PushMetrics::track('subscription_expired', [
    'endpoint' => $endpoint,
]);
```

### **Production Checklist**

- [ ] Queue system configured (Redis/Database)
- [ ] Batch processing implemented
- [ ] Error handling and retry logic
- [ ] Expired subscription cleanup
- [ ] Rate limiting configured
- [ ] Monitoring and alerting
- [ ] VAPID keys secured in environment
- [ ] HTTPS enabled in production
- [ ] Database indexes on subscriptions table
- [ ] Logging configured for debugging

### **Database Optimization**

Add indexes for better performance:

```sql
-- Index on endpoint for quick lookups
CREATE INDEX idx_pwa_subscriptions_endpoint ON pwa_subscriptions(endpoint);

-- Index on user_id for user-specific queries
CREATE INDEX idx_pwa_subscriptions_user_id ON pwa_subscriptions(user_id);

-- Index on created_at for cleanup queries
CREATE INDEX idx_pwa_subscriptions_created_at ON pwa_subscriptions(created_at);
```

### **Recommended Architecture for Scale**

```
┌─────────────┐
│   Web App   │
└──────┬──────┘
       │
       ▼
┌─────────────┐     ┌──────────────┐
│   Queue     │────▶│  Worker Pool │
└─────────────┘     └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ WebPushEngine│
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │     FCM      │
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │   Browsers   │
                    └──────────────┘
```

---

## 🧪 **Testing**

### **Test PWA Features**
```bash
# Chrome DevTools
1. Open DevTools → Application tab
2. Check Manifest
3. Check Service Workers
4. Test offline mode
5. Test push notifications

# Lighthouse
1. Run Lighthouse audit
2. Check PWA score
3. Fix any issues
```

### **Test Push Notifications**
```javascript
// Request permission
const permission = await Notification.requestPermission();

// Test notification
new Notification('Test', {
    body: 'This is a test notification'
});
```

---

## 📚 **Architecture**

```
src/Framework/Pwa/
├── Pwa.php                       # Main facade
├── ManifestGenerator.php         # manifest.json generation
├── ServiceWorkerGenerator.php    # sw.js generation
├── IconGenerator.php             # Icon resizing (GD)
├── PwaProvider.php               # DI service provider
├── Commands/
│   ├── PwaInit.php               # php console pwa:init
│   ├── PwaGenerateVapid.php      # php console pwa:generate-vapid
│   └── PwaGenerateIcons.php      # php console pwa:generate-icons
├── Controllers/
│   └── PwaController.php         # Subscribe/unsubscribe endpoints
├── Migrations/
│   └── PwaView.php               # create:migration --support=pwa
├── WebPush/
│   ├── WebPush.php               # High-level push sender
│   ├── WebPushEngine.php         # RFC 8291 encryption + HTTP
│   ├── VapidHelper.php           # VAPID key generation
│   └── PwaSubscription.php       # Subscription model
└── assets/
    └── pwa.js                    # Frontend PWA utilities
```

---

## 🔌 **Built-in Subscription Routes (Opt-in)**

`PwaController` ships ready to use. Register the routes anywhere in your app:

```php
// routes/web.php
$router->post('/pwa/subscribe',   [PwaController::class, 'subscribe']);
$router->post('/pwa/unsubscribe', [PwaController::class, 'unsubscribe']);
$router->get('/pwa/status',       [PwaController::class, 'status']);
```

The controller expects a JSON body matching the Web Push API subscription object:
```json
{
    "endpoint": "https://fcm.googleapis.com/...",
    "keys": {
        "p256dh": "<base64url public key>",
        "auth": "<base64url auth secret>"
    }
}
```

Send the subscription from JavaScript after calling `pwa.subscribePush(vapidPublicKey)`:
```javascript
const subscription = await pwa.subscribePush(vapidPublicKey);

await fetch('/pwa/subscribe', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(subscription),
});
```

---

## 🐛 **Troubleshooting**

### **Service Worker Not Registering**
- Check HTTPS (required in production)
- Verify `sw.js` is in public root
- Check browser console for errors

### **Push Notifications Not Working**
- Verify VAPID keys are set
- Check notification permissions
- Ensure subscription is saved to database
- Test with browser DevTools

### **Icons Not Showing**
- Verify icons exist in `/public/icons/`
- Check manifest.json paths
- Clear browser cache

### **Offline Mode Not Working**
- Check service worker is active
- Verify precache files exist
- Check caching strategies
- Test in DevTools offline mode

---
