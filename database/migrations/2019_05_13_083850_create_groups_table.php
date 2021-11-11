<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->string('id', 20);
            $table->string('user_id')->nullable();
            $table->string('name', 64)->nullable();
            $table->string('description', 256)->nullable();
            $table->boolean('is_joinable')->default(true);
            $table->timestamps();

            $table->primary('id');
            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table
                ->string('group_id')
                ->after('username');
            $table
                ->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('currency_exchange_rates', function (Blueprint $table) {
            $table
                ->string('group_id')
                ->after('id')
                ->nullable();
            $table
                ->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index([
                    'currency',
                    'group_id',
                ]);
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
            $table->dropForeign('users_group_id_foreign');
            $table->dropColumn('group_id');
        });
        Schema::table('currency_exchange_rates', function (Blueprint $table) {
            $table->dropForeign('currency_exchange_rates_group_id_foreign');
            $table->dropIndex('currency_exchange_rates_currency_group_id_index');
            $table->dropColumn('group_id');
        });
        Schema::dropIfExists('groups');
    }
}
