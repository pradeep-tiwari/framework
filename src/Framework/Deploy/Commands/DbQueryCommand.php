<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Run a read-only SQL query on the remote server.
 *
 * No need for a GUI client — just fire a SELECT from your terminal.
 *
 * Usage:
 *   php console db:query production --query="SELECT * FROM users LIMIT 5"
 *   php console db:query --query="SHOW TABLES"
 *   php console db:query --query="DESCRIBE orders"
 */
class DbQueryCommand extends Command
{
    use HasDeployConfig;

    private const ALLOWED_PREFIXES = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN', 'WITH'];

    private const BLOCKED_KEYWORDS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'TRUNCATE',
        'REPLACE', 'LOAD', 'UNLOCK', 'LOCK', 'GRANT', 'REVOKE', 'CALL',
        'EXECUTE', 'EXEC',
    ];

    public function run()
    {
        $config = $this->loadConfig();

        if ($config === null) {
            return self::FAILURE;
        }

        $env = $this->resolveEnvironment($config);
        $envConfig = $this->getEnvConfig($config, $env);

        if ($envConfig === null) {
            $this->printEnvironmentError($config, $env);
            return self::FAILURE;
        }

        $query = $this->args->get('query');

        if (empty($query)) {
            $this->output->error('Query is required. Use --query="SELECT ..."');
            $this->output->newline();
            $this->output->line('Examples:');
            $this->output->line('  php console db:query production --query="SELECT * FROM users LIMIT 5"');
            $this->output->line('  php console db:query --query="SHOW TABLES"');
            return self::FAILURE;
        }

        if (!$this->isReadOnly($query)) {
            $this->output->error('Only read-only queries are allowed.');
            $this->output->newline();
            $this->output->line('Allowed: SELECT, SHOW, DESCRIBE, EXPLAIN');
            return self::FAILURE;
        }

        $appPath = $envConfig['path'];
        $remoteScript = $this->buildQueryScript($appPath, $query);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $this->output->info("Running on {$env} …");
        $this->output->newline();

        $result = $this->executeRemote($sshCommand, 60);

        $this->output->newline();

        if ($result['success']) {
            return self::SUCCESS;
        }

        $this->output->error("Query failed (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    /**
     * Check that the query is safe to run (read-only).
     */
    private function isReadOnly(string $query): bool
    {
        // Strip SQL comments so they cannot be used to hide keywords
        $normalized = preg_replace('/--[^\n]*|\/\*[\s\S]*?\*\//', '', $query);
        $normalized = strtoupper(trim($normalized));

        if ($normalized === '') {
            return false;
        }

        // Must start with an allowed prefix
        $startsWithAllowed = false;
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                $startsWithAllowed = true;
                break;
            }
        }

        if (!$startsWithAllowed) {
            return false;
        }

        // Must not contain any blocked keyword as a whole word
        foreach (self::BLOCKED_KEYWORDS as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/', $normalized)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build the remote bash script that reads .env and runs the query.
     */
    private function buildQueryScript(string $appPath, string $query): string
    {
        $escapedQuery = escapeshellarg($query);

        return <<<BASH
set -e

cd "{$appPath}"

if [ ! -f .env ]; then
    echo "ERROR: .env file not found on server" >&2
    exit 1
fi

read_env() {
    local key="\$1"
    grep -E "^\${key}[[:space:]]*=" .env 2>/dev/null \\
        | head -1 \\
        | sed -E "s/^\${key}[[:space:]]*=[[:space:]]*//; s/^[\"']//; s/[\"'][[:space:]]*(#.*)?\$//; s/[[:space:]]*(#.*)?\$//"
}

DB_HOST=\$(read_env DB_HOST)
DB_NAME=\$(read_env DB_NAME)
DB_USER=\$(read_env DB_USER)
DB_PASS=\$(read_env DB_PSWD)

if [ -z "\$DB_NAME" ] || [ -z "\$DB_USER" ]; then
    echo "ERROR: DB_NAME or DB_USER not found in .env" >&2
    exit 1
fi

export MYSQL_PWD="\$DB_PASS"

mysql -h"\$DB_HOST" -u"\$DB_USER" -D "\$DB_NAME" --table -e {$escapedQuery}

unset MYSQL_PWD
BASH;
    }
}
