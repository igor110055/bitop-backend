<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateBankAccountPayablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_account_payables', function (Blueprint $table) {
            $PS = Model::POLYMORPHIC_TYPE_SIZE;

            $table->string('bank_account_id');
            $table->string('bank_account_payable_type', $PS);
            $table->string('bank_account_payable_id', 36);
            $table->timestamps();

            $table->foreign('bank_account_id')
                ->references('id')
                ->on('bank_accounts')
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
        Schema::dropIfExists('bank_account_payables');
    }
}
