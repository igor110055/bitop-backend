<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 14);
            $table->string('email', 256)->unique();
            $table->string('password', 60);
            $table->string('security_code', 60)->nullable();
            $table->string('mobile', 64)->unique();
            $table->string('username', 256)->unique()->nullable();
            $table->string('nationality', 2)->nullable();
            $table->boolean('is_admin')->default(false);
            $table->string('first_name', 128)->nullable();
            $table->string('last_name', 128)->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('note', 1024)->nullable();
            $table->timestamps();

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
