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
    private static $environment = 'development';
    private static $logger;

    public static function init(string $environment = 'development', string $templatePath = null): void 
    {
        self::$startTime = microtime(true);
        self::$environment = $environment;
        Output::setEnvironment($environment);
        
        if ($templatePath) {
            Output::setTemplatePath($templatePath);
        }

        self::registerHandlers();
        self::captureFiles();
    }

    public static function setLogger(callable $logger): void 
    {
        self::$logger = $logger;
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

        if (self::$logger) {
            call_user_func(self::$logger, $message, $context);
        }
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
        $data = [
            'message' => $e->getMessage(),
            'code' => $e->getCode() ?: 500,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'time' => microtime(true),
        ];

        self::$exceptions[] = $data;

        if (self::$logger && self::$environment === 'production') {
            call_user_func(self::$logger, $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        if (!headers_sent() && self::$environment === 'production') {
            http_response_code($data['code']);
        }

        Output::render(self::getDebugData());
        exit(1);
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
            'queries' => self::$queries,
        ];
    }

    private static function registerHandlers(): void 
    {
        set_error_handler(function(int $code, string $message, string $file, int $line) {
            if (!(error_reporting() & $code)) {
                return;
            }

            throw new \ErrorException($message, $code, $code, $file, $line);
        });

        set_exception_handler([self::class, 'exception']);

        register_shutdown_function(function() {
            $error = error_get_last();
            
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                self::exception(new \ErrorException(
                    $error['message'], 
                    $error['type'], 
                    $error['type'], 
                    $error['file'], 
                    $error['line']
                ));
            }

            if (self::$environment === 'development' && empty(self::$exceptions)) {
                Output::render(self::getDebugData());
            }
        });
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
}
