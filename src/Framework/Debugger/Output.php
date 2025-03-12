<?php

namespace Lightpack\Debugger;

class Output 
{
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

        self::renderHtml($data);
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
        $html = '<div class="debug-panel">';
        
        // Environment
        $html .= self::htmlSection('Environment', function() use ($data) {
            $output = '<div class="debug-grid">';
            foreach ($data['environment'] as $key => $value) {
                $output .= "<div class='debug-item'><span>" . ucfirst($key) . ":</span> <span>$value</span></div>";
            }
            $output .= '</div>';
            return $output;
        });

        // Exceptions
        if (!empty($data['exceptions'])) {
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

        // Queries
        if (!empty($data['queries'])) {
            $html .= self::htmlSection('Database Queries', function() use ($data) {
                $output = '';
                foreach ($data['queries'] as $query) {
                    $output .= "<div class='debug-query'>";
                    $output .= "<pre class='debug-sql'>{$query['query']}</pre>";
                    $output .= "<div class='debug-info'>Time: {$query['time']}ms</div>";
                    if (!empty($query['bindings'])) {
                        $output .= "<div class='debug-info'>Bindings: " . htmlspecialchars(json_encode($query['bindings'])) . "</div>";
                    }
                    $output .= "</div>";
                }
                return $output;
            });
        }

        // Debug Data
        if (!empty($data['data'])) {
            $html .= self::htmlSection('Debug Data', function() use ($data) {
                $output = '';
                foreach ($data['data'] as $item) {
                    $output .= "<div class='debug-item'>";
                    if ($item['type'] === 'dump') {
                        $output .= "<div class='debug-info'>Dump at {$item['file']}:{$item['line']}</div>";
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

        $html .= '</div>';
        $html .= self::getStyles();
        
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
                overflow-y: auto;
                border-top: 2px solid #e74c3c;
            }
            .debug-section {
                padding: 15px;
                border-bottom: 1px solid #333;
            }
            .debug-title {
                color: #e74c3c;
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .debug-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 10px;
            }
            .debug-item {
                background: #2c2c2c;
                padding: 8px;
                border-radius: 3px;
            }
            .debug-item span:first-child {
                color: #3498db;
            }
            .debug-exception {
                margin-bottom: 15px;
                background: #2c2c2c;
                padding: 10px;
                border-radius: 3px;
            }
            .debug-error {
                color: #e74c3c;
                font-weight: bold;
            }
            .debug-info {
                color: #95a5a6;
                font-size: 11px;
                margin: 5px 0;
            }
            .debug-trace {
                margin: 5px 0;
                padding: 8px;
                background: #363636;
                border-radius: 3px;
                overflow-x: auto;
            }
            .debug-query {
                margin-bottom: 10px;
                background: #2c2c2c;
                padding: 10px;
                border-radius: 3px;
            }
            .debug-sql {
                margin: 5px 0;
                color: #2ecc71;
            }
            .debug-message {
                color: #f1c40f;
                margin: 5px 0;
            }
            pre {
                margin: 5px 0;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
        </style>
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
