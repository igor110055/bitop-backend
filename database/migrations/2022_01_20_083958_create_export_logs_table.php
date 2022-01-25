<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\Model;

class CreateExportLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('export_logs', function (Blueprint $table) {
            $TS = Model::TYPE_SIZE;
            $PS = Model::POLYMORPHIC_TYPE_SIZE;

            $table->bigIncrements('id');
            $table->string('user_id', 14)->nullable();
            $table->string('transaction_id', 36)->nullable();
            $table->string('account', 64)->nullable();
            $table->decimal('amount', 16, 6)->nullable();
            $table->decimal('coin', 16, 6)->nullable();
            $table->decimal('bank_fee', 16, 6)->nullable();
            $table->decimal('system_fee', 16, 6)->nullable();
            $table->decimal('c_fee', 16, 6)->nullable();
            $table->string('type', 10)->nullable();
            $table->decimal('bankc_fee', 16, 6)->nullable();
            $table->string('handler_id', 14)->nullable();
            $table->string('loggable_type', $PS)->nullable();
            $table->string('loggable_id', 36)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('handler_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->index('confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('export_logs');
    }
}
