<?php

namespace Lightpack\ShortUrl\Migrations;

class ShortUrlsMigration
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
        $this->create('short_urls', function (Table $table) {
            $table->id();
            $table->varchar('code', 32)->unique();
            $table->text('url');
            $table->column('hits')->type('bigint')->attribute('unsigned')->default(0);
            $table->datetime('last_clicked_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        $this->drop('short_urls');
    }
};
TEMPLATE;
    }
}
