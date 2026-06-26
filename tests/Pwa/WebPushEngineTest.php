<?php

namespace Lightpack\Tests\Pwa;

use Lightpack\Pwa\WebPush\WebPushEngine;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class WebPushEngineTest extends TestCase
{
    private function makeEngine(array $config = []): WebPushEngine
    {
        return new WebPushEngine(array_merge([
            'vapid_subject' => 'mailto:test@example.com',
            'vapid_public_key' => 'BFakeKey',
            'vapid_private_key' => 'FakeKey',
        ], $config));
    }

    private function callMethod(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionClass($obj);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($obj, $args);
    }

    // -----------------------------------------------------------------------
    // base64url encode / decode round-trip
    // -----------------------------------------------------------------------

    public function testBase64UrlEncodeProducesUrlSafeString(): void
    {
        $engine = $this->makeEngine();
        $encoded = $this->callMethod($engine, 'base64UrlEncode', ["\xFF\xFE\x00\x01"]);

        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }

    public function testBase64UrlRoundTrip(): void
    {
        $engine = $this->makeEngine();
        $original = random_bytes(64);

        $encoded = $this->callMethod($engine, 'base64UrlEncode', [$original]);
        $decoded = $this->callMethod($engine, 'base64UrlDecode', [$encoded]);

        $this->assertEquals($original, $decoded);
    }

    // -----------------------------------------------------------------------
    // rawKeyToPem produces an OpenSSL-loadable EC key
    // -----------------------------------------------------------------------

    public function testRawKeyToPemProducesValidPem(): void
    {
        $engine = $this->makeEngine();

        // Generate a real 32-byte P-256 private scalar
        $keyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        $details = openssl_pkey_get_details($keyPair);
        $rawKey = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);

        $pem = $this->callMethod($engine, 'rawKeyToPem', [$rawKey]);

        $this->assertStringStartsWith('-----BEGIN EC PRIVATE KEY-----', $pem);
        $this->assertStringEndsWith("-----END EC PRIVATE KEY-----\n", $pem);

        $loaded = openssl_pkey_get_private($pem);
        $this->assertNotFalse($loaded, 'rawKeyToPem output must be loadable by OpenSSL');
    }

    // -----------------------------------------------------------------------
    // loadPrivateKey: Format 1 (PEM), Format 2 (file), Format 3 (base64url scalar)
    // -----------------------------------------------------------------------

    public function testLoadPrivateKeyAcceptsPemString(): void
    {
        $keyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        openssl_pkey_export($keyPair, $pem);

        $engine = $this->makeEngine(['vapid_private_key' => $pem]);
        $key = $this->callMethod($engine, 'loadPrivateKey');

        $this->assertNotFalse($key);
        $details = openssl_pkey_get_details($key);
        $this->assertEquals(OPENSSL_KEYTYPE_EC, $details['type']);
    }

    public function testLoadPrivateKeyAcceptsPemFile(): void
    {
        $keyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        openssl_pkey_export($keyPair, $pem);

        $path = tempnam(sys_get_temp_dir(), 'pwa_key_') . '.pem';
        file_put_contents($path, $pem);

        try {
            $engine = $this->makeEngine(['vapid_private_key' => $path]);
            $key = $this->callMethod($engine, 'loadPrivateKey');

            $this->assertNotFalse($key);
        } finally {
            unlink($path);
        }
    }

    public function testLoadPrivateKeyAcceptsBase64UrlScalar(): void
    {
        // Generate a real P-256 key and extract the 32-byte d scalar
        $keyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        $details = openssl_pkey_get_details($keyPair);
        $b64url = rtrim(strtr(base64_encode($details['ec']['d']), '+/', '-_'), '=');

        $engine = $this->makeEngine(['vapid_private_key' => $b64url]);
        $key = $this->callMethod($engine, 'loadPrivateKey');

        $this->assertNotFalse($key);
        $keyDetails = openssl_pkey_get_details($key);
        $this->assertEquals(OPENSSL_KEYTYPE_EC, $keyDetails['type']);
    }

    public function testLoadPrivateKeyThrowsForInvalidKey(): void
    {
        $this->expectException(\RuntimeException::class);

        // 50 base64url chars decodes to ~37 bytes (> 32-byte limit), triggering
        // the explicit "invalid VAPID private key" RuntimeException check
        $engine = $this->makeEngine(['vapid_private_key' => str_repeat('A', 50)]);
        $this->callMethod($engine, 'loadPrivateKey');
    }

    // -----------------------------------------------------------------------
    // derToRaw: DER-encoded EC signature → 64-byte raw r||s
    // -----------------------------------------------------------------------

    public function testDerToRawProduces64Bytes(): void
    {
        $engine = $this->makeEngine();

        // Generate a real DER signature
        $keyPair = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        $data = hash('sha256', 'test', true);
        openssl_sign($data, $derSig, $keyPair, OPENSSL_ALGO_SHA256);

        $raw = $this->callMethod($engine, 'derToRaw', [$derSig]);

        $this->assertEquals(64, strlen($raw), 'derToRaw must return exactly 64 bytes (r||s)');
    }

    // -----------------------------------------------------------------------
    // Fluent setter state
    // -----------------------------------------------------------------------

    public function testSetPayloadStoresPayload(): void
    {
        $engine = $this->makeEngine();
        $payload = ['title' => 'Hello', 'body' => 'World'];

        $engine->setPayload($payload);

        $ref = new ReflectionClass($engine);
        $prop = $ref->getProperty('payload');
        $prop->setAccessible(true);

        $this->assertEquals($payload, $prop->getValue($engine));
    }
}
