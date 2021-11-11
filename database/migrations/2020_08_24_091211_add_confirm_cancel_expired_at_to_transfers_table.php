<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddConfirmCancelExpiredAtToTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('message');
            $table->timestamp('canceled_at')->nullable()->after('message');
            $table->timestamp('confirmed_at')->nullable()->after('message');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn('expired_at');
            $table->dropColumn('canceled_at');
            $table->dropColumn('confirmed_at');
        });
    }
}
