<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $CP = config('core')['coin']['precision'];
            $CS = config('core')['coin']['scale'];

            $table->uuid('id');
            $table->string('user_id', 14);
            $table->uuid('account_id');
            $table->string('wallet_id', 36)->nullable();
            $table->string('transaction')->nullable();
            $table->string('coin', 20);
            $table->string('address', 128)->nullable();
            $table->string('tag', 32)->nullable();
            $table->decimal('amount', $CP, $CS)->nullable();
            $table->decimal('src_amount', $CP, $CS)->nullable();
            $table->decimal('dst_amount', $CP, $CS)->nullable();
            $table->decimal('fee', $CP, $CS);
            $table->decimal('wallet_fee', $CP, $CS)->nullable();
            $table->string('wallet_fee_coin', 20)->nullable();
            $table->boolean('is_full_payment')->default(true);
            $table->string('callback', 128);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->primary('id');
            $table->unique('wallet_id');
            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
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
        Schema::dropIfExists('withdrawals');
    }
}
