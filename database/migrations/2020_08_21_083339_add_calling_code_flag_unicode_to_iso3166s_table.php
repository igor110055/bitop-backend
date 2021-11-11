<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCallingCodeFlagUnicodeToIso3166sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('iso3166s', function (Blueprint $table) {
            $table->string('calling_code', 128)->nullable()->after('name');
            $table->string('flag_unicode', 128)->nullable()->after('calling_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('iso3166s', function (Blueprint $table) {
            $table->dropColumn('calling_code');
            $table->dropColumn('flag_unicode');
        });
    }
}
