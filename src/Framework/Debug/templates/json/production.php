<?php

echo json_encode([
    'success' => false,
    'code' => $status_code ?? $code ?? 500,
    'message' => 'We are facing some technical issues. We will be back soon.',
]);
