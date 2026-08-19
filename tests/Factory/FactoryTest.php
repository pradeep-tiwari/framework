<?php

use Lightpack\Factory\Factory;
use Lightpack\Faker\Faker;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testMakeReturnsArrayWithExpectedKeys()
    {
        $factory = new UserFactory;
        $user = $factory->make();
        $this->assertIsArray($user);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('address', $user);
        $this->assertArrayHasKey('created_at', $user);
    }

    public function testMakeWithOverrides()
    {
        $factory = new UserFactory;
        $user = $factory->make(['email' => 'custom@example.com']);
        $this->assertSame('custom@example.com', $user['email']);
    }

    public function testManyReturnsCorrectCount()
    {
        $factory = new UserFactory;
        $users = $factory->times(5)->make();
        $this->assertCount(5, $users);
        foreach ($users as $user) {
            $this->assertIsArray($user);
        }
    }

    public function testManyWithOverrides()
    {
        $factory = new UserFactory;
        $users = $factory->times(3)->make(['address' => '123 Main St']);
        $this->assertCount(3, $users);
        foreach ($users as $user) {
            $this->assertSame('123 Main St', $user['address']);
        }
    }

    public function testFakerPropertyIsAvailable()
    {
        $factory = new UserFactory;
        $this->assertInstanceOf(Faker::class, $factory->getFaker());
    }

    public function testBatchUniqueEmailsAreActuallyUnique()
    {
        $factory = new UserFactory;
        $users = $factory->times(20)->make();
        $emails = array_column($users, 'email');

        // All 20 emails must be distinct
        $this->assertCount(20, array_unique($emails));
    }

    public function testUniqueResetsBetweeenMakeCalls()
    {
        // Each make() call gets a fresh uniqueness scope —
        // so two separate batches may happen to produce the same values,
        // but within each batch values are unique.
        $factory = new UserFactory;

        $batch1 = $factory->times(5)->make();
        $batch2 = $factory->times(5)->make();

        // Each batch on its own must have unique emails
        $this->assertCount(5, array_unique(array_column($batch1, 'email')));
        $this->assertCount(5, array_unique(array_column($batch2, 'email')));
    }

    public function testCustomFakerCanBeInjected()
    {
        $faker = new Faker;
        $faker->seed(42);

        $factory1 = new UserFactory($faker);
        $user1 = $factory1->make();

        // Same seed produces same result
        $faker2 = new Faker;
        $faker2->seed(42);
        $factory2 = new UserFactory($faker2);
        $user2 = $factory2->make();

        $this->assertSame($user1['name'], $user2['name']);
    }
}

class UserFactory extends Factory
{
    // Expose faker for testing
    public function getFaker(): Faker
    {
        return $this->faker;
    }

    protected function template(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->email(),
            'address' => $this->faker->address(),
            'created_at' => $this->faker->date('Y-m-d H:i:s'),
        ];
    }
}
