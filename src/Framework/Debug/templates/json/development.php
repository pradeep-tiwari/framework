<?php

$trace = [];
if (isset($ex)) {
    foreach ($ex->getTrace() as $frame) {
        $trace[] = [
            'file' => $frame['file'] ?? 'internal',
            'line' => $frame['line'] ?? null,
            'function' => (isset($frame['class']) ? $frame['class'] . $frame['type'] : '') . ($frame['function'] ?? 'unknown'),
        ];
    }
}

echo json_encode([
    'success' => false,
    'code' => $code,
    'error' => [
        'type' => $type ?? 'Exception',
        'class' => isset($ex) ? get_class($ex) : null,
        'message' => $message,
        'file' => $file ?? null,
        'line' => $line ?? null,
        'trace' => $trace,
    ],
], JSON_PRETTY_PRINT);
