<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWfpayAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wfpay_accounts', function (Blueprint $table) {
            $table->string('id', 20);
            $table->string('api_url', 256);
            $table->string('backstage_url', 256)->nullable();
            $table->string('public_key', 4096);
            $table->string('private_key', 4096);
            $table->boolean('is_active')->default(true);
            $table->integer('rank')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->primary('id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wfpay_accounts');
    }
}
