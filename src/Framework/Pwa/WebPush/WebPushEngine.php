<?php

namespace Lightpack\Pwa\WebPush;

/**
 * Low-level Web Push Protocol engine (RFC 8291).
 *
 * Handles message encryption (AES-128-GCM), VAPID JWT signing,
 * and HTTP delivery to browser push services.
 *
 * Application code should use {@see WebPush} instead — this class
 * is only for direct protocol control or testing.
 */
class WebPushEngine
{
    protected array $config;
    protected ?array $subscription = null;
    protected array $payload = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Set subscription to send to
     */
    public function to(array $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    /**
     * Set the full notification payload at once
     */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Send the encrypted push notification to the subscription endpoint.
     */
    public function send(): bool
    {
        if (! $this->subscription) {
            throw new \RuntimeException('No subscription set');
        }

        // Get subscription details
        $endpoint = $this->subscription['endpoint'];
        $userPublicKey = $this->subscription['keys']['p256dh'] ?? $this->subscription['p256dh'];
        $userAuth = $this->subscription['keys']['auth'] ?? $this->subscription['auth'];

        // Decode base64url keys
        $userPublicKey = $this->base64UrlDecode($userPublicKey);
        $userAuth = $this->base64UrlDecode($userAuth);

        // Encrypt payload
        $encrypted = $this->encryptPayload(
            json_encode($this->payload),
            $userPublicKey,
            $userAuth
        );

        // Generate VAPID headers
        $vapidHeaders = $this->generateVapidHeaders($endpoint);

        // Send HTTP request
        return $this->sendRequest($endpoint, $encrypted, $vapidHeaders);
    }

    /**
     * Encrypt payload using RFC 8291 (aes128gcm)
     * Based on: https://datatracker.ietf.org/doc/html/rfc8291
     */
    protected function encryptPayload(string $payload, string $userPublicKey, string $userAuth): array
    {
        // Generate ephemeral ECDH key pair
        $localKeyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        $localKeyDetails = openssl_pkey_get_details($localKeyPair);
        $localPublicKey = "\x04" . $localKeyDetails['ec']['x'] . $localKeyDetails['ec']['y'];

        // Compute ECDH shared secret
        $sharedSecret = $this->computeSharedSecret($localKeyPair, $userPublicKey);

        // Generate random salt (16 bytes)
        $salt = random_bytes(16);

        // RFC 8291 Key Derivation: IKM_PRK = HMAC-SHA-256(auth_secret, ecdh_secret)
        $prk = hash_hmac('sha256', $sharedSecret, $userAuth, true);

        // Derive IKM using key info context
        $keyInfo = "WebPush: info\x00" . $userPublicKey . $localPublicKey;
        $ikm = $this->hkdfExpand($prk, $keyInfo, 32);

        // Use salt to derive final PRK
        $context = "Content-Encoding: aes128gcm\x00";
        $prk2 = hash_hmac('sha256', $ikm, $salt, true);

        // Derive content encryption key
        $cek = $this->hkdfExpand($prk2, $context, 16);

        // Derive nonce
        $nonceContext = "Content-Encoding: nonce\x00";
        $nonce = $this->hkdfExpand($prk2, $nonceContext, 12);

        // Pad payload: payload + 0x02 delimiter
        $paddedPayload = $payload . "\x02";

        // Encrypt with AES-128-GCM
        $tag = '';
        $ciphertext = openssl_encrypt(
            $paddedPayload,
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16 // tag length
        );

        // RFC 8291 Section 2: HTTP Message Encryption
        // Format: salt(16) + rs(4) + idlen(1) + keyid(idlen) + ciphertext + tag(16)
        $recordSize = 4096;
        $rs = pack('N', $recordSize);
        $idlen = chr(strlen($localPublicKey));

        $encryptedData = $salt . $rs . $idlen . $localPublicKey . $ciphertext . $tag;

        return [
            'ciphertext' => $encryptedData,
            'salt' => base64_encode($salt),
            'publicKey' => $this->base64UrlEncode($localPublicKey),
        ];
    }

    /**
     * HKDF-Expand (RFC 5869)
     */
    protected function hkdfExpand(string $prk, string $info, int $length): string
    {
        $result = '';
        $t = '';
        $counter = 1;

        while (strlen($result) < $length) {
            $t = hash_hmac('sha256', $t . $info . chr($counter), $prk, true);
            $result .= $t;
            $counter++;
        }

        return substr($result, 0, $length);
    }

    /**
     * Compute ECDH shared secret
     */
    protected function computeSharedSecret($localPrivateKey, string $userPublicKey): string
    {
        // Create EC key from user's public key
        $pem = $this->publicKeyToPem($userPublicKey);
        $userKey = openssl_pkey_get_public($pem);

        // Compute shared secret
        openssl_pkey_export($localPrivateKey, $localPrivatePem);
        $localPrivateKeyResource = openssl_pkey_get_private($localPrivatePem);

        // Derive shared secret using ECDH
        $sharedSecret = openssl_pkey_derive($userKey, $localPrivateKeyResource);

        return $sharedSecret;
    }

    /**
     * HKDF (HMAC-based Key Derivation Function) - RFC 5869
     */
    protected function hkdf(string $salt, string $ikm, string $info, int $length): string
    {
        // Extract
        $prk = hash_hmac('sha256', $ikm, $salt, true);

        // Expand
        $t = '';
        $result = '';
        $counter = 1;

        while (strlen($result) < $length) {
            $t = hash_hmac('sha256', $t . $info . chr($counter), $prk, true);
            $result .= $t;
            $counter++;
        }

        return substr($result, 0, $length);
    }

    /**
     * Generate VAPID authorization headers
     */
    protected function generateVapidHeaders(string $endpoint): array
    {
        $parsedUrl = parse_url($endpoint);
        $audience = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        // JWT header
        $header = [
            'typ' => 'JWT',
            'alg' => 'ES256',
        ];

        // JWT payload
        $payload = [
            'aud' => $audience,
            'exp' => time() + 43200, // 12 hours
            'sub' => $this->config['vapid_subject'],
        ];

        // Encode header and payload
        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload));

        // Sign with private key
        $dataToSign = $encodedHeader . '.' . $encodedPayload;
        $privateKey = $this->loadPrivateKey();

        $signature = '';
        openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        // Convert DER signature to raw format (r + s, 64 bytes)
        $signature = $this->derToRaw($signature);

        $jwt = $dataToSign . '.' . $this->base64UrlEncode($signature);

        return [
            'Authorization' => 'vapid t=' . $jwt . ', k=' . $this->config['vapid_public_key'],
        ];
    }

    /**
     * Send HTTP request to push service
     */
    protected function sendRequest(string $endpoint, array $encrypted, array $vapidHeaders): bool
    {
        $ch = curl_init($endpoint);

        $headers = array_merge([
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'TTL: 2419200', // 28 days
        ], array_map(fn ($k, $v) => "$k: $v", array_keys($vapidHeaders), $vapidHeaders));

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $encrypted['ciphertext'],
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode === 410 || $httpCode === 404) {
            throw new \RuntimeException('Subscription expired or invalid', $httpCode);
        }

        if ($httpCode !== 201 && $httpCode !== 200) {
            throw new \RuntimeException("Push notification failed with HTTP {$httpCode}", $httpCode);
        }

        return true;
    }

    /**
     * Convert EC public key to PEM format
     */
    protected function publicKeyToPem(string $publicKey): string
    {
        // EC public key in X9.62 format
        $der = "\x30\x59" // SEQUENCE
            . "\x30\x13" // SEQUENCE
            . "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01" // OID: ecPublicKey
            . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07" // OID: prime256v1
            . "\x03\x42\x00" // BIT STRING
            . $publicKey;

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Convert DER signature to raw format (r + s)
     */
    protected function derToRaw(string $der): string
    {
        // Parse DER signature
        $offset = 0;

        // Skip SEQUENCE tag and length
        $offset += 2;

        // Read r
        $offset++; // INTEGER tag
        $rLength = ord($der[$offset++]);
        if ($rLength > 32) {
            $offset++; // Skip padding byte
            $rLength--;
        }
        $r = substr($der, $offset, $rLength);
        $offset += $rLength;

        // Pad r to 32 bytes
        $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);

        // Read s
        $offset++; // INTEGER tag
        $sLength = ord($der[$offset++]);
        if ($sLength > 32) {
            $offset++; // Skip padding byte
            $sLength--;
        }
        $s = substr($der, $offset, $sLength);

        // Pad s to 32 bytes
        $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    /**
     * Load VAPID private key
     *
     * Supports three formats:
     *   1. PEM string (-----BEGIN EC PRIVATE KEY-----)
     *   2. File path to a PEM file
     *   3. Raw base64url-encoded 32-byte EC scalar (output of VapidHelper::generateKeys())
     */
    protected function loadPrivateKey()
    {
        $privateKey = trim($this->config['vapid_private_key'], " \t\n\r\0\x0B\"'");

        // Format 1: Already PEM
        if (str_starts_with($privateKey, '-----BEGIN')) {
            $key = openssl_pkey_get_private($privateKey);
            if ($key === false) {
                throw new \RuntimeException('Invalid PEM private key: ' . openssl_error_string());
            }

            return $key;
        }

        // Format 2: File path
        $resolvedPath = str_starts_with($privateKey, '/')
            ? $privateKey
            : (defined('DIR_ROOT') ? DIR_ROOT . '/' . $privateKey : $privateKey);

        if (file_exists($resolvedPath)) {
            $pemContent = file_get_contents($resolvedPath);
            if ($pemContent === false) {
                throw new \RuntimeException('Failed to read private key file: ' . $resolvedPath);
            }
            $key = openssl_pkey_get_private($pemContent);
            if ($key === false) {
                throw new \RuntimeException('Invalid private key in file: ' . openssl_error_string());
            }

            return $key;
        }

        // Format 3: Raw base64url-encoded 32-byte P-256 scalar (from VapidHelper::generateKeys())
        $rawKey = base64_decode(strtr($privateKey, '-_', '+/'));
        if (strlen($rawKey) === 0 || strlen($rawKey) > 32) {
            throw new \RuntimeException(
                'Invalid VAPID private key: expected base64url-encoded 32-byte EC scalar, file path, or PEM string'
            );
        }
        // Left-pad to 32 bytes in case of leading zeros
        $rawKey = str_pad($rawKey, 32, "\x00", STR_PAD_LEFT);

        $pem = $this->rawKeyToPem($rawKey);
        $key = openssl_pkey_get_private($pem);
        if ($key === false) {
            throw new \RuntimeException('Failed to load VAPID EC private key: ' . openssl_error_string());
        }

        return $key;
    }

    /**
     * Convert a raw 32-byte P-256 private key scalar to PEM format (RFC 5915)
     */
    protected function rawKeyToPem(string $rawKey): string
    {
        // ECPrivateKey ::= SEQUENCE { version INTEGER 1, privateKey OCTET STRING, [0] OID prime256v1 }
        $version = "\x02\x01\x01";                                          // INTEGER 1
        $keyOctet = "\x04\x20" . $rawKey;                                     // OCTET STRING 32 bytes
        $curveOid = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";            // prime256v1 OID TLV
        $params = "\xa0\x0a" . $curveOid;                                   // [0] EXPLICIT

        $inner = $version . $keyOctet . $params;
        $der = "\x30" . chr(strlen($inner)) . $inner;

        return "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----\n";
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
}
