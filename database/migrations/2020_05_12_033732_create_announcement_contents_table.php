<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAnnouncementContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('announcement_contents', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('announcement_id');
            $table->string('locale', 16);
            $table->string('title');
            $table->text('content');
            $table->timestamps();

            $table->primary('id');
            $table->foreign('announcement_id')
                ->references('id')
                ->on('announcements')
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
        Schema::dropIfExists('announcement_contents');
    }
}
