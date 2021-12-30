<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfigsTransferRankToWfpayAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wfpay_accounts', function (Blueprint $table) {
            $table->json('configs')->nullable()->after('rank');
            $table->integer('transfer_rank')->default(0)->after('rank');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wfpay_accounts', function (Blueprint $table) {
            $table->dropColumn('configs');
            $table->dropColumn('transfer_rank');
        });
    }
}
