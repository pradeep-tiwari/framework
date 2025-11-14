<?php

namespace Lightpack\Console\Views;

class SeederView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace __NAMESPACE__;

class __SEEDER_NAME__
{
    public function seed()
    {
        // ...
    }
}
TEMPLATE;
    }
}
