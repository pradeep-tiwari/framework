<?php

require_once __DIR__ . '/../Database/tmp/mysql.config.php';

use Lightpack\Container\Container;
use Lightpack\Database\DB;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\ShortUrl\ShortUrl;
use PHPUnit\Framework\TestCase;

final class ShortUrlTest extends TestCase
{
    private ?DB $db;
    private Schema $schema;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $this->schema = new Schema($this->db);

        $this->createShortUrlsTable();

        $container = Container::getInstance();
        $container->register('db', fn () => $this->db);

        $container->register('logger', function () {
            return new class {
                public function error($message, $context = [])
                {
                }

                public function critical($message, $context = [])
                {
                }
            };
        });
    }

    protected function tearDown(): void
    {
        $sql = 'DROP TABLE IF EXISTS short_urls';
        $this->db->query($sql);
        $this->db = null;
    }

    private function createShortUrlsTable(): void
    {
        $this->schema->createTable('short_urls', function (Table $table) {
            $table->id();
            $table->varchar('code', 32)->unique();
            $table->text('url');
            $table->column('hits')->type('bigint')->attribute('unsigned')->default(0);
            $table->datetime('last_clicked_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('expires_at');
        });
    }

    public function testCanCreateShortUrl()
    {
        $shortUrl = new ShortUrl;
        $shortUrl->code = 'xK9mP';
        $shortUrl->url = 'https://example.com/products/123';
        $shortUrl->save();

        $this->assertNotNull($shortUrl->id);
        $this->assertEquals('xK9mP', $shortUrl->code);
        $this->assertEquals('https://example.com/products/123', $shortUrl->url);
    }

    public function testCanFindByCode()
    {
        $shortUrl = new ShortUrl;
        $shortUrl->code = 'abc123';
        $shortUrl->url = 'https://example.com';
        $shortUrl->save();

        $found = ShortUrl::query()->where('code', 'abc123')->one();

        $this->assertInstanceOf(ShortUrl::class, $found);
        $this->assertEquals('https://example.com', $found->url);
    }

    public function testIsExpiredReturnsFalseForNonExpiredUrl()
    {
        $shortUrl = new ShortUrl;
        $shortUrl->code = 'active1';
        $shortUrl->url = 'https://example.com';
        $shortUrl->expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));
        $shortUrl->save();

        $this->assertFalse($shortUrl->isExpired());
    }

    public function testIsExpiredReturnsTrueForExpiredUrl()
    {
        $shortUrl = new ShortUrl;
        $shortUrl->code = 'expired1';
        $shortUrl->url = 'https://example.com';
        $shortUrl->expires_at = date('Y-m-d H:i:s', strtotime('-1 day'));
        $shortUrl->save();

        $this->assertTrue($shortUrl->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenNoExpirationSet()
    {
        $shortUrl = new ShortUrl;
        $shortUrl->code = 'noexpire';
        $shortUrl->url = 'https://example.com';
        $shortUrl->save();

        $this->assertFalse($shortUrl->isExpired());
    }

    public function testRecordClickIncrementsHits()
    {
        $shortUrl = new ShortUrl;
        $shortUrl->code = 'track1';
        $shortUrl->url = 'https://example.com';
        $shortUrl->save();

        $this->assertEquals(0, $shortUrl->hits);

        $shortUrl->recordClick();

        $this->assertEquals(1, $shortUrl->hits);
        $this->assertNotNull($shortUrl->last_clicked_at);
    }

    public function testMultipleClicksIncrementHits()
    {
        $shortUrl = new ShortUrl;
        $shortUrl->code = 'multi1';
        $shortUrl->url = 'https://example.com';
        $shortUrl->save();

        $shortUrl->recordClick();
        $shortUrl->recordClick();
        $shortUrl->recordClick();

        $this->assertEquals(3, $shortUrl->hits);
    }

    public function testShortUrlMethodReturnsFullUrl()
    {
        $shortUrl = new ShortUrl;
        $shortUrl->code = 'test99';
        $shortUrl->url = 'https://example.com';
        $shortUrl->save();

        $url = $shortUrl->shortUrl();

        $this->assertStringContainsString('/s/test99', $url);
    }

    public function testCodeMustBeUnique()
    {
        $shortUrl1 = new ShortUrl;
        $shortUrl1->code = 'duplicate';
        $shortUrl1->url = 'https://example.com/1';
        $shortUrl1->save();

        $shortUrl2 = new ShortUrl;
        $shortUrl2->code = 'duplicate';
        $shortUrl2->url = 'https://example.com/2';

        $this->expectException(\PDOException::class);
        $shortUrl2->save();
    }

    public function testHelperFunctionReturnsModelInstance()
    {
        $model = short_url();
        $this->assertInstanceOf(ShortUrl::class, $model);
    }
}
