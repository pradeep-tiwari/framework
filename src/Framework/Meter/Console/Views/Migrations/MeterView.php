<?php

namespace Lightpack\Meter\Console\Views\Migrations;

class MeterView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

use Lightpack\Database\Migrations\Migration;
use Lightpack\Database\Schema\Table;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('meters', function (Table $table) {
            $table->id();
            $table->varchar('key', 255);
            $table->column('value')->type('BIGINT')->attribute('UNSIGNED')->default(0);
            $table->timestamps();
            $table->unique('key');
        });
    }

    public function down(): void
    {
        $this->drop('meters');
    }
};
TEMPLATE;
    }
}
