<?php

namespace Lightpack\Debugger;

class Output 
{
    private static $templatePath;
    private static $environment = 'development';

    public static function setEnvironment(string $environment): void 
    {
        self::$environment = $environment;
    }

    public static function setTemplatePath(string $path): void 
    {
        self::$templatePath = $path;
    }

    private static function isCli(): bool 
    {
        return PHP_SAPI === 'cli';
    }

    public static function render(array $data): void 
    {
        if (self::isCli()) {
            self::renderCli($data);
            return;
        }

        if (self::isJsonRequest()) {
            self::renderJson($data);
            return;
        }

        if (self::$environment === 'production' && !empty($data['exceptions'])) {
            self::renderProductionError($data['exceptions'][0]);
            return;
        }

        self::renderHtml($data);
    }

    private static function renderProductionError(array $exception): void 
    {
        $code = $exception['code'] ?: 500;
        $message = self::$environment === 'production' 
            ? 'An error occurred. Please try again later.' 
            : $exception['message'];

        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $templateFile = self::$templatePath ?? __DIR__ . '/templates/production.php';
        
        if (file_exists($templateFile)) {
            extract(['code' => $code, 'message' => $message]);
            require $templateFile;
            return;
        }

        // Fallback minimal error page
        echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
        echo "<h1>Error $code</h1><p>" . htmlspecialchars($message) . '</p>';
        echo '</body></html>';
    }

    private static function isJsonRequest(): bool 
    {
        return isset($_SERVER['HTTP_ACCEPT']) && 
               strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }

    private static function renderCli(array $data): void 
    {
        // Environment info
        self::cliHeader('Debug Information');
        
        // Performance metrics
        foreach ($data['environment'] as $key => $value) {
            self::cliMetric(str_pad(ucwords(str_replace('_', ' ', $key)) . ':', 20) . $value);
        }
        
        // Exceptions
        if (!empty($data['exceptions'])) {
            self::cliHeader('Exception');
            foreach ($data['exceptions'] as $exception) {
                self::cliError($exception['message']);
                self::cliInfo('File: ' . $exception['file'] . ':' . $exception['line']);
                self::cliInfo('Stack Trace:');
                self::cliTrace($exception['trace']);
            }
        }

        // Debug data
        if (!empty($data['data'])) {
            self::cliHeader('Debug Log');
            foreach ($data['data'] as $item) {
                if ($item['type'] === 'dump') {
                    self::cliInfo('Variable Dump at ' . $item['file'] . ':' . $item['line']);
                    self::cliDump($item['value']);
                } else {
                    self::cliInfo('Log at ' . $item['file'] . ':' . $item['line']);
                    self::cliMessage($item['message']);
                    if (!empty($item['context'])) {
                        self::cliContext($item['context']);
                    }
                }
            }
        }
        
        // Queries
        if (!empty($data['queries'])) {
            self::cliHeader('Database Queries');
            foreach ($data['queries'] as $query) {
                self::cliSuccess($query['query']);
                self::cliInfo('Time: ' . $query['time'] . 'ms');
                if (!empty($query['bindings'])) {
                    self::cliInfo('Bindings: ' . json_encode($query['bindings']));
                }
                echo "\n";
            }
        }
    }

    private static function renderJson(array $data): void 
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    private static function renderHtml(array $data): void 
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        $hasErrors = !empty($data['exceptions']);
        
        $html = '<div class="debug-panel' . ($hasErrors ? ' has-errors' : '') . '">';
        
        if (!$hasErrors) {
            $html .= '<div class="debug-toggle" title="Toggle Debug Panel">Debug</div>';
        }
        
        $html .= '<div class="debug-content">';
        
        // Environment
        $html .= self::htmlSection('Environment', function() use ($data) {
            $output = '<div class="debug-grid">';
            foreach ($data['environment'] as $key => $value) {
                if ($key === 'execution_time') {
                    $value .= ' ms';
                } elseif (strpos($key, 'memory') !== false) {
                    $value = $value . ' MB';
                }
                $output .= "<div class='debug-item'><span>" . ucwords(str_replace('_', ' ', $key)) . ":</span> <span>$value</span></div>";
            }
            $output .= '</div>';
            return $output;
        });

        // Exceptions
        if ($hasErrors) {
            $html .= self::htmlSection('Exceptions', function() use ($data) {
                $output = '';
                foreach ($data['exceptions'] as $exception) {
                    $output .= "<div class='debug-exception'>";
                    $output .= "<div class='debug-error'>{$exception['message']}</div>";
                    $output .= "<div class='debug-info'>File: {$exception['file']}:{$exception['line']}</div>";
                    $output .= "<pre class='debug-trace'>{$exception['trace']}</pre>";
                    $output .= "</div>";
                }
                return $output;
            });
        }

        // Debug data
        if (!empty($data['data'])) {
            $html .= self::htmlSection('Debug Log', function() use ($data) {
                $output = '';
                foreach ($data['data'] as $item) {
                    $output .= "<div class='debug-item'>";
                    if ($item['type'] === 'dump') {
                        $output .= "<div class='debug-info'>Variable Dump at {$item['file']}:{$item['line']}</div>";
                        $output .= "<pre>" . htmlspecialchars(print_r($item['value'], true)) . "</pre>";
                    } else {
                        $output .= "<div class='debug-info'>Log at {$item['file']}:{$item['line']}</div>";
                        $output .= "<div class='debug-message'>{$item['message']}</div>";
                        if (!empty($item['context'])) {
                            $output .= "<pre>" . htmlspecialchars(json_encode($item['context'], JSON_PRETTY_PRINT)) . "</pre>";
                        }
                    }
                    $output .= "</div>";
                }
                return $output;
            });
        }

        $html .= '</div>'; // debug-content
        $html .= '</div>'; // debug-panel
        $html .= self::getStyles();
        $html .= self::getScript();
        
        echo $html;
    }

    private static function htmlSection(string $title, callable $content): string 
    {
        return "
            <div class='debug-section'>
                <div class='debug-title'>$title</div>
                <div class='debug-content'>{$content()}</div>
            </div>
        ";
    }

    private static function getStyles(): string 
    {
        return "
        <style>
            .debug-panel {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #1a1a1a;
                color: #fff;
                font-family: monospace;
                font-size: 12px;
                z-index: 9999;
                max-height: 80vh;
                transition: transform 0.3s ease;
            }
            .debug-panel.has-errors {
                top: 0;
                max-height: none;
                overflow-y: auto;
            }
            .debug-panel:not(.has-errors).collapsed {
                transform: translateY(calc(100% - 30px));
            }
            .debug-toggle {
                background: #e74c3c;
                color: white;
                padding: 5px 15px;
                cursor: pointer;
                font-weight: bold;
                display: inline-block;
                border-radius: 3px 3px 0 0;
                position: absolute;
                top: -25px;
                left: 20px;
            }
            .debug-toggle:hover {
                background: #c0392b;
            }
            .debug-content {
                padding: 20px;
                overflow-y: auto;
                max-height: calc(80vh - 30px);
                border-top: 2px solid #e74c3c;
            }
            .debug-panel.has-errors .debug-content {
                max-height: none;
                border-top: none;
            }
            .debug-section {
                margin-bottom: 20px;
            }
            .debug-title {
                color: #e74c3c;
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 10px;
                padding-bottom: 5px;
                border-bottom: 1px solid #333;
            }
            .debug-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 10px;
            }
            .debug-item {
                background: #2c2c2c;
                padding: 10px;
                border-radius: 3px;
                margin-bottom: 10px;
            }
            .debug-item span:first-child {
                color: #3498db;
                margin-right: 5px;
            }
            .debug-exception {
                margin-bottom: 15px;
                background: #2c2c2c;
                padding: 15px;
                border-radius: 3px;
            }
            .debug-error {
                color: #e74c3c;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .debug-info {
                color: #95a5a6;
                font-size: 11px;
                margin: 5px 0;
            }
            .debug-trace {
                margin: 10px 0;
                padding: 10px;
                background: #363636;
                border-radius: 3px;
                overflow-x: auto;
                color: #7f8c8d;
                font-size: 11px;
                line-height: 1.4;
            }
            .debug-message {
                color: #f1c40f;
                margin: 5px 0;
            }
            pre {
                margin: 5px 0;
                white-space: pre-wrap;
                word-wrap: break-word;
                background: #363636;
                padding: 10px;
                border-radius: 3px;
                color: #2ecc71;
            }
        </style>
        ";
    }

    private static function getScript(): string
    {
        return "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const panel = document.querySelector('.debug-panel');
                const toggle = document.querySelector('.debug-toggle');
                
                // Only add collapse functionality if there are no errors
                if (!panel.classList.contains('has-errors')) {
                    // Start collapsed
                    panel.classList.add('collapsed');
                    
                    toggle.addEventListener('click', function() {
                        panel.classList.toggle('collapsed');
                    });
                }
            });
        </script>
        ";
    }

    private static function cliHeader(string $text): void 
    {
        fwrite(STDERR, "\n\033[1;36m=== $text ===\033[0m\n");
    }

    private static function cliMetric(string $text): void 
    {
        fwrite(STDERR, "\033[0;33m$text\033[0m\n");
    }

    private static function cliInfo(string $text): void 
    {
        fwrite(STDERR, "\033[0;37m$text\033[0m\n");
    }

    private static function cliError(string $text): void 
    {
        fwrite(STDERR, "\033[1;31m$text\033[0m\n");
    }

    private static function cliSuccess(string $text): void 
    {
        fwrite(STDERR, "\033[0;32m$text\033[0m\n");
    }

    private static function cliMessage(string $text): void 
    {
        fwrite(STDERR, "\033[1;33m$text\033[0m\n");
    }

    private static function cliContext(array $context): void 
    {
        fwrite(STDERR, "\033[0;36m" . json_encode($context, JSON_PRETTY_PRINT) . "\033[0m\n");
    }

    private static function cliTrace(string $trace): void 
    {
        $lines = explode("\n", $trace);
        foreach ($lines as $line) {
            if (trim($line)) {
                fwrite(STDERR, "\033[0;90m$line\033[0m\n");
            }
        }
    }

    private static function cliDump($value): void 
    {
        fwrite(STDERR, "\033[0;36m");
        var_dump($value);
        fwrite(STDERR, "\033[0m");
    }
}
