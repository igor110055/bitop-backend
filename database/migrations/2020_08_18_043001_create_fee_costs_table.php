<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeeCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_costs', function (Blueprint $table) {
            $coin_precision = config('core.coin.precision');
            $coin_scale = config('core.coin.scale');

            $table->uuid('id');
            $table->date('date');
            $table->string('coin', 20);
            $table->json('params');
            $table->decimal('cost', $coin_precision, $coin_scale);
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
        Schema::dropIfExists('fee_costs');
    }
}
