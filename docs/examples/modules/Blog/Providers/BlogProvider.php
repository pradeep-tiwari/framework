<?php

namespace Modules\Blog\Providers;

use Lightpack\Modules\BaseModuleProvider;

/**
 * Blog Module Provider
 * 
 * This provider bootstraps the Blog module by loading routes,
 * events, commands, schedules, and views automatically.
 */
class BlogProvider extends BaseModuleProvider
{
    /**
     * Absolute path to the module root directory.
     */
    protected string $modulePath = __DIR__ . '/..';
    
    /**
     * View namespace for this module.
     * Use as: template('blog::posts/index')
     */
    protected string $namespace = 'blog';
}
