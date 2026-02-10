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

### **2. Generate Configuration**
```bash
php console create:config --support=pwa
```

Add VAPID keys to `.env`:
```env
PWA_VAPID_SUBJECT=mailto:admin@example.com
PWA_VAPID_PUBLIC_KEY=<your-public-key>
PWA_VAPID_PRIVATE_KEY=<your-private-key>
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
- `favicon.ico`

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
use Lightpack\Pwa\WebPush\Subscription;

// Create/update subscription
$subscription = Subscription::createOrUpdate([
    'endpoint' => $data['endpoint'],
    'keys' => [
        'p256dh' => $data['keys']['p256dh'],
        'auth' => $data['keys']['auth'],
    ],
    'user_id' => auth()->id(),
]);

// Get user's subscriptions
$subscriptions = Subscription::forUser($userId);

// Remove subscription
Subscription::removeByEndpoint($endpoint);
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

Configuration file: `config/pwa.php`

```php
return [
    'pwa' => [
        // Manifest settings
        'name' => get_env('APP_NAME', 'My App'),
        'short_name' => get_env('APP_NAME', 'App'),
        'theme_color' => '#4F46E5',
        'background_color' => '#ffffff',
        'display' => 'standalone',
        
        // Service worker
        'cache_name' => 'app-v1',
        'precache' => ['/css/app.css', '/js/app.js'],
        'runtime_cache' => [
            '/api/*' => 'network-first',
            '/img/*' => 'cache-first',
        ],
        
        // Push notifications
        'vapid_subject' => get_env('PWA_VAPID_SUBJECT'),
        'vapid_public_key' => get_env('PWA_VAPID_PUBLIC_KEY'),
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
├── Pwa.php                      # Main facade
├── ManifestGenerator.php        # Manifest generation
├── ServiceWorkerGenerator.php   # Service worker generation
├── IconGenerator.php            # Icon generation
├── PwaProvider.php              # Service provider
├── WebPush/
│   ├── WebPush.php             # Push notification sender
│   ├── VapidHelper.php         # VAPID key generation
│   └── Subscription.php        # Subscription model
└── assets/
    └── pwa.js                   # Frontend utilities
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
