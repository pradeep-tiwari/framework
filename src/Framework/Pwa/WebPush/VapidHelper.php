<?php

namespace Lightpack\Pwa\WebPush;

/**
 * VapidHelper - Generate VAPID keys for Web Push
 * 
 * Generates VAPID (Voluntary Application Server Identification) keys
 * required for Web Push authentication.
 */
class VapidHelper
{
    /**
     * Generate VAPID key pair
     */
    public static function generateKeys(): array
    {
        // Generate EC key pair
        $keyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if ($keyPair === false) {
            throw new \RuntimeException('Failed to generate key pair');
        }

        // Export private key
        openssl_pkey_export($keyPair, $privateKey);

        // Get public key details
        $details = openssl_pkey_get_details($keyPair);
        // EC public key format: 0x04 (uncompressed) + x coordinate + y coordinate
        $publicKey = "\x04" . $details['ec']['x'] . $details['ec']['y'];

        return [
            'public_key' => self::base64UrlEncode($publicKey),
            'private_key' => $privateKey,
        ];
    }

    /**
     * Base64 URL encode
     */
    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Format keys for .env file
     */
    public static function formatForEnv(array $keys): string
    {
        return <<<ENV
# VAPID Keys for Web Push Notifications
# Generated: {date('Y-m-d H:i:s')}
PWA_VAPID_SUBJECT=mailto:your-email@example.com
PWA_VAPID_PUBLIC_KEY={$keys['public_key']}
PWA_VAPID_PRIVATE_KEY="{$keys['private_key']}"
ENV;
    }
}
