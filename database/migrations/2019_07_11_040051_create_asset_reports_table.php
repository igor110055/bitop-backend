<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssetReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_reports', function (Blueprint $table) {
            $currency_precision = config('core.currency.precision');
            $currency_scale = config('core.currency.scale');
            $rate_scale = config('core.currency.rate_scale');

            $table->uuid('id');
            $table->date('date');
            $table->string('agency_id', 20)->nullable();
            $table->string('currency', 20);
            $table->decimal('unit_price', $currency_precision, $rate_scale)->nullable();
            $table->decimal('balance', $currency_precision, $currency_scale)->default(0);
            $table->decimal('deposit_amount', $currency_precision, $currency_scale)->default(0);
            $table->decimal('manual_deposit_amount', $currency_precision, $currency_scale)->default(0);
            $table->decimal('withdraw_amount', $currency_precision, $currency_scale)->default(0);
            $table->decimal('manual_withdraw_amount', $currency_precision, $currency_scale)->default(0);
            $table->timestamps();

            $table->primary('id');
            $table->index('date');
            $table->index(['date', 'agency_id']);
            $table->unique(['date', 'agency_id', 'currency']);
            $table->foreign('agency_id')
                ->references('id')
                ->on('agencies')
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
        Schema::dropIfExists('asset_reports');
    }
}
