<?php

namespace Lightpack\Console\Commands\Deploy;

use Lightpack\Console\ICommand;

class DeployActivate implements ICommand
{
    public function run(array $arguments = [])
    {
        $releasesDir = DIR_ROOT . '/releases';
        $currentSymlink = DIR_ROOT . '/current';
        
        // Find the latest release by timestamp
        $releases = array_filter(scandir($releasesDir), function($item) use ($releasesDir) {
            return $item !== '.' && $item !== '..' && is_dir($releasesDir . '/' . $item);
        });
        if(empty($releases)) {
            echo "[deploy:activate] No releases found.\n";
            return 1;
        }
        rsort($releases);
        $latestRelease = $releases[0];
        $latestReleasePath = realpath($releasesDir . '/' . $latestRelease);
        
        // Atomically update the 'current' symlink
        if(is_link($currentSymlink) || file_exists($currentSymlink)) {
            unlink($currentSymlink);
        }
        symlink($latestReleasePath, $currentSymlink);
        echo "[deploy:activate] Symlinked 'current' to $latestReleasePath\n";
        
        // TODO: (Optional) Reload PHP-FPM or clear cache here
        return 0;
    }
}
