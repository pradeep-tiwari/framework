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

        $details = openssl_pkey_get_details($keyPair);

        // EC public key: 0x04 (uncompressed marker) || x || y = 65 bytes
        $publicKey = "\x04" . $details['ec']['x'] . $details['ec']['y'];

        return [
            // base64url of 65-byte uncompressed P-256 public key
            'public_key'  => self::base64UrlEncode($publicKey),
            // base64url of 32-byte raw EC private scalar — single-line, safe to store in .env
            'private_key' => self::base64UrlEncode($details['ec']['d']),
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
        $date = date('Y-m-d H:i:s');
        $publicKey = $keys['public_key'];
        $privateKey = $keys['private_key'];

        return <<<ENV
# VAPID Keys for Web Push Notifications
# Generated: {$date}
PWA_VAPID_SUBJECT=mailto:your-email@example.com
PWA_VAPID_PUBLIC_KEY={$publicKey}
PWA_VAPID_PRIVATE_KEY="{$privateKey}"
ENV;
    }
}
