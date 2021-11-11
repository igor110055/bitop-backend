<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('verifications', function (Blueprint $table) {
            $TS = Model::TYPE_SIZE;
            $PS = Model::POLYMORPHIC_TYPE_SIZE;

            $table->uuid('id');
            $table->string('verificable_id', 36)->nullable();
            $table->string('verificable_type', $PS)->nullable();
            $table->string('type', $TS);
            $table->json('channel')->nullable();
            $table->string('data', 256)->nullable();
            $table->string('code', 64)->nullable();
            $table->integer('tries')->default(0)->unsigned();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->primary('id');
            $table->index(['verificable_id', 'verificable_type']);
            $table->index(['verificable_id', 'verificable_type', 'expired_at']);
            $table->index([
                'verificable_id',
                'verificable_type',
                'type',
                'code',
                'expired_at',
            ], 'id_type_code_expired_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table
                ->uuid('email_verification_id')
                ->after('nationality')
                ->nullable();
            $table
                ->uuid('mobile_verification_id')
                ->after('email_verification_id')
                ->nullable();
            $table
                ->foreign('email_verification_id')
                ->references('id')
                ->on('verifications')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table
                ->foreign('mobile_verification_id')
                ->references('id')
                ->on('verifications')
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
            $table->dropForeign('users_email_verification_id_foreign');
            $table->dropForeign('users_mobile_verification_id_foreign');
            $table->dropColumn([
                'email_verification_id',
                'mobile_verification_id',
            ]);
        });
        Schema::dropIfExists('verifications');
    }
}
