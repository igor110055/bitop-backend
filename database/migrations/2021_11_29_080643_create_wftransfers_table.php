<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWftransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wftransfers', function (Blueprint $table) {
            $currency_precision = config('core.currency.precision');
            $currency_scale = config('core.currency.scale');

            $table->uuid('id');
            $table->string('order_id', 36)->nullable();
            $table->string('status', 20);
            $table->uuid('bank_account_id', 36)->nullable();
            $table->uuid('remote_id')->nullable();
            $table->decimal('total', $currency_precision, $currency_scale);
            $table->string('account_name', 20)->nullable();
            $table->decimal('merchant_fee', $currency_precision, $currency_scale)->nullable();
            $table->json('callback_response')->nullable();
            $table->json('response')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->primary('id');
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('bank_account_id')
                ->references('id')
                ->on('bank_accounts')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wftransfers');
    }
}
