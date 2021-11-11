<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $currency_precision = config('core')['currency']['precision'];
            $currency_scale = config('core')['currency']['scale'];
            $rate_scale = config('core.currency.rate_scale');

            $table->uuid('id');
            $table->string('agency_id', 14);
            $table->string('currency', 20);
            $table->decimal('balance', $currency_precision, $currency_scale)->default(0);
            $table->decimal('unit_price', $currency_precision, $rate_scale)->nullable();
            $table->timestamps();

            $table->primary('id');
            $table
                ->foreign('agency_id')
                ->references('id')
                ->on('agencies')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unique(['agency_id', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
}
