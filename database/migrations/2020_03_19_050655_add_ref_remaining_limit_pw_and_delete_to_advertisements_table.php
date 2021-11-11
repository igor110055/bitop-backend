<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRefRemainingLimitPwAndDeleteToAdvertisementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $currency_precision = config('core.currency.precision');
            $currency_scale = config('core.currency.scale');
            $coin_precision = config('core.coin.precision');
            $coin_scale = config('core.coin.scale');
            $table->string('reference_id', 36)->nullable()->after('id');
            $table->decimal('remaining_amount', $coin_precision, $coin_scale)->after('amount');
            $table->decimal('remaining_fee', $coin_precision, $coin_scale)->default(0)->after('fee');
            $table->decimal('max_limit', $currency_precision, $currency_scale)->after('unit_price');
            $table->decimal('min_limit', $currency_precision, $currency_scale)->after('unit_price');
            $table->integer('payment_window')->after('min_trades');
            $table->softDeletes();

            $table->foreign('reference_id')
                ->references('id')
                ->on('advertisements')
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
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropForeign(['reference_id']);
            $table->dropColumn('reference_id');
            $table->dropColumn('remaining_amount');
            $table->dropColumn('remaining_fee');
            $table->dropColumn('max_limit');
            $table->dropColumn('min_limit');
            $table->dropColumn('payment_window');
            $table->dropSoftDeletes();
        });
    }
}
