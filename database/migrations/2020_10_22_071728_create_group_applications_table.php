<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_applications', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('user_id', 14);
            $table->string('group_name', 128);
            $table->string('description');
            $table->string('status', 32)->default('processing');
            $table->timestamps();

            $table->primary('id');
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
        Schema::dropIfExists('group_applications');
    }
}
