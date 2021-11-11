<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfers', function (Blueprint $table) {
            $coin_precision = config('core')['coin']['precision'];
            $coin_scale = config('core')['coin']['scale'];

            $table->uuid('id');
            $table->string('src_user_id', 14);
            $table->string('dst_user_id', 14);
            $table->uuid('src_account_id');
            $table->uuid('dst_account_id');
            $table->string('coin', 20);
            $table->decimal('amount', $coin_precision, $coin_scale);
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

            $table
                ->foreign('src_account_id')
                ->references('id')
                ->on('accounts')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table
                ->foreign('dst_account_id')
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
        Schema::dropIfExists('transfers');
    }
}
