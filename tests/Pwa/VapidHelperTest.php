<?php

namespace Lightpack\Tests\Pwa;

use Lightpack\Pwa\WebPush\VapidHelper;
use PHPUnit\Framework\TestCase;

class VapidHelperTest extends TestCase
{
    public function testGenerateKeysReturnsPublicAndPrivateKey(): void
    {
        $keys = VapidHelper::generateKeys();

        $this->assertArrayHasKey('public_key', $keys);
        $this->assertArrayHasKey('private_key', $keys);
        $this->assertNotEmpty($keys['public_key']);
        $this->assertNotEmpty($keys['private_key']);
    }

    public function testPublicKeyIs65BytesUncompressedP256(): void
    {
        $keys = VapidHelper::generateKeys();

        $raw = base64_decode(strtr($keys['public_key'], '-_', '+/'));

        // Uncompressed P-256: 0x04 || x (32) || y (32) = 65 bytes
        $this->assertEquals(65, strlen($raw), 'Public key must be 65 bytes');
        $this->assertEquals("\x04", $raw[0], 'First byte must be 0x04 (uncompressed marker)');
    }

    public function testPrivateKeyIs32ByteEcScalar(): void
    {
        $keys = VapidHelper::generateKeys();

        // Pad with standard base64 padding before decoding
        $padded = $keys['private_key'] . str_repeat('=', (4 - strlen($keys['private_key']) % 4) % 4);
        $raw = base64_decode(strtr($padded, '-_', '+/'));

        // P-256 private key scalar: 32 bytes
        $this->assertLessThanOrEqual(32, strlen($raw), 'Private key must be at most 32 bytes');
        $this->assertGreaterThan(0, strlen($raw), 'Private key must not be empty');
    }

    public function testPrivateKeyIsSingleLineSafeForDotEnv(): void
    {
        $keys = VapidHelper::generateKeys();

        $this->assertStringNotContainsString("\n", $keys['private_key'], 'Private key must not contain newlines');
        $this->assertStringNotContainsString("\r", $keys['private_key'], 'Private key must not contain carriage returns');
        $this->assertStringNotContainsString('-----BEGIN', $keys['private_key'], 'Private key must not be PEM');
    }

    public function testPublicKeyIsValidBase64Url(): void
    {
        $keys = VapidHelper::generateKeys();

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9\-_]+$/',
            $keys['public_key'],
            'Public key must be base64url (no +, /, or = characters)'
        );
    }

    public function testFormatForEnvContainsAllRequiredLines(): void
    {
        $keys = VapidHelper::generateKeys();
        $env = VapidHelper::formatForEnv($keys);

        $this->assertStringContainsString('PWA_VAPID_SUBJECT=', $env);
        $this->assertStringContainsString('PWA_VAPID_PUBLIC_KEY=', $env);
        $this->assertStringContainsString('PWA_VAPID_PRIVATE_KEY=', $env);
        $this->assertStringContainsString($keys['public_key'], $env);
        $this->assertStringContainsString($keys['private_key'], $env);
    }

    public function testFormatForEnvPrivateKeyIsSingleLine(): void
    {
        $keys = VapidHelper::generateKeys();
        $env = VapidHelper::formatForEnv($keys);

        $lines = explode("\n", trim($env));
        $privateKeyLines = array_values(array_filter($lines, fn ($l) => str_starts_with(trim($l), 'PWA_VAPID_PRIVATE_KEY=')));

        $this->assertCount(1, $privateKeyLines, 'PWA_VAPID_PRIVATE_KEY must occupy exactly one line in .env');
    }
}
