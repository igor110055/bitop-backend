<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCoinExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coin_exchange_rates', function (Blueprint $table) {
            $N = config('core')['coin']['precision'];
            $D = config('core')['coin']['rate_scale'];

            $table->uuid('id');
            $table->string('coin', 20);
            $table->decimal('price', $N, $D)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coin_exchange_rates');
    }
}
