<?php

/**
 * DevOps Dashboard Web Routes
 *
 * Include this file in your application's routes to enable
 * the web-based DevOps management dashboard.
 *
 * Example (in your app's routes file):
 *   require __DIR__ . '/../vendor/lightpack/framework/src/Framework/DevOps/routes.php';
 *
 * Required environment variables:
 *   DEVOPS_DASHBOARD_ENABLED=true
 *   DEVOPS_DASHBOARD_KEY=your-secret-key-here
 *
 * Access the dashboard at: /devops?key=your-secret-key-here
 */

use Lightpack\DevOps\Controllers\DashboardController;

// Main dashboard
route()->get('/devops', DashboardController::class, 'index');

// AJAX command execution
route()->post('/devops/run', DashboardController::class, 'run');

// SSE streaming for real-time commands
route()->get('/devops/stream', DashboardController::class, 'stream');
