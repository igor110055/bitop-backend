<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIso3166sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('iso3166s', function (Blueprint $table) {
            $table->string('id', 3);
            $table->string('alpha_2', 2)->unique();
            $table->string('alpha_3', 3)->unique();
            $table->string('name');

            $table->primary('id');
            $table->index('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table
                ->foreign('nationality')
                ->references('alpha_2')
                ->on('iso3166s')
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_nationality_foreign');
        });
        Schema::dropIfExists('iso3166s');
    }
}
