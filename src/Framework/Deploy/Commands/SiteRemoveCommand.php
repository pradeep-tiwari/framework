<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Remove a FrankenPHP site and optionally its SSL certificate.
 *
 * Usage:
 *   php console server:site:remove production --domain=example.com
 *   php console server:site:remove --domain=example.com --keep-ssl
 */
class SiteRemoveCommand extends Command
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

        $keepSsl = $this->args->has('keep-ssl');

        $this->output->warning("Removing site {$domain} ...");
        $this->output->newline();

        $remoteScript = $this->buildRemoveScript($domain, $keepSsl);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 60);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Site {$domain} removed.");
            return self::SUCCESS;
        }

        $this->output->error("Failed to remove site (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    private function buildRemoveScript(string $domain, bool $keepSsl): string
    {
        $sslCleanup = '';

        if (!$keepSsl) {
            $sslCleanup = <<<SSL

# Caddy manages certificates automatically in /var/lib/caddy
# No manual certificate cleanup needed
SSL;
        }

        return <<<BASH
domain="{$domain}"

# Remove site config
sudo lp-frankenphp-remove "\${domain}"
{$sslCleanup}

# Reload FrankenPHP
sudo lp-frankenphp-reload

echo "Site \${domain} removed."
BASH;
    }
}
