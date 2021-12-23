<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWfpaymentsAndWftransfersAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wfpayments', function (Blueprint $table) {
            $table->renameColumn('account_name', 'wfpay_account_id');

            $table->foreign('wfpay_account_id')
                ->references('id')
                ->on('wfpay_accounts')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('wftransfers', function (Blueprint $table) {
            $table->renameColumn('account_name', 'wfpay_account_id');

            $table->foreign('wfpay_account_id')
                ->references('id')
                ->on('wfpay_accounts')
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
        Schema::table('wfpayments', function (Blueprint $table) {
            $table->dropForeign('wfpayments_wfpay_account_id_foreign');
            $table->renameColumn('wfpay_account_id', 'account_name');
        });

        Schema::table('wftransfers', function (Blueprint $table) {
            $table->dropForeign('wftransfers_wfpay_account_id_foreign');
            $table->renameColumn('wfpay_account_id', 'account_name');
        });
    }
}
