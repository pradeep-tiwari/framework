<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * SSL certificate management.
 *
 * FrankenPHP (via Caddy) automatically obtains and renews
 * Let's Encrypt certificates when a site is accessed. No
 * manual certificate installation is required in most cases.
 *
 * Usage:
 *   php console server:site:ssl production --domain=example.com
 *   php console server:site:ssl --domain=example.com --www
 */
class SslCommand extends Command
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

        $this->output->info("Checking SSL for {$domain} ...");
        $this->output->newline();

        $remoteScript = $this->buildCertbotScript($domain, $includeWww, $env);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 60);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("SSL is configured for {$domain}.");
            $this->output->line("FrankenPHP handles certificate provisioning automatically.");
            $this->output->line("Ensure DNS points to this server before accessing via HTTPS.");
            return self::SUCCESS;
        }

        $this->output->error("SSL check failed (exit code: {$result['exit_code']}).");
        $this->output->newline();
        $this->output->line('Common causes:');
        $this->output->line('  - Domain DNS does not point to this server');
        $this->output->line('  - Port 80/443 is blocked by firewall');
        $this->output->line('  - FrankenPHP site config does not exist (run server:site:add first)');

        return self::FAILURE;
    }

    private function buildCertbotScript(string $domain, bool $includeWww, string $env): string
    {
        $domains = [$domain];
        if ($includeWww) {
            $domains[] = "www.{$domain}";
        }
        $domainList = implode(', ', $domains);

        return <<<BASH
set -e

# Ensure site config exists
if [ ! -f "/etc/frankenphp/sites/{$domain}.caddy" ]; then
    echo "ERROR: FrankenPHP site config not found for {$domain}"
    echo "Run: php console server:site:add {$env} --domain={$domain}"
    exit 1
fi

echo "Domains: {$domainList}"
echo ""
echo "FrankenPHP (via Caddy) automatically handles SSL certificates:"
echo "  1. On first HTTP request, Caddy requests a cert from Let's Encrypt"
echo "  2. Certificates are renewed automatically"
echo "  3. HTTP->HTTPS redirect is enabled by default"
echo ""
echo "No manual action required. Ensure DNS A/AAAA records point to this server."
BASH;
    }
}
