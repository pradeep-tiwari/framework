# Lightpack Validator Documentation

The Lightpack Validator provides a fluent interface for validating data with a rich set of validation rules. It supports both simple and complex validation scenarios, including nested data structures and array validation.

## Basic Usage

```php
use Lightpack\Utils\Validator;

$validator = new Validator();

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => '25'
];

$result = $validator->check($data, [
    'name' => $validator->rule()->required()->min(2),
    'email' => $validator->rule()->required()->email(),
    'age' => $validator->rule()->required()->numeric()
]);

if (!$result->valid) {
    print_r($result->errors);
}
```

## Available Validation Rules

### Type Validation
- **string()**: Validates that the value is a string
  ```php
  $validator->rule()->string()
  ```

- **int()**: Validates that the value is an integer or numeric string
  ```php
  $validator->rule()->int() // Accepts: 42, "42"
  ```

- **float()**: Validates that the value is a float or decimal string
  ```php
  $validator->rule()->float() // Accepts: 42.5, "42.5"
  ```

- **bool()**: Validates that the value is a boolean or boolean-like
  ```php
  $validator->rule()->bool() // Accepts: true, false, 1, 0, "true", "false"
  ```

- **array()**: Validates that the value is an array
  ```php
  $validator->rule()->array()
  ```

### String Validation
- **required()**: Ensures the field is present and not empty
  ```php
  $validator->rule()->required()
  ```

- **email()**: Validates email format
  ```php
  $validator->rule()->email()
  ```

- **min(int $length)**: Validates minimum string length
  ```php
  $validator->rule()->min(8)
  ```

- **max(int $length)**: Validates maximum string length
  ```php
  $validator->rule()->max(100)
  ```

### Numeric Validation
- **numeric()**: Validates that the value is numeric
  ```php
  $validator->rule()->numeric()
  ```

- **between(int|float $min, int|float $max)**: Validates number is within range
  ```php
  $validator->rule()->between(1, 100)
  ```

### Date and URL Validation
- **date(?string $format = null)**: Validates date format
  ```php
  // Validate any valid date
  $validator->rule()->date()
  
  // Validate specific format
  $validator->rule()->date('Y-m-d')
  ```

- **url()**: Validates URL format
  ```php
  $validator->rule()->url()
  ```

### Array Validation
- **unique()**: Validates array values are unique
  ```php
  $validator->rule()->array()->unique()
  ```

### Optional Fields
- **nullable()**: Allows field to be null or empty
  ```php
  $validator->rule()->nullable()->string()
  ```

### Custom Validation
- **custom(callable $callback, string $message)**: Add custom validation logic
  ```php
  $validator->rule()->custom(
      fn($value) => $value >= 18,
      'Must be 18 or older'
  )
  ```

### Wildcard Validation
Validate array elements using wildcard notation:

```php
$data = [
    'users' => [
        ['name' => 'John', 'email' => 'john@example.com'],
        ['name' => 'Jane', 'email' => 'jane@example.com']
    ]
];

$result = $validator->check($data, [
    'users.*.name' => $validator->rule()->required()->string(),
    'users.*.email' => $validator->rule()->required()->email()
]);
```

### Transform Values
Transform values during validation:

```php
$data = ['price' => '99.99'];

$result = $validator->check($data, [
    'price' => $validator->rule()
        ->numeric()
        ->transform(fn($value) => (float) $value)
]);
```

### Chaining Rules
Multiple validation rules can be chained:

```php
$validator->rule()
    ->required()
    ->string()
    ->min(2)
    ->max(100)
    ->custom(fn($value) => /* custom logic */, 'Custom error message')
```

### Custom Error Messages
Set custom error messages for validation rules:

```php
$validator->rule()
    ->required()
    ->message('This field is mandatory')
    ->min(8)
    ->message('Must be at least 8 characters')
```

### Validation Result
The `check()` method returns an object with:
- `valid`: Boolean indicating if validation passed
- `errors`: Array of validation errors by field

```php
$result = $validator->check($data, $rules);

if (!$result->valid) {
    foreach ($result->errors as $field => $message) {
        echo "$field: $message\n";
    }
}
```

## Best Practices

1. **Create Fresh Validator Instances**
   ```php
   // For complex validations, use fresh instances
   $emailValidator = new Validator();
   $phoneValidator = new Validator();
   ```

2. **Group Related Rules**
   ```php
   // Group rules for reusability
   $passwordRules = $validator->rule()
       ->required()
       ->string()
       ->min(8)
       ->custom(fn($value) => preg_match('/[A-Z]/', $value), 'Need uppercase');
   ```

3. **Handle Nullable Fields**
   ```php
   // Make fields optional but validate if present
   $validator->rule()
       ->nullable()
       ->email()
   ```

This validator component provides a robust foundation for data validation in your application. It's extensible through custom rules and transformations, making it suitable for both simple and complex validation scenarios.
