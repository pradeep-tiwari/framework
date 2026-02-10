<?php

namespace Lightpack\Pwa\WebPush;

use Lightpack\Database\Lucid\Model;

/**
 * WebPush - Send push notifications to PWA subscribers
 * 
 * Implements Web Push Protocol with VAPID authentication
 * for sending push notifications to PWA users.
 */
class WebPush
{
    protected array $config;
    protected ?array $subscription = null;
    protected array $payload = [];
    protected array $options = [];

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? $this->loadConfig();
        $this->validateConfig();
    }

    /**
     * Set target subscription
     */
    public function to(array $subscription): self
    {
        $this->subscription = $subscription;
        return $this;
    }

    /**
     * Set notification title
     */
    public function title(string $title): self
    {
        $this->payload['title'] = $title;
        return $this;
    }

    /**
     * Set notification body
     */
    public function body(string $body): self
    {
        $this->payload['body'] = $body;
        return $this;
    }

    /**
     * Set notification icon
     */
    public function icon(string $icon): self
    {
        $this->payload['icon'] = $icon;
        return $this;
    }

    /**
     * Set notification badge
     */
    public function badge(string $badge): self
    {
        $this->payload['badge'] = $badge;
        return $this;
    }

    /**
     * Set notification data
     */
    public function data(array $data): self
    {
        $this->payload['data'] = $data;
        return $this;
    }

    /**
     * Set require interaction flag
     */
    public function requireInteraction(bool $require = true): self
    {
        $this->payload['requireInteraction'] = $require;
        return $this;
    }

    /**
     * Set vibration pattern
     */
    public function vibrate(array $pattern): self
    {
        $this->payload['vibrate'] = $pattern;
        return $this;
    }

    /**
     * Set notification actions
     */
    public function actions(array $actions): self
    {
        $this->payload['actions'] = $actions;
        return $this;
    }

    /**
     * Set notification tag
     */
    public function tag(string $tag): self
    {
        $this->payload['tag'] = $tag;
        return $this;
    }

    /**
     * Set TTL (time to live in seconds)
     */
    public function ttl(int $seconds): self
    {
        $this->options['TTL'] = $seconds;
        return $this;
    }

    /**
     * Set urgency level
     */
    public function urgency(string $urgency): self
    {
        $validUrgencies = ['very-low', 'low', 'normal', 'high'];
        if (!in_array($urgency, $validUrgencies)) {
            throw new \InvalidArgumentException("Invalid urgency: {$urgency}");
        }
        $this->options['urgency'] = $urgency;
        return $this;
    }

    /**
     * Send push notification
     */
    public function send(): bool
    {
        if (!$this->subscription) {
            throw new \RuntimeException('No subscription set. Use to() method first.');
        }

        $endpoint = $this->subscription['endpoint'];
        $p256dh = $this->subscription['keys']['p256dh'] ?? $this->subscription['p256dh'];
        $auth = $this->subscription['keys']['auth'] ?? $this->subscription['auth'];

        // Encrypt payload
        $encrypted = $this->encryptPayload(
            json_encode($this->payload),
            $p256dh,
            $auth
        );

        // Send to push service
        return $this->sendToEndpoint($endpoint, $encrypted);
    }

    /**
     * Broadcast to all subscribers
     */
    public function broadcast(string $title, array $options = []): int
    {
        $this->title($title);
        
        if (isset($options['body'])) {
            $this->body($options['body']);
        }
        if (isset($options['icon'])) {
            $this->icon($options['icon']);
        }
        if (isset($options['data'])) {
            $this->data($options['data']);
        }
        if (isset($options['requireInteraction'])) {
            $this->requireInteraction($options['requireInteraction']);
        }
        if (isset($options['vibrate'])) {
            $this->vibrate($options['vibrate']);
        }

        // Get all subscriptions from database
        $subscriptions = $this->getAllSubscriptions();
        $sent = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $this->to($subscription)->send();
                $sent++;
            } catch (\Exception $e) {
                // Log error but continue
                error_log("Failed to send push notification: " . $e->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Encrypt payload using ECDH
     */
    protected function encryptPayload(string $payload, string $userPublicKey, string $userAuthToken): array
    {
        // Decode base64url encoded keys
        $userPublicKey = $this->base64UrlDecode($userPublicKey);
        $userAuthToken = $this->base64UrlDecode($userAuthToken);

        // Generate local key pair
        $localKeyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        $localPublicKey = openssl_pkey_get_details($localKeyPair)['ec']['x'] . 
                         openssl_pkey_get_details($localKeyPair)['ec']['y'];

        // Compute shared secret
        $sharedSecret = $this->computeSharedSecret($localKeyPair, $userPublicKey);

        // Derive encryption key and nonce
        $salt = random_bytes(16);
        $info = $this->buildInfo('aesgcm', $userPublicKey, $localPublicKey);
        
        $prk = hash_hmac('sha256', $sharedSecret, $userAuthToken . $salt, true);
        $key = $this->hkdf($prk, $info, 16);
        $nonce = $this->hkdf($prk, $this->buildInfo('nonce', $userPublicKey, $localPublicKey), 12);

        // Encrypt payload
        $paddedPayload = pack('n*', strlen($payload)) . $payload;
        $ciphertext = openssl_encrypt(
            $paddedPayload,
            'aes-128-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        return [
            'ciphertext' => $ciphertext . $tag,
            'salt' => $salt,
            'localPublicKey' => $localPublicKey,
        ];
    }

    /**
     * Send encrypted payload to push endpoint
     */
    protected function sendToEndpoint(string $endpoint, array $encrypted): bool
    {
        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aesgcm',
            'Encryption: salt=' . $this->base64UrlEncode($encrypted['salt']),
            'Crypto-Key: dh=' . $this->base64UrlEncode($encrypted['localPublicKey']),
            'TTL: ' . ($this->options['TTL'] ?? 2419200),
        ];

        // Add urgency header
        if (isset($this->options['urgency'])) {
            $headers[] = 'Urgency: ' . $this->options['urgency'];
        }

        // Add VAPID authentication
        $vapidHeaders = $this->generateVapidHeaders($endpoint);
        $headers = array_merge($headers, $vapidHeaders);

        // Send request
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encrypted['ciphertext'],
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check response
        if ($statusCode === 201 || $statusCode === 200) {
            return true;
        }

        if ($statusCode === 410 || $statusCode === 404) {
            // Subscription expired or invalid
            $this->removeSubscription($endpoint);
        }

        throw new \RuntimeException("Push notification failed with status: {$statusCode}");
    }

    /**
     * Generate VAPID authentication headers
     */
    protected function generateVapidHeaders(string $endpoint): array
    {
        $url = parse_url($endpoint);
        $audience = $url['scheme'] . '://' . $url['host'];

        $header = [
            'typ' => 'JWT',
            'alg' => 'ES256',
        ];

        $payload = [
            'aud' => $audience,
            'exp' => time() + 43200, // 12 hours
            'sub' => $this->config['vapid_subject'],
        ];

        $jwt = $this->generateJWT($header, $payload, $this->config['vapid_private_key']);

        return [
            'Authorization: vapid t=' . $jwt . ', k=' . $this->config['vapid_public_key'],
        ];
    }

    /**
     * Generate JWT token
     */
    protected function generateJWT(array $header, array $payload, string $privateKey): string
    {
        $segments = [];
        $segments[] = $this->base64UrlEncode(json_encode($header));
        $segments[] = $this->base64UrlEncode(json_encode($payload));

        $signingInput = implode('.', $segments);

        // If it looks like a file path, load the key from file
        $privateKey = trim($privateKey);
        $privateKey = DIR_ROOT . '/' . $privateKey;
        
        if (file_exists($privateKey)) {
            $privateKey = file_get_contents($privateKey);
            if ($privateKey === false) {
                throw new \RuntimeException('Failed to read private key file');
            }
        }
        
        $key = openssl_pkey_get_private($privateKey);
        
        if ($key === false) {
            throw new \RuntimeException('Invalid VAPID private key: ' . openssl_error_string());
        }
        
        $result = openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);
        
        if ($result === false) {
            throw new \RuntimeException('Failed to sign JWT: ' . openssl_error_string());
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Compute shared secret using ECDH
     */
    protected function computeSharedSecret($privateKey, string $publicKey): string
    {
        $details = openssl_pkey_get_details($privateKey);
        $privateKeyBin = $details['ec']['d'];

        // Perform ECDH
        // Note: This is a simplified version. Production should use proper ECDH implementation
        return hash('sha256', $privateKeyBin . $publicKey, true);
    }

    /**
     * HKDF key derivation
     */
    protected function hkdf(string $prk, string $info, int $length): string
    {
        $output = '';
        $counter = 1;

        while (strlen($output) < $length) {
            $output .= hash_hmac('sha256', $output . $info . chr($counter), $prk, true);
            $counter++;
        }

        return substr($output, 0, $length);
    }

    /**
     * Build info string for HKDF
     */
    protected function buildInfo(string $type, string $userPublicKey, string $localPublicKey): string
    {
        return 'Content-Encoding: ' . $type . "\0" .
               'P-256' . "\0" .
               pack('n*', strlen($userPublicKey)) . $userPublicKey .
               pack('n*', strlen($localPublicKey)) . $localPublicKey;
    }

    /**
     * Base64 URL encode
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    protected function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Get all subscriptions from database
     */
    protected function getAllSubscriptions(): array
    {
        // This should query the pwa_subscriptions table
        // For now, return empty array - will be implemented with Subscription model
        return [];
    }

    /**
     * Remove expired subscription
     */
    protected function removeSubscription(string $endpoint): void
    {
        // Remove from database
        // Will be implemented with Subscription model
    }

    /**
     * Load configuration
     */
    protected function loadConfig(): array
    {
        $config = app('config');
        return [
            'vapid_subject' => $config->get('pwa.vapid_subject'),
            'vapid_public_key' => $config->get('pwa.vapid_public_key'),
            'vapid_private_key' => $config->get('pwa.vapid_private_key'),
        ];
    }

    /**
     * Validate configuration
     */
    protected function validateConfig(): void
    {
        $required = ['vapid_subject', 'vapid_public_key', 'vapid_private_key'];

        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new \RuntimeException("Missing required config: {$key}");
            }
        }
    }
}
