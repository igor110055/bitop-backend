<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeviceTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('user_id', 14)->nullable();
            $table->string('platform', 16);
            $table->string('service', 32);
            $table->string('token');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at');
            $table->timestamps();

            $table->primary('id');
            $table->index(['platform', 'service', 'token']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_tokens');
    }
}
