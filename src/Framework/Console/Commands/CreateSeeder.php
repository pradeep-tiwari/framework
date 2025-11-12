<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\SeederView;

class CreateSeeder implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;
        $module = $this->getModuleOption($arguments);

        if (null === $className) {
            $message = "Please provide the seeder class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid seeder class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = SeederView::getTemplate();
        $template = str_replace('__SEEDER_NAME__', $className, $template);
        
        if ($module) {
            $directory = "./modules/{$module}/Database/Seeders";
            $filePath = DIR_ROOT . "/modules/{$module}/Database/Seeders/{$className}.php";
            if (!is_dir(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }
        } else {
            $directory = './database/seeders';
            $filePath = DIR_ROOT . "/database/seeders/{$className}.php";
        }
        if (file_exists($filePath)) {
            $message = "Seeder class file already exists: {$directory}/{$className}.php\n\n";
            fputs(STDERR, $message);
            return;
        }
        file_put_contents($filePath, $template);
        fputs(STDOUT, "✓ Seeder class file created: {$directory}/{$className}.php\n\n");
    }
    
    private function getModuleOption(array $arguments): ?string
    {
        foreach ($arguments as $arg) {
            if (str_starts_with($arg, '--module=')) {
                return substr($arg, 9);
            }
        }
        return null;
    }
}
