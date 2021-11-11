<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('user_id', 14);
            $table->uuid('bank_id');
            $table->json('currency');
            $table->string('account', 64);
            $table->string('type', 32)->nullable();
            $table->string('name', 256);
            $table->string('phonetic_name', 256)->nullable();
            $table->string('bank_branch_name', 128)->nullable();
            $table->string('bank_branch_phonetic_name', 128)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->primary('id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('bank_id')
                ->references('id')
                ->on('banks')
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
        Schema::dropIfExists('bank_accounts');
    }
}
