<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthenticationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authentications', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('user_id', 14);
            $table->string('first_name', 128);
            $table->string('last_name', 128);
            $table->string('username', 256);
            $table->string('security_code', 256);
            $table->string('id_number', 256);
            $table->string('status', 256)->default('processing');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->primary('id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table
                ->string('authentication_status')
                ->after('nationality')
                ->default('unauthenticated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('authentications');
    }
}
