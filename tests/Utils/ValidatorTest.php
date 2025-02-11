<?php

declare(strict_types=1);

namespace Lightpack\Tests\Utils;

use Lightpack\Utils\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testBasicValidation(): void
    {
        $data = ['name' => 'John'];
        
        $result = $this->validator->check($data, [
            'name' => $this->validator->rule()->required()->min(2),
        ]);

        $this->assertTrue($result->valid);
        $this->assertEmpty($result->errors);
    }

    public function testFailedValidation(): void
    {
        $data = ['name' => 'J'];
        
        $result = $this->validator->check($data, [
            'name' => $this->validator->rule()->required()->min(2),
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('name', $result->errors);
    }

    public function testEmailValidation(): void
    {
        $data = ['email' => 'invalid'];
        
        $result = $this->validator->check($data, [
            'email' => $this->validator->rule()->required()->email(),
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('email', $result->errors);
    }

    public function testCustomValidation(): void
    {
        $data = ['age' => 15];
        
        $result = $this->validator->check($data, [
            'age' => $this->validator->rule()
                ->required()
                ->custom(fn($value) => $value >= 18, 'Must be 18 or older'),
        ]);

        $this->assertFalse($result->valid);
        $this->assertEquals('Must be 18 or older', $result->errors['age']);
    }

    public function testWildcardValidation(): void
    {
        // Test 1: Basic array validation with required and min length
        $data = [
            'skills' => ['', 'php', '']
        ];
        
        $result = $this->validator->check($data, [
            'skills.*' => $this->validator->rule()->required()->min(2),
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('skills.0', $result->errors);
        $this->assertArrayHasKey('skills.2', $result->errors);

        // Test 2: Complex nested array with multiple validations
        $data = [
            'users' => [
                ['name' => 'Jo', 'email' => 'invalid-email', 'age' => '17'],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => '25'],
                ['name' => '', 'email' => '', 'age' => 'not-numeric']
            ]
        ];

        $result = $this->validator->check($data, [
            'users.*.name' => $this->validator->rule()->required()->min(3),
            'users.*.email' => $this->validator->rule()->required()->email(),
            'users.*.age' => $this->validator->rule()->required()->numeric(),
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('users.0.name', $result->errors);
        $this->assertArrayHasKey('users.0.email', $result->errors);
        $this->assertArrayHasKey('users.2.name', $result->errors);
        $this->assertArrayHasKey('users.2.email', $result->errors);
        $this->assertArrayHasKey('users.2.age', $result->errors);

        // Test 3: Array with custom validation and transformation
        $data = [
            'scores' => ['85', '90', '110', '75']
        ];

        $result = $this->validator->check($data, [
            'scores.*' => $this->validator->rule()
                ->required()
                ->numeric()
                ->transform(fn($value) => (int) $value)
                ->custom(fn($value) => $value <= 100, 'Score must not exceed 100'),
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('scores.2', $result->errors);
        $this->assertEquals('Score must not exceed 100', $result->errors['scores.2']);
        $this->assertIsInt($data['scores'][0]);

        // Test 4: Valid complex data
        $data = [
            'contacts' => [
                ['email' => 'john@example.com', 'phone' => '1234567890'],
                ['email' => 'jane@example.com', 'phone' => '9876543210']
            ]
        ];

        $emailValidator = new Validator();
        $phoneValidator = new Validator();

        $result = $this->validator->check($data, [
            'contacts.*.email' => $emailValidator->rule()->required()->email(),
            'contacts.*.phone' => $phoneValidator->rule()
                ->required()
                ->numeric()
                ->custom(fn($value) => strlen((string) $value) === 10, 'Phone must be exactly 10 digits'),
        ]);

        $this->assertTrue($result->valid);
        $this->assertEmpty($result->errors);
    }

    public function testCustomMessage(): void
    {
        $data = ['name' => ''];
        $message = 'Name is required!';
        
        $result = $this->validator->check($data, [
            'name' => $this->validator->rule()->required()->message($message),
        ]);

        $this->assertFalse($result->valid);
        $this->assertEquals($message, $result->errors['name']);
    }

    public function testTransformation(): void
    {
        $data = ['name' => ' john '];
        
        $this->validator->check($data, [
            'name' => $this->validator->rule()
                ->required()
                ->transform(fn($value) => trim($value))
                ->min(4),
        ]);

        $this->assertEquals('john', $data['name']);
    }

    public function testCustomRule(): void
    {
        $this->validator->addRule('uppercase', function($value) {
            return strtoupper($value) === $value;
        }, 'Must be uppercase');

        $data = ['code' => 'abc'];
        
        $result = $this->validator->check($data, [
            'code' => $this->validator->rule()->required()->uppercase(),
        ]);

        $this->assertFalse($result->valid);
        $this->assertEquals('Must be uppercase', $result->errors['code']);
    }

    public function testNestedValidation(): void
    {
        $data = [
            'user' => [
                'profile' => [
                    'name' => '',
                    'age' => 15
                ]
            ]
        ];
        
        $result = $this->validator->check($data, [
            'user.profile.name' => $this->validator->rule()->required(),
            'user.profile.age' => $this->validator->rule()
                ->required()
                ->custom(fn($value) => $value >= 18),
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('user.profile.name', $result->errors);
        $this->assertArrayHasKey('user.profile.age', $result->errors);
    }
}
