<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\CommandView;

class CreateCommand implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;
        $module = $this->getModuleOption($arguments);

        if (null === $className) {
            $message = "Please provide a command class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid command class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = CommandView::getTemplate();
        
        if ($module) {
            $namespace = "Modules\\{$module}\\Commands";
            $directory = "./modules/{$module}/Commands";
            $filepath = DIR_ROOT . "/modules/{$module}/Commands/{$className}.php";
        } else {
            $namespace = "App\\Commands";
            $directory = './app/Commands';
            $filepath = DIR_ROOT . "/app/Commands/{$className}.php";
        }
        
        $template = str_replace(['__COMMAND_NAME__', '__NAMESPACE__'], [$className, $namespace], $template);
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $template);
        fputs(STDOUT, "✓ Command created: {$directory}/{$className}.php\n\n");
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
