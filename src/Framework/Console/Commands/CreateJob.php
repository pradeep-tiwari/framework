<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\JobView;

class CreateJob implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;
        $module = $this->getModuleOption($arguments);

        if (null === $className) {
            $message = "Please provide the job class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid job class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = JobView::getTemplate();
        
        if ($module) {
            $namespace = "Modules\\{$module}\\Jobs";
            $directory = "./modules/{$module}/Jobs";
            $filepath = DIR_ROOT . "/modules/{$module}/Jobs/{$className}.php";
        } else {
            $namespace = "App\\Jobs";
            $directory = './app/Jobs';
            $filepath = DIR_ROOT . "/app/Jobs/{$className}.php";
        }
        
        $template = str_replace(['__JOB_NAME__', '__NAMESPACE__'], [$className, $namespace], $template);
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $template);
        fputs(STDOUT, "✓ Job created: {$directory}/{$className}.php\n\n");
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
