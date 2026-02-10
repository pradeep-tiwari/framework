<?php

namespace Lightpack\Console\Views\Migrations;

class PwaSubscriptionsView
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
            $table->column('user_id')->type('BIGINT')->attribute('UNSIGNED')->nullable();
            $table->text('endpoint');
            $table->varchar('p256dh', 255);
            $table->varchar('auth', 255);
            $table->timestamps();

            $table->foreignKey('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique('endpoint');
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
