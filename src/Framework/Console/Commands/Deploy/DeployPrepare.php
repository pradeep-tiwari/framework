<?php

namespace Lightpack\Console\Commands\Deploy;

use Lightpack\Console\ICommand;

class DeployPrepare implements ICommand
{
    public function run(array $arguments = [])
    {
        // 1. Create a new release directory
        $timestamp = date('Ymd_His');
        $releaseDir = DIR_ROOT . '/releases/' . $timestamp;
        if (!mkdir($releaseDir, 0777, true)) {
            echo "[deploy:prepare] Failed to create release directory: $releaseDir\n";
            return 1;
        }
        echo "[deploy:prepare] Created release directory: $releaseDir\n";

        // 2. Copy project files to new release (excluding releases, shared, vendor)
        $exclude = ['releases', 'shared', 'vendor'];
        $src = DIR_ROOT;
        self::recurseCopy($src, $releaseDir, $exclude);
        echo "[deploy:prepare] Project files copied to release directory.\n";

        // 3. Symlink shared storage and .env
        $shared = DIR_ROOT . '/shared';
        if (!is_dir("$shared/storage")) {
            mkdir("$shared/storage", 0777, true);
        }
        if (!file_exists("$shared/.env")) {
            copy($src . '/.env', "$shared/.env");
        }
        symlink("$shared/storage", "$releaseDir/storage");
        symlink("$shared/.env", "$releaseDir/.env");
        echo "[deploy:prepare] Symlinks for storage and .env created.\n";

        // 4. Composer install
        chdir($releaseDir);
        passthru('composer install --no-dev --optimize-autoloader');
        echo "[deploy:prepare] Composer install complete.\n";

        // 5. TODO: (Optional) Run migrations, build assets, warm cache
        passthru('php console migrate:up');
        // 6. TODO: (Optional) Add asset build or cache warmup here if needed
        return 0;
    }

    private static function recurseCopy($src, $dst, $exclude = [])
    {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ($file = readdir($dir))) {
            if ($file == '.' || $file == '..' || in_array($file, $exclude)) continue;
            if (is_dir($src . '/' . $file)) {
                self::recurseCopy($src . '/' . $file, $dst . '/' . $file, $exclude);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
        closedir($dir);
    }
}
