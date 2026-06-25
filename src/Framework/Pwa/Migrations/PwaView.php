<?php

namespace Lightpack\Pwa\Migrations;

class PwaView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

use Lightpack\Database\Schema\Table;
use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('pwa_subscriptions', function (Table $table) {
            $table->id();
            $table->column('endpoint')->type('text');
            $table->varchar('p256dh', 255);
            $table->varchar('auth', 255);
            $table->column('user_id')->type('bigint')->attribute('unsigned')->nullable();
            $table->datetime('created_at')->nullable();
            $table->datetime('updated_at')->nullable();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        $this->drop('pwa_subscriptions');
    }
};
TEMPLATE;
    }
}
