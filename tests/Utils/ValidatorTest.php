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

    public function testTypeValidation(): void
    {
        // Test string validation
        $data = ['name' => true];  
        $result = $this->validator->check($data, [
            'name' => $this->validator->rule()->string(),
        ]);
        $this->assertFalse($result->valid);

        // Test int validation
        $data = ['age' => '25'];
        $result = $this->validator->check($data, [
            'age' => $this->validator->rule()->int(),
        ]);
        $this->assertTrue($result->valid);

        // Test float validation
        $data = ['price' => '99.99'];
        $result = $this->validator->check($data, [
            'price' => $this->validator->rule()->float(),
        ]);
        $this->assertTrue($result->valid);

        // Test bool validation
        $data = ['active' => 'true'];
        $result = $this->validator->check($data, [
            'active' => $this->validator->rule()->bool(),
        ]);
        $this->assertTrue($result->valid);

        // Test array validation
        $data = ['items' => 'not-array'];
        $result = $this->validator->check($data, [
            'items' => $this->validator->rule()->array(),
        ]);
        $this->assertFalse($result->valid);
    }

    public function testDateValidation(): void
    {
        // Test date without format
        $data = ['created' => '2025-02-11'];
        $result = $this->validator->check($data, [
            'created' => $this->validator->rule()->date(),
        ]);
        $this->assertTrue($result->valid);

        // Test date with format
        $data = ['birthday' => '11/02/2025'];
        $result = $this->validator->check($data, [
            'birthday' => $this->validator->rule()->date('d/m/Y'),
        ]);
        $this->assertTrue($result->valid);

        // Test invalid date
        $data = ['invalid' => 'not-a-date'];
        $result = $this->validator->check($data, [
            'invalid' => $this->validator->rule()->date(),
        ]);
        $this->assertFalse($result->valid);
    }

    public function testUrlValidation(): void
    {
        $data = [
            'valid' => 'https://example.com',
            'invalid' => 'not-a-url',
        ];

        $result = $this->validator->check($data, [
            'valid' => $this->validator->rule()->url(),
            'invalid' => $this->validator->rule()->url(),
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('invalid', $result->errors);
        $this->assertArrayNotHasKey('valid', $result->errors);
    }

    public function testBetweenValidation(): void
    {
        $data = [
            'valid' => '15',
            'invalid' => '25',
            'non_numeric' => 'abc'
        ];

        $validRule = (new Validator())->rule()->between(10, 20);
        $invalidRule = (new Validator())->rule()->between(0, 10);
        $nonNumericRule = (new Validator())->rule()->between(0, 10);

        $result = (new Validator())->check($data, [
            'valid' => $validRule,
            'invalid' => $invalidRule,
            'non_numeric' => $nonNumericRule
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('invalid', $result->errors);
        $this->assertArrayHasKey('non_numeric', $result->errors);
        $this->assertArrayNotHasKey('valid', $result->errors);
    }

    public function testUniqueValidation(): void
    {
        $data = [
            'valid' => [1, 2, 3],
            'invalid' => [1, 2, 2, 3],
        ];

        $result = $this->validator->check($data, [
            'valid' => $this->validator->rule()->array()->unique(),
            'invalid' => $this->validator->rule()->array()->unique(),
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('invalid', $result->errors);
        $this->assertArrayNotHasKey('valid', $result->errors);
    }

    public function testNullableValidation(): void
    {
        $data = [
            'empty' => '',
            'null' => null,
            'value' => 'test',
        ];

        $result = $this->validator->check($data, [
            'empty' => $this->validator->rule()->nullable()->string(),
            'null' => $this->validator->rule()->nullable()->string(),
            'value' => $this->validator->rule()->nullable()->string(),
        ]);

        $this->assertTrue($result->valid);
    }

    public function testSameValidation(): void
    {
        $data = [
            'password' => 'secret123',
            'confirm_password' => 'secret123',
            'wrong_confirm' => 'different'
        ];

        $result = $this->validator->check($data, [
            'confirm_password' => $this->validator->rule()->same('password'),
            'wrong_confirm' => $this->validator->rule()->same('password')
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('wrong_confirm', $result->errors);
        $this->assertArrayNotHasKey('confirm_password', $result->errors);
    }

    public function testDifferentValidation(): void
    {
        $data = [
            'current_password' => 'secret123',
            'new_password' => 'newpass456',
            'wrong_new' => 'secret123'
        ];

        $result = $this->validator->check($data, [
            'new_password' => $this->validator->rule()->different('current_password'),
            'wrong_new' => $this->validator->rule()->different('current_password')
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('wrong_new', $result->errors);
        $this->assertArrayNotHasKey('new_password', $result->errors);
    }

    public function testMultibyteStringValidation(): void
    {
        $data = [
            'name' => 'José',
            'long_name' => 'あいうえお', // 5 Japanese characters
            'short_name' => '李', // 1 Chinese character
        ];

        $result = $this->validator->check($data, [
            'name' => $this->validator->rule()->string()->min(4),
            'long_name' => $this->validator->rule()->string()->max(5),
            'short_name' => $this->validator->rule()->string()->min(2)
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('short_name', $result->errors);
        $this->assertArrayNotHasKey('name', $result->errors);
        $this->assertArrayNotHasKey('long_name', $result->errors);
    }

    public function testAlphaValidation(): void
    {
        $data = [
            'name' => 'José',
            'invalid' => 'John123',
            'numbers' => '123'
        ];

        $result = $this->validator->check($data, [
            'name' => $this->validator->rule()->alpha(),
            'invalid' => $this->validator->rule()->alpha(),
            'numbers' => $this->validator->rule()->alpha()
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('invalid', $result->errors);
        $this->assertArrayHasKey('numbers', $result->errors);
        $this->assertArrayNotHasKey('name', $result->errors);
    }

    public function testAlphaNumValidation(): void
    {
        $data = [
            'username' => 'José123',
            'invalid' => 'John_123',
            'valid' => '123abc'
        ];

        $result = $this->validator->check($data, [
            'username' => $this->validator->rule()->alphaNum(),
            'invalid' => $this->validator->rule()->alphaNum(),
            'valid' => $this->validator->rule()->alphaNum()
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('invalid', $result->errors);
        $this->assertArrayNotHasKey('username', $result->errors);
        $this->assertArrayNotHasKey('valid', $result->errors);
    }

    public function testInValidation(): void
    {
        $data = [
            'color' => 'red',
            'invalid' => 'orange',
            'valid' => 'blue'
        ];

        $result = $this->validator->check($data, [
            'color' => $this->validator->rule()->in(['red', 'green', 'blue']),
            'invalid' => $this->validator->rule()->in(['red', 'green', 'blue']),
            'valid' => $this->validator->rule()->in(['red', 'green', 'blue'])
        ]);

        $this->assertFalse($result->valid);
        $this->assertArrayHasKey('invalid', $result->errors);
        $this->assertArrayNotHasKey('color', $result->errors);
        $this->assertArrayNotHasKey('valid', $result->errors);
    }
}
