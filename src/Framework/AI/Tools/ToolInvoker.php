<?php
namespace Lightpack\AI\Tools;

class ToolInvoker
{
    public static function invoke(mixed $tool, array $params, ToolContext $context): mixed
    {
        if ($tool instanceof \Closure) {
            return self::invokeCallable($tool, $params, $context);
        }
        
        if (is_object($tool) && is_callable($tool)) {
            return $tool($params, $context);
        }
        
        if (is_string($tool) && class_exists($tool)) {
            $instance = new $tool();
            return $instance($params, $context);
        }
        
        throw new \InvalidArgumentException('Tool must be closure, invokable object, or class string');
    }
    
    protected static function invokeCallable(\Closure $fn, array $params, ToolContext $context): mixed
    {
        $reflection = new \ReflectionFunction($fn);
        $paramCount = $reflection->getNumberOfParameters();
        
        if ($paramCount === 1) {
            return $fn($params);
        }
        
        return $fn($params, $context);
    }
    
    public static function extractMeta(mixed $tool): array
    {
        if (is_string($tool) && class_exists($tool)) {
            return [
                'description' => method_exists($tool, 'description') ? $tool::description() : null,
                'params' => method_exists($tool, 'params') ? $tool::params() : [],
            ];
        }
        
        if (is_object($tool) && !$tool instanceof \Closure) {
            $class = get_class($tool);
            return [
                'description' => method_exists($class, 'description') ? $class::description() : null,
                'params' => method_exists($class, 'params') ? $class::params() : [],
            ];
        }
        
        return ['description' => null, 'params' => []];
    }
}
