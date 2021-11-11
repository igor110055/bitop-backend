<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $TS = Model::TYPE_SIZE;
            $PS = Model::POLYMORPHIC_TYPE_SIZE;

            $currency_precision = config('core.currency.precision');
            $rate_scale = config('core.currency.rate_scale');
            $coin_precision = config('core')['coin']['precision'];
            $coin_scale = config('core')['coin']['scale'];

            $table->uuid('id');
            $table->uuid('account_id');
            $table->string('coin', 20);
            $table->string('type', $TS);
            $table->decimal('amount', $coin_precision, $coin_scale);
            $table->decimal('balance', $coin_precision, $coin_scale);
            $table->decimal('unit_price', $currency_precision, $rate_scale)->nullable();
            $table->decimal('result_unit_price', $currency_precision, $rate_scale)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->string('transactable_type', $PS)->nullable();
            $table->string('transactable_id', 36)->nullable();
            $table->string('message', 128)->nullable();
            $table->timestamps();

            $table->primary('id');
            $table->index('is_locked');
            $table->index(['coin', 'is_locked', 'created_at']);
            $table
                ->foreign('account_id')
                ->references('id')
                ->on('accounts')
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
        Schema::dropIfExists('transactions');
    }
}
