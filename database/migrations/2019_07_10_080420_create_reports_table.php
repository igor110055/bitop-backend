<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $currency_precision = config('core.currency.precision');
            $currency_scale = config('core.currency.scale');

            $table->uuid('id');
            $table->date('date');
            $table->string('agency_id', 20)->nullable();
            $table->unsignedInteger('orders')->default(0);
            $table->unsignedInteger('sell_orders')->default(0);
            $table->unsignedInteger('buy_orders')->default(0);
            $table->decimal('profit', $currency_precision, $currency_scale)->default(0);
            $table->timestamps();

            $table->primary('id');
            $table->index('date');
            $table->unique(['date', 'agency_id']);
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
        Schema::dropIfExists('reports');
    }
}
