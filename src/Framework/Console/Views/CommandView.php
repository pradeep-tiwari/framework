<?php

namespace Lightpack\Console\Views;

class CommandView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace __NAMESPACE__;

use Lightpack\Console\ICommand;

class __COMMAND_NAME__ implements ICommand
{
    public function run(array $arguments = [])
    {
        fputs(STDOUT, "Hello\n\n");
    }
}
TEMPLATE;
    }
}
