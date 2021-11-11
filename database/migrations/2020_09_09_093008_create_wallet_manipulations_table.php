<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletManipulationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_manipulations', function (Blueprint $table) {
            $CP = config('core.coin.precision');
            $CS = config('core.coin.scale');

            $table->uuid('id');
            $table->string('coin', 20);
            $table->string('type', 64);
            $table->string('wallet_id', 36);
            $table->string('transaction');
            $table->decimal('amount', $CP, $CS);
            $table->json('response')->nullable();
            $table->json('callback_response')->nullable();
            $table->timestamps();

            $table->primary('id');
            $table->unique(['type', 'wallet_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wallet_manipulations');
    }
}
