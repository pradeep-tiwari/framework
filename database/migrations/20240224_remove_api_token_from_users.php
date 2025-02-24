<?php

use Lightpack\Database\Migration\Migration;

class RemoveApiTokenFromUsers extends Migration
{
    public function up()
    {
        $this->schema->table('users', function($table) {
            $table->dropColumn('api_token');
        });
    }

    public function down()
    {
        $this->schema->table('users', function($table) {
            $table->string('api_token')->nullable();
        });
    }
}
