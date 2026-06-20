<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Add a FrankenPHP site for a domain.
 *
 * Usage:
 *   php console server:site:add production --domain=example.com
 *   php console server:site:add --domain=example.com --www   # include www alias
 */
class SiteAddCommand extends Command
{
    use HasDeployConfig;

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

        $domain = $this->args->get('domain');

        if (empty($domain)) {
            $this->output->error('Domain is required. Use --domain=example.com');
            return self::FAILURE;
        }

        if (!$this->validateDomain($domain)) {
            $this->output->error("Invalid domain name: {$domain}");
            return self::FAILURE;
        }

        $includeWww = $this->args->has('www');
        $appPath    = $envConfig['path'];

        $this->output->info("Adding FrankenPHP site for {$domain} ...");
        $this->output->newline();

        $remoteScript = $this->buildSiteScript($domain, $appPath, $includeWww);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 60);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Site {$domain} configured.");

            if (!filter_var($domain, FILTER_VALIDATE_IP)) {
                $this->output->line("SSL is handled automatically by FrankenPHP.");
            }

            return self::SUCCESS;
        }

        $this->output->error("Failed to add site (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    private function buildSiteScript(string $domain, string $appPath, bool $includeWww): string
    {
        $serverNames = $includeWww ? "{$domain}, www.{$domain}" : $domain;

        $configContent = <<<CADDY
{$serverNames} {
    root * {$appPath}/public
    php_server
    file_server
    encode gzip

    header {
        ?X-Powered-By ""
        X-Frame-Options "SAMEORIGIN"
        X-Content-Type-Options "nosniff"
        X-XSS-Protection "1; mode=block"
        Referrer-Policy "strict-origin-when-cross-origin"
    }
}
CADDY;

        return <<<BASH
set -e

CADDY_CONF=\$(cat << 'CADDY_EOF'
{$configContent}
CADDY_EOF
)

echo "\$CADDY_CONF" | sudo lp-frankenphp-write "{$domain}"
sudo lp-frankenphp-reload

echo "Site {$domain} added and enabled."
BASH;
    }
}
