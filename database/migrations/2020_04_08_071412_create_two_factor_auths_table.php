<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTwoFactorAuthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('two_factor_auths', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('user_id', 14);
            $table->string('method');
            $table->string('secret');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->primary('id');
            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_auth')->nullable()->after('security_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('two_factor_auth');
        });
        Schema::dropIfExists('two_factor_auths');
    }
}
