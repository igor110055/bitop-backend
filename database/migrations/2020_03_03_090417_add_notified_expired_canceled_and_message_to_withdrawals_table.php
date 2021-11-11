<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotifiedExpiredCanceledAndMessageToWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('message', 128)->nullable()->after('callback');
            $table->timestamp('canceled_at')->nullable()->after('confirmed_at');
            $table->timestamp('expired_at')->nullable()->after('confirmed_at');
            $table->timestamp('notified_at')->nullable()->after('confirmed_at');
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
            $table->dropColumn('message');
            $table->dropColumn('canceled_at');
            $table->dropColumn('expired_at');
            $table->dropColumn('notified_at');
        });
    }
}
