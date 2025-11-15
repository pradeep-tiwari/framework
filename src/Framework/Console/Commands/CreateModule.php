<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;

class CreateModule implements ICommand
{
    public function run(array $arguments = [])
    {
        $moduleName = $arguments[0] ?? null;
        
        if (!$moduleName) {
            fputs(STDERR, "Please provide a module name.\n");
            fputs(STDERR, "Usage: php console create:module Blog\n\n");
            return;
        }
        
        // Validate module name
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $moduleName)) {
            fputs(STDERR, "Invalid module name. Must start with uppercase letter and contain only letters and numbers.\n\n");
            return;
        }
        
        $modulePath = DIR_ROOT . '/modules/' . $moduleName;
        
        if (is_dir($modulePath)) {
            fputs(STDERR, "Module already exists: {$moduleName}\n\n");
            return;
        }
        
        // Create module directory structure
        $this->createDirectoryStructure($modulePath, $moduleName);
        
        // Create module provider
        $this->createProvider($modulePath, $moduleName);
        
        // Create example files
        $this->createRoutes($modulePath, $moduleName);
        $this->createEvents($modulePath);
        $this->createCommands($modulePath);
        $this->createConfig($modulePath, $moduleName);
        $this->createSchedules($modulePath);
        $this->createFilters($modulePath);
        
        // Show success message
        fputs(STDOUT, "✓ Module created: {$moduleName}\n\n");
        fputs(STDOUT, "Next steps:\n");
        fputs(STDOUT, "1. Add to boot/modules.php:\n");
        fputs(STDOUT, "   \\Modules\\{$moduleName}\\Providers\\{$moduleName}Provider::class,\n\n");
        fputs(STDOUT, "2. Run: composer dump-autoload\n\n");
    }
    
    private function createDirectoryStructure(string $basePath, string $moduleName): void
    {
        $directories = [
            '',
            '/Controllers',
            '/Models',
            '/Views',
            '/Database/Migrations',
            '/Tests/Feature',
            '/Tests/Unit',
            '/Providers',
            '/Config',
            '/Assets/css',
            '/Assets/js',
        ];
        
        foreach ($directories as $dir) {
            $path = $basePath . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
    
    private function createProvider(string $modulePath, string $moduleName): void
    {
        $namespace = strtolower($moduleName);
        
        $content = <<<PHP
<?php

namespace Modules\\{$moduleName}\\Providers;

use Lightpack\\Modules\\BaseModuleProvider;

class {$moduleName}Provider extends BaseModuleProvider
{
    protected string \$modulePath = __DIR__ . '/..';
    protected string \$namespace = '{$namespace}';
}

PHP;
        
        file_put_contents(
            $modulePath . '/Providers/' . $moduleName . 'Provider.php',
            $content
        );
    }
    
    private function createRoutes(string $modulePath, string $moduleName): void
    {
        $prefix = str()->dasherize($moduleName);
        
        $content = <<<PHP
<?php

route()->group(['prefix' => '{$prefix}'], function() {
    // route()->get('/', {$moduleName}Controller::class, 'index');
});

PHP;
        
        file_put_contents($modulePath . '/routes.php', $content);
    }
    
    private function createEvents(string $modulePath): void
    {
        $content = <<<'PHP'
<?php

return [
    // 'event.name' => [
    //     \Modules\YourModule\Listeners\YourListener::class,
    // ],
];

PHP;
        
        file_put_contents($modulePath . '/events.php', $content);
    }
    
    private function createCommands(string $modulePath): void
    {
        $content = <<<'PHP'
<?php

return [
    // 'module:command' => \Modules\YourModule\Commands\YourCommand::class,
];

PHP;
        
        file_put_contents($modulePath . '/commands.php', $content);
    }

    private function createConfig(string $modulePath, string $moduleName): void
    {
        $namespace = str()->underscore($moduleName);
        $content = <<<'PHP'
<?php

return [
    '{$namespace}' => [
        // 'config.name' => 'value',
    ],
];

PHP;
        
        file_put_contents($modulePath . '/config.php', $content);
    }

    private function createSchedules(string $modulePath): void
    {
        $content = <<<'PHP'
<?php

// schedule()->job(ExampleJob::class)->daily();

PHP;
        
        file_put_contents($modulePath . '/schedules.php', $content);
    }

    private function createFilters(string $modulePath): void
    {
        $content = <<<'PHP'
<?php

return [
    // 'filter.name' => \Modules\YourModule\Filters\YourFilter::class,
];

PHP;
        
        file_put_contents($modulePath . '/filters.php', $content);
    }
}
