<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckWalletBalanceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('check_wallet_balance_logs', function (Blueprint $table) {
            $CP = config('core.coin.precision');
            $CS = config('core.coin.scale');

            $table->uuid('id');
            $table->string('coin', 20);
            $table->decimal('system_balance', $CP, $CS)->default(0);
            $table->decimal('balance', $CP, $CS)->default(0);
            $table->decimal('free_balance', $CP, $CS)->default(0);
            $table->decimal('addresses_balance', $CP, $CS)->default(0);
            $table->decimal('addresses_free_balance', $CP, $CS)->default(0);
            $table->decimal('change_balance', $CP, $CS)->default(0);
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
        Schema::dropIfExists('check_wallet_balance_logs');
    }
}
