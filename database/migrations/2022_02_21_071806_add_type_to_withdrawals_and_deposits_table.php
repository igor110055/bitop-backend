<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToWithdrawalsAndDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('type')->after('wallet_id')->nullable();
        });
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('type')->after('wallet_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
