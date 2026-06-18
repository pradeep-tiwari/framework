<?php

namespace Lightpack\DevOps\Controllers;

use Lightpack\Console\Console;
use Lightpack\View\Template;

/**
 * Web dashboard for DevOps command management.
 *
 * Provides a visual interface to monitor environments,
 * execute commands, and view logs.
 *
 * Security: protected by DEVOPS_DASHBOARD_KEY env variable.
 */
class DashboardController
{
    private ?array $deployConfig = null;

    public function __construct()
    {
        $this->deployConfig = $this->loadDeployConfig();
    }

    /**
     * Load deploy configuration from config/deploy.php
     */
    private function loadDeployConfig(): ?array
    {
        $configPath = DIR_ROOT . '/config/deploy.php';

        if (!file_exists($configPath)) {
            return null;
        }

        $raw = require $configPath;

        return $raw['deploy'] ?? null;
    }

    /**
     * Render the main DevOps dashboard.
     */
    public function index()
    {
        if (!$this->isAuthorized()) {
            return response()->setStatus(403)->setBody('Unauthorized');
        }

        $environments = $this->deployConfig ? array_keys($this->deployConfig) : [];
        $currentEnv = request()->input('env') ?? ($environments[0] ?? '');
        $envConfig = $currentEnv && $this->deployConfig ? ($this->deployConfig[$currentEnv] ?? null) : null;

        $data = [
            'environments' => $environments,
            'currentEnv' => $currentEnv,
            'envConfig' => $envConfig,
            'commands' => $this->getCommandGroups(),
        ];

        $template = new Template(__DIR__ . '/../Views');
        $html = $template->setData($data)->include('dashboard');

        return response()->setBody($html);
    }

    /**
     * AJAX endpoint to execute a DevOps command.
     */
    public function run()
    {
        if (!$this->isAuthorized()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $command = request()->input('command');
        $env = request()->input('env');
        $args = request()->input('args', []);

        if (empty($command)) {
            return response()->json(['error' => 'Command required'], 400);
        }

        $handler = $this->resolveCommand($command, $env, $args);

        if (!$handler) {
            return response()->json(['error' => 'Unknown command'], 400);
        }

        // Capture output
        ob_start();
        $exitCode = $handler->run();
        $rawOutput = ob_get_clean();

        return response()->json([
            'success' => $exitCode === 0,
            'output' => $this->stripAnsi($rawOutput),
            'exit_code' => $exitCode ?? 0,
        ]);
    }

    /**
     * Stream command output for real-time commands.
     */
    public function stream()
    {
        if (!$this->isAuthorized()) {
            return response()->setStatus(403)->setBody('Unauthorized');
        }

        $command = request()->input('command');
        $env = request()->input('env');

        if (empty($command)) {
            return response()->setStatus(400)->setBody('Command required');
        }

        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $handler = $this->resolveCommand($command, $env);

        if (!$handler) {
            echo "data: " . json_encode(['error' => 'Unknown command']) . "\n\n";
            return;
        }

        ob_start();
        $exitCode = $handler->run();
        $rawOutput = ob_get_clean();

        echo "data: " . json_encode(['output' => $this->stripAnsi($rawOutput), 'done' => true]) . "\n\n";
        flush();
    }

    /**
     * Check if request is authorized to access the dashboard.
     */
    private function isAuthorized(): bool
    {
        $enabled = get_env('DEVOPS_DASHBOARD_ENABLED', 'false');
        if ($enabled !== 'true' && $enabled !== '1') {
            return false;
        }

        $key = get_env('DEVOPS_DASHBOARD_KEY', '');
        if (empty($key)) {
            return false;
        }

        $provided = request()->input('key') ?? request()->header('X-DevOps-Key');

        return hash_equals($key, (string) $provided);
    }

    /**
     * Resolve a console command handler instance.
     */
    private function resolveCommand(string $command, string $env, array $args = []): ?object
    {
        $commands = Console::getCommands();

        if (!isset($commands[$command])) {
            return null;
        }

        $className = $commands[$command];
        // Build args: env first, then any additional args (same as CLI after command name)
        $commandArgs = array_filter([$env, ...$args]);
        $handler = new $className($commandArgs);

        return $handler;
    }

    /**
     * Strip ANSI escape sequences from terminal output.
     */
    private function stripAnsi(string $text): string
    {
        return preg_replace('/\e\[[\d;]*m/', '', $text);
    }

    /**
     * Get command groups for the dashboard UI.
     */
    private function getCommandGroups(): array
    {
        return [
            'deploy' => [
                'label' => 'Deploy',
                'icon' => 'rocket',
                'color' => '#10b981',
                'commands' => [
                    ['cmd' => 'app:deploy', 'label' => 'Deploy', 'desc' => 'Deploy code to server'],
                    ['cmd' => 'app:rollback', 'label' => 'Rollback', 'desc' => 'Rollback to previous commit'],
                ],
            ],
            'queue' => [
                'label' => 'Queue Workers',
                'icon' => 'layers',
                'color' => '#8b5cf6',
                'commands' => [
                    ['cmd' => 'server:queue:status', 'label' => 'Status', 'desc' => 'Check worker status'],
                    ['cmd' => 'server:queue:start', 'label' => 'Start', 'desc' => 'Start queue workers'],
                    ['cmd' => 'server:queue:stop', 'label' => 'Stop', 'desc' => 'Stop queue workers'],
                    ['cmd' => 'server:queue:restart', 'label' => 'Restart', 'desc' => 'Restart queue workers'],
                    ['cmd' => 'server:queue:logs:tail', 'label' => 'Tail Logs', 'desc' => 'Stream worker logs'],
                    ['cmd' => 'server:queue:logs:view', 'label' => 'View Logs', 'desc' => 'View worker logs'],
                ],
            ],
            'schedule' => [
                'label' => 'Scheduler',
                'icon' => 'clock',
                'color' => '#f59e0b',
                'commands' => [
                    ['cmd' => 'server:schedule:status', 'label' => 'Status', 'desc' => 'Check scheduler status'],
                    ['cmd' => 'server:schedule:setup', 'label' => 'Setup', 'desc' => 'Install cron job'],
                    ['cmd' => 'server:schedule:remove', 'label' => 'Remove', 'desc' => 'Remove cron job'],
                ],
            ],
            'logs' => [
                'label' => 'Logs',
                'icon' => 'file-text',
                'color' => '#3b82f6',
                'commands' => [
                    ['cmd' => 'server:logs:view', 'label' => 'View App Logs', 'desc' => 'Last lines of app log'],
                    ['cmd' => 'server:logs:tail', 'label' => 'Tail App Logs', 'desc' => 'Stream app logs'],
                ],
            ],
            'database' => [
                'label' => 'Database',
                'icon' => 'database',
                'color' => '#ec4899',
                'commands' => [
                    ['cmd' => 'db:backup', 'label' => 'Backup', 'desc' => 'Backup database'],
                    ['cmd' => 'db:restore', 'label' => 'Restore', 'desc' => 'Restore database'],
                    ['cmd' => 'db:create', 'label' => 'Create', 'desc' => 'Create database'],
                ],
            ],
            'server' => [
                'label' => 'Server',
                'icon' => 'server',
                'color' => '#6366f1',
                'commands' => [
                    ['cmd' => 'server:provision', 'label' => 'Provision', 'desc' => 'Provision new server'],
                    ['cmd' => 'server:site:add', 'label' => 'Add Site', 'desc' => 'Add Nginx site'],
                    ['cmd' => 'server:site:remove', 'label' => 'Remove Site', 'desc' => 'Remove Nginx site'],
                    ['cmd' => 'server:site:ssl', 'label' => 'SSL', 'desc' => 'Configure SSL certificate'],
                    ['cmd' => 'server:config', 'label' => 'Config', 'desc' => 'Server configuration'],
                    ['cmd' => 'server:run', 'label' => 'Run', 'desc' => 'Run remote command'],
                ],
            ],
        ];
    }
}
