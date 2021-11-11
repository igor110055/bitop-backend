<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSubmittedAtSubmittedConfrimedAtToWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->timestamp('submitted_confirmed_at')->nullable()->after('confirmed_at');
            $table->timestamp('submitted_at')->nullable()->after('confirmed_at');

            $table->index('confirmed_at');
            $table->index('submitted_at');
            $table->index('submitted_confirmed_at');
            $table->index('expired_at');
            $table->index('canceled_at');
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
            $table->dropIndex(['confirmed_at']);
            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['submitted_confirmed_at']);
            $table->dropIndex(['expired_at']);
            $table->dropIndex(['canceled_at']);

            $table->dropColumn('submitted_confirmed_at');
            $table->dropColumn('submitted_at');
        });
    }
}
