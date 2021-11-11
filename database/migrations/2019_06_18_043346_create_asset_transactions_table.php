<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateAssetTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_transactions', function (Blueprint $table) {
            $TS = Model::TYPE_SIZE;
            $PS = Model::POLYMORPHIC_TYPE_SIZE;

            $currency_precision = config('core.currency.precision');
            $currency_scale = config('core.currency.scale');
            $rate_scale = config('core.currency.rate_scale');

            $table->uuid('id');
            $table->uuid('asset_id');
            $table->string('type', $TS);
            $table->decimal('amount', $currency_precision, $currency_scale);
            $table->decimal('balance', $currency_precision, $currency_scale);
            $table->decimal('unit_price', $currency_precision, $rate_scale)->nullable();
            $table->decimal('result_unit_price', $currency_precision, $rate_scale)->nullable();
            $table->string('transactable_type', $PS)->nullable();
            $table->string('transactable_id', 36)->nullable();
            $table->timestamps();

            $table->primary('id');
            $table
                ->foreign('asset_id')
                ->references('id')
                ->on('assets')
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
        Schema::dropIfExists('asset_transactions');
    }
}
