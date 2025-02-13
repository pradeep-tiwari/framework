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

    public function testEmailFormat()
    {
        $email = $this->faker->email();
        $this->assertMatchesRegularExpression('/^[a-z]+[0-9]+@[a-z\.]+$/', $email);
    }

    public function testTextGeneration()
    {
        $text = $this->faker->text(5);
        $words = explode(' ', trim($text, '.'));
        $this->assertCount(5, $words);
    }
}
