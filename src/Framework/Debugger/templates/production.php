<?php
/**
 * Production error template
 * Variables available:
 * $message - Error message
 * $code - HTTP status code
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $code ?></title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f8f9fa;
            color: #343a40;
            line-height: 1.5;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .error-container {
            max-width: 600px;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 { 
            margin: 0 0 20px;
            color: #dc3545;
            font-size: 24px;
        }
        p {
            margin: 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Error <?= $code ?></h1>
        <p><?= htmlspecialchars($message) ?></p>
    </div>
</body>
</html>
