<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('bank_city_name')->after('name')->nullable();
            $table->string('bank_province_name')->after('name')->nullable();

            $table->dropColumn('phonetic_name');
            $table->dropColumn('bank_branch_name');
            $table->dropColumn('bank_branch_phonetic_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->string('bank_branch_phonetic_name')->after('name')->nullable();
        $table->string('bank_branch_name')->after('name')->nullable();
        $table->string('phonetic_name')->after('name')->nullable();

        $table->dropColumn('bank_city_name');
        $table->dropColumn('bank_province_name');
    }
}
