<?php

namespace Lightpack\Tests\Sms;

use Lightpack\Config\Config;
use Lightpack\Container\Container;
use Lightpack\Logger\Logger;
use Lightpack\Sms\SmsManager;
use PHPUnit\Framework\TestCase;

class SmsManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::getInstance()->reset();
    }

    protected function setupContainer(?string $provider): Container
    {
        $container = Container::getInstance();

        // Register a temp config dir with the desired sms.provider value
        $tmpDir = sys_get_temp_dir() . '/lp_sms_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $smsConfig = "<?php return ['sms' => ['provider' => " . var_export($provider, true) . "]];";
        file_put_contents($tmpDir . '/sms.php', $smsConfig);

        $container->register('config', function () use ($tmpDir) {
            return new Config($tmpDir);
        });

        $container->register('logger', function () {
            return $this->createMock(Logger::class);
        });

        return $container;
    }

    public function testDefaultDriverIsLogWhenProviderIsNull()
    {
        // Simulates: SMS_PROVIDER not set in .env → get_env returns null
        $container = $this->setupContainer(null);
        $manager = new SmsManager($container);

        $this->assertSame('log', $manager->getDefaultDriver());
    }

    public function testDefaultDriverIsLogWhenProviderNotInConfig()
    {
        // Simulates: no sms config file at all
        $tmpDir = sys_get_temp_dir() . '/lp_sms_test_' . uniqid();
        mkdir($tmpDir, 0777, true);

        $container = Container::getInstance();
        $container->register('config', function () use ($tmpDir) {
            return new Config($tmpDir);
        });
        $container->register('logger', function () {
            return $this->createMock(Logger::class);
        });

        $manager = new SmsManager($container);

        $this->assertSame('log', $manager->getDefaultDriver());
    }

    public function testDefaultDriverRespectsExplicitProvider()
    {
        $container = $this->setupContainer('twilio');
        $manager = new SmsManager($container);

        $this->assertSame('twilio', $manager->getDefaultDriver());
    }
}
