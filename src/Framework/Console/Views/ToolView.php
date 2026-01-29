<?php

namespace Lightpack\Console\Views;

class ToolView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace App\Tools;

use Lightpack\AI\Tools\ToolInterface;
use Lightpack\AI\Tools\ToolContext;

class __TOOL_NAME__ implements ToolInterface
{
    public function __invoke(array $params, ToolContext $context): mixed
    {
        // Implement your tool logic here
        
        return [];
    }
    
    public static function description(): string
    {
        return 'Describe what this tool does';
    }
    
    public static function params(): array
    {
        return [
            // 'param_name' => 'type',
            // 'param_name' => ['type', 'Description of parameter'],
        ];
    }
}
TEMPLATE;
    }
}
