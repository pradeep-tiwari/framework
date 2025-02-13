<?php

namespace Lightpack\Tests\Faker;

use Lightpack\Faker\Faker;
use PHPUnit\Framework\TestCase;

class FakerTest extends TestCase
{
    private Faker $faker;

    protected function setUp(): void
    {
        $this->faker = new Faker();
    }

    public function testSeedProducesSameResults()
    {
        $this->faker->seed(123);
        $first = $this->faker->firstName();
        $last = $this->faker->lastName();
        
        $this->faker->seed(123);
        $this->assertEquals($first, $this->faker->firstName());
        $this->assertEquals($last, $this->faker->lastName());
    }

    public function testUniqueValuesAreNeverRepeated()
    {
        $this->faker->unique();
        $names = [];
        
        // Get 5 unique names
        for ($i = 0; $i < 5; $i++) {
            $names[] = $this->faker->firstName();
        }
        
        $this->assertEquals(count($names), count(array_unique($names)));
    }

    public function testPersonData()
    {
        // Username format
        $this->assertMatchesRegularExpression('/^[a-z]+_[0-9]+$/', $this->faker->username());
        
        // Password complexity
        $password = $this->faker->password();
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
        $this->assertMatchesRegularExpression('/[!@#$%^&*]/', $password);
        
        // Phone format
        $this->assertMatchesRegularExpression('/^\+1-[0-9]{3}-[0-9]{3}-[0-9]{4}$/', $this->faker->phone());
    }

    public function testWebData()
    {
        // URL format
        $this->assertMatchesRegularExpression('/^https:\/\/[a-z\.]+\/[a-z0-9\-]+$/', $this->faker->url());
        
        // IPv4 format
        $this->assertMatchesRegularExpression('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $this->faker->ipv4());
        
        // Slug format
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $this->faker->slug());
    }

    public function testBusinessData()
    {
        // Address has all components
        $address = $this->faker->address();
        $this->assertStringContainsString(',', $address);
        $this->assertNotEmpty($address);
    }

    public function testContentGeneration()
    {
        $faker = new Faker();

        // Test plain text output
        $this->assertIsString($faker->article());
        $this->assertIsString($faker->text());
        $this->assertIsString($faker->title());
        $this->assertIsString($faker->intro());
        $this->assertIsString($faker->conclusion());
        $this->assertIsString($faker->markdown());

        // Test HTML output
        $article = $faker->article('html');
        $this->assertStringContainsString('<article>', $article);
        $this->assertStringContainsString('<h1>', $article);
        $this->assertStringContainsString('<div class="intro">', $article);
        $this->assertStringContainsString('<div class="body">', $article);
        $this->assertStringContainsString('<div class="conclusion">', $article);
        $this->assertStringContainsString('</article>', $article);

        $text = $faker->text('html');
        $this->assertStringStartsWith('<p>', $text);
        $this->assertStringEndsWith('</p>', $text);

        $title = $faker->title('html');
        $this->assertStringStartsWith('<h1>', $title);
        $this->assertStringEndsWith('</h1>', $title);

        $intro = $faker->intro('html');
        $this->assertStringStartsWith('<p class="intro">', $intro);
        $this->assertStringEndsWith('</p>', $intro);

        $conclusion = $faker->conclusion('html');
        $this->assertStringStartsWith('<p class="conclusion">', $conclusion);
        $this->assertStringEndsWith('</p>', $conclusion);

        $html = $faker->html();
        $this->assertStringContainsString('<article class="content">', $html);
        $this->assertStringContainsString('<div class="content-body">', $html);
        $this->assertStringContainsString('<h2>Overview</h2>', $html);
        $this->assertStringContainsString('<h2>Details</h2>', $html);
        $this->assertStringContainsString('<h2>Summary</h2>', $html);
    }

    public function testTextGeneration()
    {
        $faker = new Faker();
        
        // Test that text content is non-empty
        $this->assertNotEmpty($faker->text());
        $this->assertNotEmpty($faker->title());
        $this->assertNotEmpty($faker->intro());
        $this->assertNotEmpty($faker->conclusion());
        
        // Test that markdown has proper structure
        $markdown = $faker->markdown();
        $this->assertStringContainsString('# ', $markdown);
        $this->assertStringContainsString('## ', $markdown);
        
        // Test that HTML has proper structure
        $html = $faker->html();
        $this->assertStringContainsString('<article', $html);
        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringContainsString('<h2>', $html);
        $this->assertStringContainsString('</article>', $html);
    }

    public function testIdsAndCodes()
    {
        // UUID format
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $this->faker->uuid()
        );
        
        // Hash length
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $this->faker->hash());
        
        // Token is base64
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\/\+=]+$/', $this->faker->token());
    }

    public function testDates()
    {
        // Past date is in the past
        $pastDate = strtotime($this->faker->pastDate());
        $this->assertLessThan(time(), $pastDate);
        
        // Future date is in the future
        $futureDate = strtotime($this->faker->futureDate());
        $this->assertGreaterThan(time(), $futureDate);
        
        // Time format
        $this->assertMatchesRegularExpression('/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $this->faker->time());
    }

    public function testEmailFormat()
    {
        $email = $this->faker->email();
        $this->assertMatchesRegularExpression('/^[a-z]+[0-9]+@[a-z\.]+$/', $email);
    }
}
