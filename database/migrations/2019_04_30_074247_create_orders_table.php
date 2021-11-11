<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $PS = Model::POLYMORPHIC_TYPE_SIZE;
            $currency_precision = config('core.currency.precision');
            $currency_scale = config('core.currency.scale');
            $rate_scale = config('core.currency.rate_scale');
            $coin_precision = config('core.coin.precision');
            $coin_scale = config('core.coin.scale');

            $table->string('id', 36);
            $table->string('src_user_id', 14);
            $table->string('dst_user_id', 14);
            $table->string('status', 20);
            $table->string('coin', 20);
            $table->decimal('amount', $coin_precision, $coin_scale);
            $table->decimal('fee', $coin_precision, $coin_scale)->default(0);
            $table->string('currency', 3);
            $table->decimal('price', $currency_precision, $currency_scale);
            $table->decimal('unit_price', $currency_precision, $currency_scale);
            $table->decimal('profit', $currency_precision, $currency_scale)->nullable();
            $table->decimal('coin_unit_price', $currency_precision, $rate_scale)->nullable();
            $table->decimal('currency_unit_price', $currency_precision, $rate_scale)->nullable();
            $table->string('payment_src_type', $PS)->nullable();
            $table->uuid('payment_src_id')->nullable();
            $table->string('payment_dst_type', $PS)->nullable();
            $table->uuid('payment_dst_id')->nullable();
            $table->string('advertisement_id', 36)->nullable();
            $table->uuid('fee_setting_id')->nullable();
            $table->timestamp('expired_at');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->primary('id');

            $table
                ->foreign('src_user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table
                ->foreign('dst_user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('fee_setting_id')
                ->references('id')
                ->on('fee_settings')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table
                ->foreign('advertisement_id')
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
        Schema::dropIfExists('orders');
    }
}
