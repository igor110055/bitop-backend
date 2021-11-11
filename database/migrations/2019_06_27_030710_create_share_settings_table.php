<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShareSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_settings', function (Blueprint $table) {

            $table->uuid('id');
            $table->string('group_id', 20)->nullable();
            $table->string('user_id', 14);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->boolean('is_prior')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->primary('id');
            $table->index('is_prior');
            $table->index(['group_id', 'is_active']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
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
        Schema::dropIfExists('share_settings');
    }
}
