<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('nationality', 2);
            $table->string('name');
            $table->string('phonetic_name')->nullable();
            $table->json('foreign_name')->nullable();
            $table->string('swift_id', 32)->nullable();
            $table->string('local_code', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->primary('id');
            $table->index('nationality');
            $table->index(['swift_id', 'local_code']);

            $table->foreign('nationality')
                ->references('alpha_2')
                ->on('iso3166s')
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
        Schema::dropIfExists('banks');
    }
}
