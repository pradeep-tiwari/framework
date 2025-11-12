<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\FilterView;

class CreateFilter implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;
        $module = $this->getModuleOption($arguments);

        if (null === $className) {
            $message = "Please provide a filter class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid filter class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = FilterView::getTemplate();
        
        if ($module) {
            $namespace = "Modules\\{$module}\\Filters";
            $directory = "./modules/{$module}/Filters";
            $filepath = DIR_ROOT . "/modules/{$module}/Filters/{$className}.php";
        } else {
            $namespace = "App\\Filters";
            $directory = './app/Filters';
            $filepath = DIR_ROOT . "/app/Filters/{$className}.php";
        }
        
        $template = str_replace(['__FILTER_NAME__', '__NAMESPACE__'], [$className, $namespace], $template);
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $template);
        fputs(STDOUT, "✓ Filter created: {$directory}/{$className}.php\n\n");
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
