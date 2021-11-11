<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateWalletBalanceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_balance_logs', function (Blueprint $table) {
            $PS = Model::POLYMORPHIC_TYPE_SIZE;
            $coin_precision = config('core.coin.precision');
            $coin_scale = config('core.coin.scale');

            $table->string('id', 36);
            $table->uuid('wallet_balance_id', 36);
            $table->string('coin', 20);
            $table->string('type', 64);
            $table->decimal('amount', $coin_precision, $coin_scale);
            $table->decimal('balance', $coin_precision, $coin_scale);
            $table->string('wlogable_type', $PS)->nullable();
            $table->string('wlogable_id', 36)->nullable();
            $table->timestamps();

            $table->primary('id');
            $table->foreign('wallet_balance_id')
                ->references('id')
                ->on('wallet_balances')
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
        Schema::dropIfExists('wallet_balance_logs');
    }
}
