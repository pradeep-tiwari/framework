<?php

namespace Lightpack\Faker;

class Faker 
{
    private array $data;
    private array $used = [];
    private bool $unique = false;
    
    public function __construct() 
    {
        $path = __DIR__ . '/faker.json';
        $this->data = json_decode(file_get_contents($path), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to load faker data');
        }
    }
    
    public function __call(string $name, array $args): mixed 
    {
        if (!isset($this->data[$name])) {
            throw new \RuntimeException("Unknown faker type: {$name}");
        }
        
        return $this->pick($this->data[$name]);
    }
    
    public function seed(int $seed): self 
    {
        mt_srand($seed);
        return $this;
    }
    
    public function unique(): self 
    {
        $this->unique = true;
        return $this;
    }

    public function email(): string 
    {
        $name = strtolower($this->firstName());
        $number = mt_rand(1, 999);
        $domain = $this->domain();
        
        return "{$name}{$number}@{$domain}";
    }

    public function text(int $words = 10): string 
    {
        $text = [];
        for ($i = 0; $i < $words; $i++) {
            $text[] = $this->word();
        }
        return ucfirst(implode(' ', $text)) . '.';
    }

    public function number(int $min = 0, int $max = 999999): int 
    {
        return mt_rand($min, $max);
    }

    public function date(string $format = 'Y-m-d'): string 
    {
        $timestamp = time() - mt_rand(0, 365 * 24 * 60 * 60);
        return date($format, $timestamp);
    }
    
    private function pick(array $array): string 
    {
        $value = $array[array_rand($array)];
        
        if ($this->unique) {
            while (in_array($value, $this->used)) {
                $value = $array[array_rand($array)];
            }
            $this->used[] = $value;
        }
        
        return $value;
    }
}
