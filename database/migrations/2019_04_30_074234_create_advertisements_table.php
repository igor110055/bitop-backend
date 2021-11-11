<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdvertisementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $currency_precision = config('core')['currency']['precision'];
            $currency_scale = config('core')['currency']['scale'];
            $coin_precision = config('core')['coin']['precision'];
            $coin_scale = config('core')['coin']['scale'];

            $table->string('id', 36);
            $table->string('user_id', 14);
            $table->string('type', 20);
            $table->string('status', 20);
            $table->string('coin', 20);
            $table->decimal('amount', $coin_precision, $coin_scale);
            $table->decimal('fee', $coin_precision, $coin_scale)->default(0);
            $table->string('currency', 3);
            $table->decimal('price', $currency_precision, $currency_scale);
            $table->decimal('unit_price', $currency_precision, $currency_scale);
            $table->text('terms')->nullable();
            $table->text('message')->nullable();
            $table->integer('min_trades')->default(0);
            $table->integer('pay_time')->default(0);
            $table->string('nationality', 20)->nullable();
            $table->uuid('fee_setting_id')->nullable();
            $table->timestamps();

            $table->primary('id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('fee_setting_id')
                ->references('id')
                ->on('fee_settings')
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
        Schema::dropIfExists('advertisements');
    }
}
