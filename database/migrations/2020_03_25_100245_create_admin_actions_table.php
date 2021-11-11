<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateAdminActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_actions', function (Blueprint $table) {
            $TS = Model::TYPE_SIZE;
            $PS = Model::POLYMORPHIC_TYPE_SIZE;

            $table->uuid('id');
            $table->string('admin_id', 14);
            $table->string('type', $TS);
            $table->string('applicable_type', $PS)->nullable();
            $table->string('applicable_id', 36)->nullable();
            $table->string('description', 512);
            $table->timestamps();

            $table->primary('id');
            $table->foreign('admin_id')
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
        Schema::dropIfExists('admin_actions');
    }
}
