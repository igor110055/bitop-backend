<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWithdrawalsTableFeeNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $CP = config('core')['coin']['precision'];
            $CS = config('core')['coin']['scale'];

            $table->decimal('fee', $CP, $CS)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $CP = config('core')['coin']['precision'];
            $CS = config('core')['coin']['scale'];

            $table->decimal('fee', $CP, $CS)->change();
        });
    }
}
