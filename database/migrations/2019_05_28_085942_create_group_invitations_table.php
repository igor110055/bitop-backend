<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_invitations', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('group_id', 20);
            $table->string('invitation_code', 10);
            $table->timestamp('expired_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->primary('id');

            $table->unique('invitation_code');

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
        Schema::table('users', function (Blueprint $table) {
            $table
                ->uuid('invitation_id')
                ->after('nationality')
                ->nullable();
            $table
                ->foreign('invitation_id')
                ->references('id')
                ->on('group_invitations')
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_invitation_id_foreign');
            $table->dropColumn([
                'invitation_id',
            ]);
        });
        Schema::dropIfExists('group_invitations');
    }
}
