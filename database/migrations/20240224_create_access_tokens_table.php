<?php

use Lightpack\Database\Migration\Migration;

class CreateAccessTokensTable extends Migration
{
    public function up()
    {
        $this->schema->create('access_tokens', function($table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('token', 100)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->schema->dropIfExists('access_tokens');
    }
}
