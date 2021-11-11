<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class AddLimitableIdAndTypeToLimitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('limitations', function (Blueprint $table) {
            $PS = Model::POLYMORPHIC_TYPE_SIZE;
            $table->string('limitable_id', 36)->nullable()->after('id');
            $table->string('limitable_type', $PS)->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('limitations', function (Blueprint $table) {
            $table->dropColumn('limitable_id');
            $table->dropColumn('limitable_type');
        });
    }
}
