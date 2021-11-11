<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateManipulationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manipulations', function (Blueprint $table) {
            $PS = Model::POLYMORPHIC_TYPE_SIZE;

            $table->uuid('id');
            $table->string('user_id', 14);
            $table->string('note', 128)->nullable();
            $table->timestamps();

            $table->primary('id');
            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('manipulations');
    }
}
