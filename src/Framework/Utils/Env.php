<?php

namespace Lightpack\Utils;

class Env
{
    private static array $cache = [];

    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                $value = trim($value, '"\'');
                
                // Handle special values
                $value = match (strtolower($value)) {
                    'true', '(true)' => true,
                    'false', '(false)' => false,
                    'null', '(null)' => null,
                    default => $value
                };

                // Handle variable interpolation
                if (str_contains($value, '${')) {
                    $value = preg_replace_callback('/\${([^}]+)}/', function($matches) {
                        return self::get($matches[1]) ?? '';
                    }, $value);
                }

                self::$cache[$key] = $value;
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$cache[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset(self::$cache[$key]);
    }
}