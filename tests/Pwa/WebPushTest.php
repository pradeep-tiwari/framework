<?php

namespace Lightpack\Tests\Pwa;

use Lightpack\Pwa\WebPush\WebPush;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class WebPushTest extends TestCase
{
    private function config(): array
    {
        return [
            'vapid_subject' => 'mailto:test@example.com',
            'vapid_public_key' => 'BFakePublicKeyForTesting',
            'vapid_private_key' => 'FakePrivateKeyForTesting',
        ];
    }

    private function webPush(): WebPush
    {
        return new WebPush($this->config());
    }

    private function getPayload(WebPush $webPush): array
    {
        $ref = new ReflectionClass($webPush);
        $prop = $ref->getProperty('payload');
        $prop->setAccessible(true);

        return $prop->getValue($webPush);
    }

    private function getSubscription(WebPush $webPush): ?array
    {
        $ref = new ReflectionClass($webPush);
        $prop = $ref->getProperty('subscription');
        $prop->setAccessible(true);

        return $prop->getValue($webPush);
    }

    // -----------------------------------------------------------------------
    // Fluent API builds correct payload
    // -----------------------------------------------------------------------

    public function testTitleSetsPayloadTitle(): void
    {
        $webPush = $this->webPush()->title('Hello');
        $this->assertEquals('Hello', $this->getPayload($webPush)['title']);
    }

    public function testBodySetsPayloadBody(): void
    {
        $webPush = $this->webPush()->title('T')->body('World');
        $this->assertEquals('World', $this->getPayload($webPush)['body']);
    }

    public function testIconSetsPayloadIcon(): void
    {
        $webPush = $this->webPush()->title('T')->icon('/icon.png');
        $this->assertEquals('/icon.png', $this->getPayload($webPush)['icon']);
    }

    public function testBadgeSetsPayloadBadge(): void
    {
        $webPush = $this->webPush()->title('T')->badge('/badge.png');
        $this->assertEquals('/badge.png', $this->getPayload($webPush)['badge']);
    }

    public function testDataSetsPayloadData(): void
    {
        $webPush = $this->webPush()->title('T')->data(['url' => '/page']);
        $this->assertEquals(['url' => '/page'], $this->getPayload($webPush)['data']);
    }

    public function testRequireInteractionSetsFlag(): void
    {
        $webPush = $this->webPush()->title('T')->requireInteraction(true);
        $this->assertTrue($this->getPayload($webPush)['requireInteraction']);
    }

    public function testVibrateSetsPattern(): void
    {
        $webPush = $this->webPush()->title('T')->vibrate([200, 100, 200]);
        $this->assertEquals([200, 100, 200], $this->getPayload($webPush)['vibrate']);
    }

    public function testTagSetsTag(): void
    {
        $webPush = $this->webPush()->title('T')->tag('my-tag');
        $this->assertEquals('my-tag', $this->getPayload($webPush)['tag']);
    }

    public function testActionsSetsActions(): void
    {
        $actions = [['action' => 'view', 'title' => 'View']];
        $webPush = $this->webPush()->title('T')->actions($actions);
        $this->assertEquals($actions, $this->getPayload($webPush)['actions']);
    }

    public function testToSetsSubscription(): void
    {
        $sub = ['endpoint' => 'https://fcm.example.com', 'keys' => ['p256dh' => 'abc', 'auth' => 'xyz']];
        $webPush = $this->webPush()->to($sub);
        $this->assertEquals($sub, $this->getSubscription($webPush));
    }

    public function testChainedCallsReturnSelf(): void
    {
        $webPush = $this->webPush();
        $result = $webPush->title('T')->body('B')->icon('/i.png');
        $this->assertSame($webPush, $result);
    }

    // -----------------------------------------------------------------------
    // send() throws when no subscription set
    // -----------------------------------------------------------------------

    public function testSendThrowsWhenNoSubscription(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/subscription/i');

        $this->webPush()->title('T')->send();
    }

    // -----------------------------------------------------------------------
    // broadcast() resets state before building payload
    // -----------------------------------------------------------------------

    public function testBroadcastResetsStalePayload(): void
    {
        $webPush = $this->webPush();

        // Set stale icon from a previous call
        $webPush->title('Old')->icon('/old.png');

        // broadcast() resets state before fetching subscriptions (which needs DB).
        // Catch the DB exception — the important state reset already happened.
        try {
            $webPush->broadcast('New Title', []);
        } catch (\Throwable $e) {
            // Expected in test context: no DB container registered
        }

        $payload = $this->getPayload($webPush);
        $this->assertEquals('New Title', $payload['title']);
        $this->assertArrayNotHasKey('icon', $payload);
    }

    public function testBroadcastResetsStaleSubscription(): void
    {
        $webPush = $this->webPush();

        $sub = ['endpoint' => 'https://fcm.example.com', 'keys' => ['p256dh' => 'a', 'auth' => 'b']];
        $webPush->to($sub)->title('T');

        try {
            $webPush->broadcast('New', []);
        } catch (\Throwable $e) {
            // Expected in test context: no DB container registered
        }

        $this->assertNull($this->getSubscription($webPush));
    }

    public function testBroadcastBuildsOptionsIntoPayload(): void
    {
        $webPush = $this->webPush();

        try {
            $webPush->broadcast('Alert', [
                'body' => 'Something happened',
                'icon' => '/alert.png',
            ]);
        } catch (\Throwable $e) {
            // Expected in test context: no DB container registered
        }

        $payload = $this->getPayload($webPush);
        $this->assertEquals('Alert', $payload['title']);
        $this->assertEquals('Something happened', $payload['body']);
        $this->assertEquals('/alert.png', $payload['icon']);
    }

    // -----------------------------------------------------------------------
    // Config validation
    // -----------------------------------------------------------------------

    public function testConstructorThrowsWhenVapidSubjectMissing(): void
    {
        $this->expectException(\RuntimeException::class);

        new WebPush([
            'vapid_subject' => '',
            'vapid_public_key' => 'key',
            'vapid_private_key' => 'key',
        ]);
    }

    public function testConstructorThrowsWhenVapidPublicKeyMissing(): void
    {
        $this->expectException(\RuntimeException::class);

        new WebPush([
            'vapid_subject' => 'mailto:test@example.com',
            'vapid_public_key' => '',
            'vapid_private_key' => 'key',
        ]);
    }
}
