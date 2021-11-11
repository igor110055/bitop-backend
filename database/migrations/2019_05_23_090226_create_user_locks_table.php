<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserLocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_locks', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('user_id', 14);
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->timestamp('expired_at');
            $table->timestamps();

            $table->primary('id');

            $table->index(['user_id', 'is_active']);
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
        Schema::dropIfExists('user_locks');
    }
}
