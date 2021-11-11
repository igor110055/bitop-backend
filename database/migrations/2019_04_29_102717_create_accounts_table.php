<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $currency_precision = config('core')['currency']['precision'];
            $currency_scale = config('core')['currency']['scale'];
            $rate_scale = config('core.currency.rate_scale');
            $coin_precision = config('core')['coin']['precision'];
            $coin_scale = config('core')['coin']['scale'];

            $table->uuid('id');
            $table->string('user_id', 14);
            $table->string('coin', 20);
            $table->decimal('balance', $coin_precision, $coin_scale)->default(0);
            $table->decimal('locked_balance', $coin_precision, $coin_scale)->default(0);
            $table->decimal('unit_price', $currency_precision, $rate_scale)->nullable();
            $table->string('address', 128)->nullable();
            $table->string('tag', 32)->nullable();
            $table->timestamps();

            $table->primary('id');
            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unique(['user_id', 'coin']);
            $table->unique(['coin', 'address', 'tag']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
}
