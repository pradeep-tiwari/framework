<?php

namespace Lightpack\Console\Commands\Deploy;

use Lightpack\Console\ICommand;

class DeployCleanup implements ICommand
{
    public function run(array $arguments = [])
    {
        $releasesDir = DIR_ROOT . '/releases';
        $keep = isset($arguments[0]) ? (int)$arguments[0] : 3;
        
        $releases = array_filter(scandir($releasesDir), function($item) use ($releasesDir) {
            return $item !== '.' && $item !== '..' && is_dir($releasesDir . '/' . $item);
        });
        if(count($releases) <= $keep) {
            echo "[deploy:cleanup] Nothing to clean up.\n";
            return 0;
        }
        rsort($releases);
        $toDelete = array_slice($releases, $keep);
        foreach($toDelete as $release) {
            self::rrmdir($releasesDir . '/' . $release);
            echo "[deploy:cleanup] Deleted $release\n";
        }
        return 0;
    }
    private static function rrmdir($dir) {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $path = "$dir/$item";
            if (is_dir($path)) {
                self::rrmdir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
