<?php

namespace Lightpack\Meter\Console\Views\Config;

class MeterView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'meter' => [
        /**
         * Available drivers: database, memory
         */
        'driver' => get_env('METER_DRIVER', 'database'),

        /**
         * Database driver
         */
        'database' => [
            'table' => 'meters',
        ],
    ],
];
PHP;
    }
}
