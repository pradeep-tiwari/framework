<?php

namespace Lightpack\Console\Commands\Deploy;

use Lightpack\Console\ICommand;

class DeployRollback implements ICommand
{
    public function run(array $arguments = [])
    {
        $releasesDir = DIR_ROOT . '/releases';
        $currentSymlink = DIR_ROOT . '/current';
        
        // Find the two most recent releases
        $releases = array_filter(scandir($releasesDir), function($item) use ($releasesDir) {
            return $item !== '.' && $item !== '..' && is_dir($releasesDir . '/' . $item);
        });
        if(count($releases) < 2) {
            echo "[deploy:rollback] Not enough releases to roll back.\n";
            return 1;
        }
        rsort($releases);
        $previousRelease = $releases[1];
        $previousReleasePath = realpath($releasesDir . '/' . $previousRelease);
        
        // Atomically update the 'current' symlink
        if(is_link($currentSymlink) || file_exists($currentSymlink)) {
            unlink($currentSymlink);
        }
        symlink($previousReleasePath, $currentSymlink);
        echo "[deploy:rollback] Symlinked 'current' to $previousReleasePath\n";
        
        // TODO: (Optional) Reload PHP-FPM or clear cache here
        return 0;
    }
}
