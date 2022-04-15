<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $N = config('core')['currency']['precision'];
            $D = config('core')['currency']['scale'];

            $table->uuid('id');
            $table->string('merchant_id', 20);
            $table->string('coin', 20);
            $table->string('type', 20);
            $table->decimal('bid', $N, $D)->default(0);
            $table->decimal('ask', $N, $D)->default(0);
            $table->decimal('bid_diff', $N, $D)->default(0);
            $table->decimal('ask_diff', $N, $D)->default(0);
            $table->decimal('diff', $N, $D)->default(0);
            $table->timestamps();

            $table->primary('id');
            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index('coin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exchange_rates');
    }
}
