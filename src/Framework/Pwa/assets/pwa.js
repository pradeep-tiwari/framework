/**
 * PWA.js - Progressive Web App utilities for Lightpack
 * 
 * Provides client-side PWA functionality including:
 * - Install prompt management
 * - Push notification subscription
 * - Service worker communication
 * - Offline detection
 */

class PWA {
    constructor(config = {}) {
        this.swUrl = config.swUrl ?? '/sw.js';
        this.swScope = config.scope ?? '/';
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.isStandalone = false;
        this.registration = null;

        this.init();
    }

    /**
     * Initialize PWA
     */
    init() {
        // Check if running as installed PWA
        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                           window.navigator.standalone === true;
        
        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.onInstallable(e);
        });

        // Listen for app installed
        window.addEventListener('appinstalled', () => {
            this.isInstalled = true;
            this.deferredPrompt = null;
            this.onInstalled();
        });

        // Check if already installed
        if (this.isStandalone) {
            this.isInstalled = true;
        }

        // Register service worker
        if ('serviceWorker' in navigator) {
            this.registerServiceWorker();
        }

        // Listen for online/offline
        window.addEventListener('online', () => this.onOnline());
        window.addEventListener('offline', () => this.onOffline());
    }

    /**
     * Register service worker
     */
    async registerServiceWorker() {
        try {
            this.registration = await navigator.serviceWorker.register(this.swUrl, {
                scope: this.swScope,
            });

            // Listen for updates
            this.registration.addEventListener('updatefound', () => {
                const newWorker = this.registration.installing;
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        this.onUpdateAvailable();
                    }
                });
            });
        } catch (error) {
            this.onRegistrationError(error);
        }
    }

    /**
     * Show install prompt
     */
    async install() {
        if (!this.deferredPrompt) {
            return false;
        }

        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        this.deferredPrompt = null;
        return outcome === 'accepted';
    }

    /**
     * Subscribe to push notifications
     */
    async subscribePush(vapidPublicKey) {
        if (!this.registration) {
            throw new Error('Service worker not registered');
        }

        if (!('PushManager' in window)) {
            throw new Error('Push notifications not supported');
        }

        // Request notification permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Notification permission denied');
        }

        // Subscribe to push
        const subscription = await this.registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
        });

        return subscription.toJSON();
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribePush() {
        if (!this.registration) {
            throw new Error('Service worker not registered');
        }

        const subscription = await this.registration.pushManager.getSubscription();
        if (subscription) {
            await subscription.unsubscribe();
            return true;
        }

        return false;
    }

    /**
     * Check if subscribed to push
     */
    async isPushSubscribed() {
        if (!this.registration) {
            return false;
        }

        const subscription = await this.registration.pushManager.getSubscription();
        return subscription !== null;
    }

    /**
     * Get current push subscription
     */
    async getPushSubscription() {
        if (!this.registration) {
            return null;
        }

        const subscription = await this.registration.pushManager.getSubscription();
        return subscription ? subscription.toJSON() : null;
    }

    /**
     * Send message to service worker
     */
    sendMessage(message) {
        if (!this.registration || !this.registration.active) {
            return;
        }

        this.registration.active.postMessage(message);
    }

    /**
     * Update service worker
     */
    update() {
        if (!this.registration) {
            return;
        }

        this.registration.update();
    }

    /**
     * Skip waiting and activate new service worker
     */
    skipWaiting() {
        this.sendMessage({ type: 'SKIP_WAITING' });
        window.location.reload();
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.sendMessage({ type: 'CLEAR_CACHE' });
    }

    /**
     * Check if online
     */
    isOnline() {
        return navigator.onLine;
    }

    /**
     * Convert VAPID key to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    /**
     * Callbacks - override these in your app
     */
    onInstallable(event) {}

    onInstalled() {}

    onUpdateAvailable() {}

    onOnline() {}

    onOffline() {}

    onRegistrationError(error) {}
}

// Create global instance with optional config from window.PWAConfig
window.pwa = new PWA(window.PWAConfig ?? {});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PWA;
}
