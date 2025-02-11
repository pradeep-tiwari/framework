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

    private function validateField(string $field, $value, $rules): void
    {
        if ($rules instanceof self) {
            $rules = $rules->currentRules;
        }

        foreach ($rules as $rule) {
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
