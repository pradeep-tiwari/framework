<?php

namespace Lightpack\Debugger;

use Throwable;

class Debug 
{
    private static $data = [];
    private static $startTime;
    private static $queries = [];
    private static $files = [];
    private static $trace = [];
    private static $exceptions = [];
    private static $environment;

    public static function init(string $environment = 'development'): void 
    {
        self::$startTime = microtime(true);
        self::$environment = $environment;
        self::captureFiles();
        self::captureEnvironment();
    }

    public static function dump($var): void 
    {
        if (self::$environment !== 'development') {
            return;
        }

        $type = gettype($var);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        
        self::$data[] = [
            'type' => 'dump',
            'value' => $var,
            'var_type' => $type,
            'file' => $trace['file'],
            'line' => $trace['line'],
            'time' => microtime(true),
        ];
    }

    public static function log(string $message, array $context = []): void 
    {
        if (self::$environment !== 'development') {
            return;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        
        self::$data[] = [
            'type' => 'log',
            'message' => $message,
            'context' => $context,
            'file' => $trace['file'],
            'line' => $trace['line'],
            'time' => microtime(true),
        ];
    }

    public static function query(string $query, float $time, array $bindings = []): void 
    {
        if (self::$environment !== 'development') {
            return;
        }

        self::$queries[] = [
            'query' => $query,
            'time' => $time,
            'bindings' => $bindings,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
        ];
    }

    public static function exception(Throwable $e): void 
    {
        if (self::$environment !== 'development') {
            return;
        }

        self::$exceptions[] = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'time' => microtime(true),
        ];
    }

    public static function getDebugData(): array 
    {
        return [
            'environment' => [
                'execution_time' => number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2),
                'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2),
                'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2),
                'php_version' => PHP_VERSION,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            ],
            'exceptions' => self::$exceptions,
            'data' => self::$data,
        ];
    }

    private static function captureFiles(): void 
    {
        $included = get_included_files();
        foreach ($included as $file) {
            self::$files[] = [
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file),
            ];
        }
    }

    private static function captureEnvironment(): void 
    {
        register_shutdown_function(function() {
            if (self::$environment !== 'development') {
                return;
            }

            $error = error_get_last();
            if ($error) {
                self::$exceptions[] = [
                    'type' => 'Fatal Error',
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'time' => microtime(true),
                ];
            }
        });
    }

    private static function formatBytes(int $bytes): string 
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
