<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('coin_reports');
        Schema::dropIfExists('group_reports');

        Schema::create('order_reports', function (Blueprint $table) {
            $currency_precision = config('core.currency.precision');
            $currency_scale = config('core.currency.scale');
            $coin_precision = config('core.coin.precision');
            $coin_scale = config('core.coin.scale');
            $coin_rate_scale = config('core.coin.rate_scale');

            $table->uuid('id');
            $table->date('date');
            $table->string('coin', 20)->nullable();
            $table->decimal('exchange_rate', $coin_precision, $coin_rate_scale)->nullable();
            $table->string('group_id', 20)->nullable();
            $table->unsignedInteger('order_count')->default(0);
            $table->decimal('order_amount', $coin_precision, $coin_scale)->nullable();
            $table->decimal('order_price', $currency_precision, $currency_scale)->default(0);
            $table->decimal('share_amount', $coin_precision, $coin_scale)->nullable();
            $table->decimal('share_price', $currency_precision, $currency_scale)->default(0);
            $table->decimal('profit', $currency_precision, $currency_scale)->default(0);
            $table->timestamps();

            $table->primary('id');
            $table->index('date');
            $table->unique(['date', 'coin', 'group_id']);
            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
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
        Schema::dropIfExists('order_reports');
    }
}
