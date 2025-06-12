<?php

namespace Lightpack\Console\Commands\Deploy;

use Lightpack\Console\ICommand;

class DeployCommand implements ICommand
{
    public function run(array $arguments = [])
    {
        // Manual rollback: php console deploy rollback
        if (isset($arguments[0]) && $arguments[0] === 'rollback') {
            echo "[deploy] Rolling back to previous release...\n";
            $rollback = new DeployRollback();
            return $rollback->run($arguments);
        }

        // Step 1: Prepare new release
        echo "[deploy] Preparing new release...\n";
        $prepare = new DeployPrepare();
        $result = $prepare->run($arguments);
        if ($result !== 0) {
            echo "[deploy] Failed during prepare step.\n";
            return $result;
        }

        // Step 2: Activate new release
        echo "[deploy] Activating new release...\n";
        $activate = new DeployActivate();
        $result = $activate->run($arguments);
        if ($result !== 0) {
            echo "[deploy] Failed during activate step. Rolling back...\n";
            $rollback = new DeployRollback();
            $rollback->run($arguments);
            return $result;
        }

        // Step 3: Cleanup old releases (keep 3 by default)
        echo "[deploy] Cleaning up old releases...\n";
        $cleanup = new DeployCleanup();
        $cleanupArgs = isset($arguments[0]) ? [$arguments[0]] : [3];
        $cleanup->run($cleanupArgs);

        echo "[deploy] Done.\n";
        return 0;
    }
}
