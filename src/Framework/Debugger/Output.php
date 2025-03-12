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
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        $html = '<div class="debug-panel">';
        $html .= '<div class="debug-toggle" title="Toggle Debug Panel"><span class="debug-icon">🐛</span> Debug</div>';
        $html .= '<div class="debug-content">';
        
        // Environment
        $html .= self::htmlSection('Environment', function() use ($data) {
            $output = '<div class="debug-grid">';
            
            // Performance metrics
            $output .= '<div class="metric-group"><h4>Performance</h4>';
            $output .= "<div class='debug-item'><span>Execution Time:</span> <span class='value-highlight'>{$data['environment']['execution_time']} ms</span></div>";
            $output .= "<div class='debug-item'><span>Memory Usage:</span> <span class='value-highlight'>{$data['environment']['memory_usage']} MB</span></div>";
            $output .= "<div class='debug-item'><span>Peak Memory:</span> <span class='value-highlight'>{$data['environment']['peak_memory']} MB</span></div>";
            $output .= "<div class='debug-item'><span>Query Count:</span> <span class='value-highlight'>{$data['environment']['query_count']}</span></div>";
            $output .= "<div class='debug-item'><span>Included Files:</span> <span class='value-highlight'>{$data['environment']['included_files']}</span></div>";
            $output .= "<div class='debug-item'><span>Session Size:</span> <span class='value-highlight'>{$data['environment']['session_size']} KB</span></div>";
            $output .= '</div>';
            
            // Request info
            $output .= '<div class="metric-group"><h4>Request</h4>';
            $output .= "<div class='debug-item'><span>Method:</span> <span class='method-{$data['environment']['request_method']}'>{$data['environment']['request_method']}</span></div>";
            $output .= "<div class='debug-item'><span>URI:</span> <span>{$data['environment']['request_uri']}</span></div>";
            $output .= '</div>';
            
            // System info
            $output .= '<div class="metric-group"><h4>System</h4>';
            $output .= "<div class='debug-item'><span>PHP Version:</span> <span>{$data['environment']['php_version']}</span></div>";
            $output .= "<div class='debug-item'><span>Server:</span> <span>{$data['environment']['server']}</span></div>";
            $output .= '</div>';
            
            $output .= '</div>';
            return $output;
        });

        // Database Queries
        if (!empty($data['queries'])) {
            $html .= self::htmlSection('Database', function() use ($data) {
                $output = '<div class="debug-queries">';
                foreach ($data['queries'] as $query) {
                    $output .= "<div class='query-item'>";
                    $output .= "<div class='query-text'>{$query['query']}</div>";
                    $output .= "<div class='query-meta'>";
                    $output .= "<span>Time: {$query['time']} ms</span>";
                    $output .= "<span>Rows: {$query['rows']}</span>";
                    $output .= "</div>";
                    $output .= "</div>";
                }
                $output .= '</div>';
                return $output;
            });
        }

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
                <h3>$title</h3>
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
                font-family: system-ui, -apple-system, sans-serif;
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
                background: #2c3e50;
                color: white;
                padding: 5px 15px;
                cursor: pointer;
                font-weight: 500;
                display: inline-block;
                border-radius: 3px 3px 0 0;
                position: absolute;
                top: -25px;
                left: 20px;
                transition: background 0.2s;
            }
            .debug-toggle:hover {
                background: #34495e;
            }
            .debug-icon {
                margin-right: 4px;
            }
            .debug-content {
                padding: 20px;
                overflow-y: auto;
                max-height: calc(80vh - 30px);
                border-top: 2px solid #2c3e50;
            }
            .debug-panel.has-errors .debug-content {
                max-height: none;
                border-top: none;
            }
            .debug-section {
                margin-bottom: 20px;
                background: #242424;
                border-radius: 6px;
                overflow: hidden;
            }
            .debug-section h3 {
                margin: 0;
                padding: 10px 15px;
                background: #2c3e50;
                color: white;
                font-size: 14px;
                font-weight: 500;
            }
            .debug-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                padding: 15px;
            }
            .metric-group {
                background: #2a2a2a;
                border-radius: 4px;
                padding: 12px;
            }
            .metric-group h4 {
                margin: 0 0 10px 0;
                color: #3498db;
                font-size: 13px;
                font-weight: 500;
            }
            .debug-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 4px 0;
                border-bottom: 1px solid #333;
            }
            .debug-item:last-child {
                border-bottom: none;
            }
            .value-highlight {
                color: #2ecc71;
                font-weight: 500;
            }
            .method-GET { color: #3498db; }
            .method-POST { color: #2ecc71; }
            .method-PUT { color: #f1c40f; }
            .method-DELETE { color: #e74c3c; }
            .query-item {
                background: #2a2a2a;
                margin: 10px;
                padding: 12px;
                border-radius: 4px;
            }
            .query-text {
                color: #e74c3c;
                margin-bottom: 8px;
                font-family: monospace;
            }
            .query-meta {
                color: #7f8c8d;
                font-size: 11px;
            }
            .query-meta span {
                margin-right: 15px;
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
