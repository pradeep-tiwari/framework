<?php

echo json_encode([
    'success' => false,
    'code' => $code,
    'type' => $type ?? 'Exception',
    'message' => $message,
    'file' => $file ?? null,
    'line' => $line ?? null,
    'trace' => isset($ex) ? $ex->getTraceAsString() : null,
], JSON_PRETTY_PRINT);
