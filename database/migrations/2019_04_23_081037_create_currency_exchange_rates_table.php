<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCurrencyExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $N = config('core')['currency']['precision'];
            $D = config('core')['currency']['rate_scale'];

            $table->uuid('id');
            $table->string('currency', 3);
            $table->decimal('bid', $N, $D)->default(0);
            $table->decimal('ask', $N, $D)->default(0);
            $table->decimal('mid', $N, $D)->default(0);
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
        Schema::dropIfExists('currency_exchange_rates');
    }
}
