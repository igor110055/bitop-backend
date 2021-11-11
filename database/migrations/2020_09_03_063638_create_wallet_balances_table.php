<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_balances', function (Blueprint $table) {
            $coin_precision = config('core.coin.precision');
            $coin_scale = config('core.coin.scale');

            $table->string('id', 36);
            $table->string('coin', 20);
            $table->decimal('balance', $coin_precision, $coin_scale);
            $table->timestamps();

            $table->primary('id');
            $table->unique('coin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_balances');
    }
}
