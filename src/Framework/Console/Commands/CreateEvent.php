<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\EventView;

class CreateEvent implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;
        $module = $this->getModuleOption($arguments);

        if (null === $className) {
            $message = "Please provide an event listener class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid event listener class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = EventView::getTemplate();
        
        if ($module) {
            $namespace = "Modules\\{$module}\\Listeners";
            $directory = "./modules/{$module}/Listeners";
            $filepath = DIR_ROOT . "/modules/{$module}/Listeners/{$className}.php";
        } else {
            $namespace = "App\\Events";
            $directory = './app/Events';
            $filepath = DIR_ROOT . "/app/Events/{$className}.php";
        }
        
        $template = str_replace(['__EVENT_NAME__', '__NAMESPACE__'], [$className, $namespace], $template);
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $template);
        fputs(STDOUT, "✓ Event created: {$directory}/{$className}.php\n\n");
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
