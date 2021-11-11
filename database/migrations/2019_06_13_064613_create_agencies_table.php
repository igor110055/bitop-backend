<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->string('id', 20);
            $table->string('name', 64)->nullable();
            $table->timestamps();

            $table->primary('id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table
                ->string('agency_id')
                ->after('is_admin')
                ->nullable();
            $table
                ->foreign('agency_id')
                ->references('id')
                ->on('agencies')
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
            //$table->dropForeign('users_agency_id_foreign');
            //$table->dropColumn('agency_id');
        });
        Schema::dropIfExists('agencies');
    }
}
