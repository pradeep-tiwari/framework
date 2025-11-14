<?php

namespace Lightpack\Console\Views;

class EventView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace __NAMESPACE__;

class __EVENT_NAME__
{
    public function handle()
    {
        
    }
}
TEMPLATE;
    }
}
