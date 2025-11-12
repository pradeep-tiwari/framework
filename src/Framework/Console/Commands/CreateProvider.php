<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\ProviderView;

class CreateProvider implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;
        $module = $this->getModuleOption($arguments);

        if (null === $className) {
            $message = "Please provide a class name for service provider.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid service provider class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $provider = strtolower(str_replace('Provider', '', $className));
        $binding = in_array('--instance', $arguments) ? 'factory' : 'register';
        
        if ($module) {
            $namespace = "Modules\\{$module}\\Providers";
            $directory = "./modules/{$module}/Providers";
            $filepath = DIR_ROOT . "/modules/{$module}/Providers/{$className}.php";
        } else {
            $namespace = "App\\Providers";
            $directory = './app/Providers';
            $filepath = DIR_ROOT . "/app/Providers/{$className}.php";
        }
        
        $template = ProviderView::getTemplate();
        $template = str_replace(
            ['__PROVIDER_NAME__', '__PROVIDER_ALIAS__', '__PROVIDER_BINDING__', '__NAMESPACE__'], 
            [$className, $provider, $binding, $namespace], 
            $template
        );
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        file_put_contents($filepath, $template);
        fputs(STDOUT, "✓ Provider created: {$directory}/{$className}.php\n\n");
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
