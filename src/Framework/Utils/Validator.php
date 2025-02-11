<?php

declare(strict_types=1);

namespace Lightpack\Utils;

class Validator
{
    private array $currentRules = [];
    private array $customRules = [];
    private array $data = [];
    private array $errors = [];
    private ?Arr $arr = null;

    public function __construct()
    {
        $this->arr = new Arr;
    }

    public function rule(): self
    {
        $this->currentRules = [];
        return $this;
    }

    public function check(array &$data, array $rules): object
    {
        $this->data = &$data;
        $this->errors = [];

        foreach ($rules as $field => $rule) {
            $value = $this->arr->get($field, $data);
            
            if (str_contains($field, '*')) {
                $this->validateWildcard($field, $value, $rule);
                continue;
            }

            $this->validateField($field, $value, $rule);
        }

        return (object) [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
        ];
    }

    public function required(): self
    {
        $this->currentRules[] = [
            'rule' => 'required',
            'params' => [],
            'message' => 'Field is required',
            'callback' => fn($value) => !empty($value),
        ];
        return $this;
    }

    public function email(): self
    {
        $this->currentRules[] = [
            'rule' => 'email',
            'params' => [],
            'message' => 'Invalid email format',
            'callback' => fn($value) => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
        ];
        return $this;
    }

    public function min(int $length): self
    {
        $this->currentRules[] = [
            'rule' => 'min',
            'params' => [$length],
            'message' => "Minimum length is {$length}",
            'callback' => fn($value) => strlen((string) $value) >= $length,
        ];
        return $this;
    }

    public function max(int $length): self
    {
        $this->currentRules[] = [
            'rule' => 'max',
            'params' => [$length],
            'message' => "Maximum length is {$length}",
            'callback' => fn($value) => strlen((string) $value) <= $length,
        ];
        return $this;
    }

    public function numeric(): self
    {
        $this->currentRules[] = [
            'rule' => 'numeric',
            'params' => [],
            'message' => 'Must be numeric',
            'callback' => fn($value) => is_numeric($value),
        ];
        return $this;
    }

    public function custom(callable $callback, string $message = 'Validation failed'): self
    {
        $this->currentRules[] = [
            'rule' => 'custom',
            'params' => [],
            'message' => $message,
            'callback' => $callback,
        ];
        return $this;
    }

    public function message(string $message): self
    {
        if (!empty($this->currentRules)) {
            $lastRule = &$this->currentRules[count($this->currentRules) - 1];
            $lastRule['message'] = $message;
        }
        return $this;
    }

    public function transform(callable $callback): self
    {
        $this->currentRules[] = [
            'rule' => 'transform',
            'params' => [],
            'message' => '',
            'callback' => $callback,
            'transform' => true,
        ];
        return $this;
    }

    public function addRule(string $name, callable $callback, string $message = 'Validation failed'): void
    {
        $this->customRules[$name] = [
            'callback' => $callback,
            'message' => $message,
        ];
    }

    public function __call(string $name, array $arguments): self
    {
        if (isset($this->customRules[$name])) {
            $rule = $this->customRules[$name];
            $this->currentRules[] = [
                'rule' => $name,
                'params' => $arguments,
                'message' => $rule['message'],
                'callback' => $rule['callback'],
            ];
            return $this;
        }

        throw new \BadMethodCallException("Rule '{$name}' not found");
    }

    public function string(): self
    {
        $this->currentRules[] = [
            'rule' => 'string',
            'params' => [],
            'message' => 'Must be a string',
            'callback' => fn($value) => is_string($value),
        ];
        return $this;
    }

    public function int(): self
    {
        $this->currentRules[] = [
            'rule' => 'int',
            'params' => [],
            'message' => 'Must be an integer',
            'callback' => fn($value) => is_int($value) || (is_string($value) && ctype_digit($value)),
        ];
        return $this;
    }

    public function float(): self
    {
        $this->currentRules[] = [
            'rule' => 'float',
            'params' => [],
            'message' => 'Must be a float',
            'callback' => fn($value) => is_float($value) || (is_string($value) && is_numeric($value) && str_contains($value, '.')),
        ];
        return $this;
    }

    public function bool(): self
    {
        $this->currentRules[] = [
            'rule' => 'bool',
            'params' => [],
            'message' => 'Must be a boolean',
            'callback' => fn($value) => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true),
        ];
        return $this;
    }

    public function array(): self
    {
        $this->currentRules[] = [
            'rule' => 'array',
            'params' => [],
            'message' => 'Must be an array',
            'callback' => fn($value) => is_array($value),
        ];
        return $this;
    }

    public function date(?string $format = null): self
    {
        $this->currentRules[] = [
            'rule' => 'date',
            'params' => [$format],
            'message' => $format ? "Must be a valid date in format: {$format}" : 'Must be a valid date',
            'callback' => function($value) use ($format) {
                if ($format) {
                    $date = \DateTime::createFromFormat($format, $value);
                    return $date && $date->format($format) === $value;
                }
                return strtotime($value) !== false;
            },
        ];
        return $this;
    }

    public function url(): self
    {
        $this->currentRules[] = [
            'rule' => 'url',
            'params' => [],
            'message' => 'Must be a valid URL',
            'callback' => fn($value) => filter_var($value, FILTER_VALIDATE_URL) !== false,
        ];
        return $this;
    }

    public function between(int|float $min, int|float $max): self
    {
        $this->currentRules[] = [
            'rule' => 'between',
            'params' => [$min, $max],
            'message' => "Must be between {$min} and {$max}",
            'callback' => function($value) use ($min, $max) {
                if (!is_numeric($value)) {
                    return false;
                }
                $numericValue = (float) $value;
                return $numericValue >= $min && $numericValue <= $max;
            },
        ];
        return $this;
    }

    public function unique(): self
    {
        $this->currentRules[] = [
            'rule' => 'unique',
            'params' => [],
            'message' => 'Values must be unique',
            'callback' => function($value) {
                if (!is_array($value)) {
                    return true;
                }
                return count($value) === count(array_unique($value));
            },
        ];
        return $this;
    }

    public function nullable(): self
    {
        $this->currentRules[] = [
            'rule' => 'nullable',
            'params' => [],
            'message' => '',
            'callback' => fn($value) => true, // Always pass validation
            'nullable' => true,
        ];
        return $this;
    }

    public function same(string $field): self
    {
        $this->currentRules[] = [
            'rule' => 'same',
            'params' => [$field],
            'message' => "Must match {$field}",
            'callback' => fn($value) => $value === $this->arr->get($field, $this->data),
        ];
        return $this;
    }

    private function validateField(string $field, $value, $rules): void
    {
        if ($rules instanceof self) {
            $rules = $rules->currentRules;
        }

        foreach ($rules as $rule) {
            // Handle nullable values
            if ($value === null || $value === '') {
                if (isset($rule['nullable']) && $rule['nullable']) {
                    continue;
                }
                if ($rule['rule'] !== 'required') {
                    continue;
                }
            }

            if (isset($rule['transform']) && $rule['transform']) {
                $value = $rule['callback']($value, ...$rule['params']);
                $parts = explode('.', $field);
                $key = array_pop($parts);
                $target = &$this->data;
                
                foreach ($parts as $part) {
                    if (!isset($target[$part]) || !is_array($target[$part])) {
                        $target[$part] = [];
                    }
                    $target = &$target[$part];
                }
                
                $target[$key] = $value;
                continue;
            }

            $valid = $rule['callback']($value, ...$rule['params']);
            
            if ($valid === false) {
                $this->errors[$field] = $rule['message'];
                break;
            }
        }
    }

    private function validateWildcard(string $field, $values, $rules): void
    {
        if (!is_array($values)) {
            return;
        }

        foreach ($values as $index => $value) {
            $indexedField = str_replace('*', (string) $index, $field);
            $this->validateField($indexedField, $value, $rules);
        }
    }
}
