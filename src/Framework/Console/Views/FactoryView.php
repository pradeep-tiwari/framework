<?php

namespace Lightpack\Console\Views;

class FactoryView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace Database\Factories;

use Lightpack\Factory\Factory;

class __FACTORY_NAME__ extends Factory
{
    protected function template(): array
    {
        return [
            // 'name' => $this->faker->name(),
            // 'email' => $this->faker->unique()->email(),
        ];
    }
}
TEMPLATE;
    }

    public static function getModelFactoryTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace Database\Factories;

use __MODEL_CLASS__;
use Lightpack\Factory\ModelFactory;

class __FACTORY_NAME__ extends ModelFactory
{
    protected function template(): array
    {
        return [
            // 'name' => $this->faker->name(),
            // 'email' => $this->faker->unique()->email(),
        ];
    }

    protected function model(): string
    {
        return __MODEL_SHORT__::class;
    }
}
TEMPLATE;
    }
}
