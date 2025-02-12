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
        
        $result = $this->validator
            ->field('name')
                ->required()
                ->min(2)
            ->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testFailedValidation(): void
    {
        $data = ['name' => 'J'];
        
        $result = $this->validator
            ->field('name')
                ->required()
                ->min(2)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('name', $result->getErrors());
    }

    public function testEmailValidation(): void
    {
        $data = ['email' => 'invalid'];
        
        $result = $this->validator
            ->field('email')
                ->required()
                ->email()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('email', $result->getErrors());
    }

    public function testCustomValidation(): void
    {
        $data = ['age' => 15];
        
        $result = $this->validator
            ->field('age')
                ->required()
                ->custom(fn($value) => $value >= 18, 'Must be 18 or older')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertEquals('Must be 18 or older', $result->getErrors()['age']);
    }

    public function testWildcardValidation(): void
    {
        // Test 1: Basic array validation with required and min length
        $data = [
            'skills' => ['', 'php', '']
        ];
        
        $result = $this->validator
            ->field('skills.*')
                ->required()
                ->min(2)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('skills.0', $result->getErrors());
        $this->assertArrayHasKey('skills.2', $result->getErrors());

        // Test 2: Complex nested array with multiple validations
        $data = [
            'users' => [
                ['name' => 'Jo', 'email' => 'invalid-email', 'age' => '17'],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => '25'],
                ['name' => '', 'email' => '', 'age' => 'not-numeric']
            ]
        ];

        $validator = new Validator();
        $result = $validator
            ->field('users.*.name')
                ->required()
                ->min(3)
            ->field('users.*.email')
                ->required()
                ->email()
            ->field('users.*.age')
                ->required()
                ->numeric()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('users.0.name', $result->getErrors());
        $this->assertArrayHasKey('users.0.email', $result->getErrors());
        $this->assertArrayHasKey('users.2.name', $result->getErrors());
        $this->assertArrayHasKey('users.2.email', $result->getErrors());
        $this->assertArrayHasKey('users.2.age', $result->getErrors());

        // Test 3: Array with custom validation and transformation
        $data = [
            'scores' => ['85', '90', '110', '75']
        ];

        $result = $this->validator
            ->field('scores.*')
                ->required()
                ->numeric()
                ->transform(fn($value) => (int) $value)
                ->custom(fn($value) => $value <= 100, 'Score must not exceed 100')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('scores.2', $result->getErrors());
        $this->assertEquals('Score must not exceed 100', $result->getErrors()['scores.2']);
        $this->assertIsInt($data['scores'][0]);

        // Test 4: Valid complex data
        $data = [
            'contacts' => [
                ['email' => 'john@example.com', 'phone' => '1234567890'],
                ['email' => 'jane@example.com', 'phone' => '9876543210']
            ]
        ];

        $validator = new Validator();
        $result = $validator
            ->field('contacts.*.email')
                ->required()
                ->email()
            ->field('contacts.*.phone')
                ->required()
                ->numeric()
                ->custom(fn($value) => strlen((string) $value) === 10, 'Phone must be exactly 10 digits')
            ->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testCustomMessage(): void
    {
        $data = ['name' => ''];
        $message = 'Name is required!';
        
        $result = $this->validator
            ->field('name')
                ->required()
                ->message($message)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertEquals($message, $result->getErrors()['name']);
    }

    public function testTransformation(): void
    {
        $data = ['name' => ' john '];
        
        $this->validator
            ->field('name')
                ->required()
                ->transform(fn($value) => trim($value))
                ->min(4)
            ->validate($data);

        $this->assertEquals('john', $data['name']);
    }

    public function testCustomRule(): void
    {
        $this->validator->addRule('uppercase', function($value) {
            return strtoupper($value) === $value;
        }, 'Must be uppercase');

        $data = ['code' => 'abc'];
        
        $result = $this->validator
            ->field('code')
                ->required()
                ->uppercase()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertEquals('Must be uppercase', $result->getErrors()['code']);
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
        
        $result = $this->validator
            ->field('user.profile.name')
                ->required()
            ->field('user.profile.age')
                ->required()
                ->custom(fn($value) => $value >= 18)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('user.profile.name', $result->getErrors());
        $this->assertArrayHasKey('user.profile.age', $result->getErrors());
    }

    public function testTypeValidation(): void
    {
        // Test string validation
        $data = ['name' => true];  
        $result = $this->validator
            ->field('name')
                ->string()
            ->validate($data);
        $this->assertFalse($result->isValid());

        // Test int validation
        $data = ['age' => '25'];
        $result = $this->validator
            ->field('age')
                ->int()
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test float validation
        $data = ['price' => '99.99'];
        $result = $this->validator
            ->field('price')
                ->float()
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test bool validation
        $data = ['active' => 'true'];
        $result = $this->validator
            ->field('active')
                ->bool()
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test array validation
        $data = ['items' => 'not-array'];
        $result = $this->validator
            ->field('items')
                ->array()
            ->validate($data);
        $this->assertFalse($result->isValid());
    }

    public function testDateValidation(): void
    {
        // Test date without format
        $data = ['created' => '2025-02-11'];
        $result = $this->validator
            ->field('created')
                ->date()
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test date with format
        $data = ['birthday' => '11/02/2025'];
        $result = $this->validator
            ->field('birthday')
                ->date('d/m/Y')
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test invalid date
        $data = ['invalid' => 'not-a-date'];
        $result = $this->validator
            ->field('invalid')
                ->date()
            ->validate($data);
        $this->assertFalse($result->isValid());
    }

    public function testUrlValidation(): void
    {
        $data = [
            'valid' => 'https://example.com',
            'invalid' => 'not-a-url',
        ];

        $result = $this->validator
            ->field('valid')
                ->url()
            ->field('invalid')
                ->url()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testBetweenValidation(): void
    {
        $data = [
            'valid' => '15',
            'invalid' => '25',
            'non_numeric' => 'abc'
        ];

        $result = $this->validator
            ->field('valid')
                ->between(10, 20)
            ->field('invalid')
                ->between(0, 10)
            ->field('non_numeric')
                ->between(0, 10)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayHasKey('non_numeric', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testUniqueValidation(): void
    {
        $data = [
            'valid' => [1, 2, 3],
            'invalid' => [1, 2, 2, 3],
        ];

        $result = $this->validator
            ->field('valid')
                ->array()
                ->unique()
            ->field('invalid')
                ->array()
                ->unique()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testNullableValidation(): void
    {
        $data = [
            'empty' => '',
            'null' => null,
            'value' => 'test',
        ];

        $result = $this->validator
            ->field('empty')
                ->nullable()
                ->string()
            ->field('null')
                ->nullable()
                ->string()
            ->field('value')
                ->nullable()
                ->string()
            ->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testSameValidation(): void
    {
        $data = [
            'password' => 'secret123',
            'confirm_password' => 'secret123',
            'wrong_confirm' => 'different'
        ];

        $result = $this->validator
            ->field('confirm_password')
                ->same('password')
            ->field('wrong_confirm')
                ->same('password')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('wrong_confirm', $result->getErrors());
        $this->assertArrayNotHasKey('confirm_password', $result->getErrors());
    }

    public function testDifferentValidation(): void
    {
        $data = [
            'current_password' => 'secret123',
            'new_password' => 'newpass456',
            'wrong_new' => 'secret123'
        ];

        $result = $this->validator
            ->field('new_password')
                ->different('current_password')
            ->field('wrong_new')
                ->different('current_password')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('wrong_new', $result->getErrors());
        $this->assertArrayNotHasKey('new_password', $result->getErrors());
    }

    public function testMultibyteStringValidation(): void
    {
        $data = [
            'name' => 'José',
            'long_name' => 'あいうえお', // 5 Japanese characters
            'short_name' => '李', // 1 Chinese character
        ];

        $result = $this->validator
            ->field('name')
                ->string()
                ->min(4)
            ->field('long_name')
                ->string()
                ->max(5)
            ->field('short_name')
                ->string()
                ->min(2)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('short_name', $result->getErrors());
        $this->assertArrayNotHasKey('name', $result->getErrors());
        $this->assertArrayNotHasKey('long_name', $result->getErrors());
    }

    public function testAlphaValidation(): void
    {
        $data = [
            'name' => 'José',
            'invalid' => 'John123',
            'numbers' => '123'
        ];

        $result = $this->validator
            ->field('name')
                ->alpha()
            ->field('invalid')
                ->alpha()
            ->field('numbers')
                ->alpha()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayHasKey('numbers', $result->getErrors());
        $this->assertArrayNotHasKey('name', $result->getErrors());
    }

    public function testAlphaNumValidation(): void
    {
        $data = [
            'username' => 'José123',
            'invalid' => 'John_123',
            'valid' => '123abc'
        ];

        $result = $this->validator
            ->field('username')
                ->alphaNum()
            ->field('invalid')
                ->alphaNum()
            ->field('valid')
                ->alphaNum()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayNotHasKey('username', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testInValidation(): void
    {
        $data = [
            'color' => 'red',
            'invalid' => 'orange',
            'valid' => 'blue'
        ];

        $result = $this->validator
            ->field('color')
                ->in(['red', 'green', 'blue'])
            ->field('invalid')
                ->in(['red', 'green', 'blue'])
            ->field('valid')
                ->in(['red', 'green', 'blue'])
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayNotHasKey('color', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testNotInValidation(): void
    {
        $data = [
            'color' => 'red',
            'invalid' => 'orange',
            'valid' => 'blue'
        ];

        $result = $this->validator
            ->field('color')
                ->notIn(['red', 'green', 'blue'])
            ->field('invalid')
                ->notIn(['red', 'green', 'blue'])
            ->field('valid')
                ->notIn(['red', 'green', 'blue'])
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayNotHasKey('invalid', $result->getErrors());
        $this->assertArrayHasKey('color', $result->getErrors());
        $this->assertArrayHasKey('valid', $result->getErrors());
    }

    public function testRegexValidation(): void
    {
        $data = [
            'valid' => 'abcdef',
            'invalid' => '@#$%^&',
            'empty' => ''
        ];

        $result = $this->validator
            ->field('valid')
                ->required()
                ->regex('/^[a-f0-9]{6}$/i')
            ->field('invalid')
                ->required()
                ->regex('/^[a-f0-9]{6}$/i')
            ->field('empty')
                ->required()
                ->regex('/^[a-f0-9]{6}$/i')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayHasKey('empty', $result->getErrors());
    }
}
