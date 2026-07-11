<?php

$statusCode = $status_code ?? $code ?? 500;
$isHttpException = isset($ex) && $ex instanceof \Lightpack\Exceptions\HttpException;
$isClientError = $statusCode >= 400 && $statusCode < 500;

$defaultMessage = 'We are facing some technical issues. We will be back soon.';
$message = $defaultMessage;

if ($isHttpException && $isClientError) {
    $message = $ex->getMessage() ?: $defaultMessage;
}

echo json_encode([
    'success' => false,
    'code' => $statusCode,
    'message' => $message,
]);
