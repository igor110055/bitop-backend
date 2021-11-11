<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deposits', function (Blueprint $table) {
            $CP = config('core')['coin']['precision'];
            $CS = config('core')['coin']['scale'];

            $table->uuid('id');
            $table->string('user_id', 14);
            $table->uuid('account_id');
            $table->string('wallet_id', 36);
            $table->string('transaction');
            $table->string('coin', 20);
            $table->string('address', 128);
            $table->string('tag', 32)->nullable();
            $table->decimal('amount', $CP, $CS);
            $table->timestamp('confirmed_at');
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
        Schema::dropIfExists('deposits');
    }
}
