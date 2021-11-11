<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateFeeSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fee_settings', function (Blueprint $table) {
            $TS = Model::TYPE_SIZE;
            $PS = Model::POLYMORPHIC_TYPE_SIZE;

            $M = config('core.currency.precision');
            $D = config('core.currency.scale');
            $C = config('core.coin.default_exp');

            $table->uuid('id');
            $table->string('applicable_id', 20)->nullable();
            $table->string('applicable_type', $PS)->nullable();
            $table->string('type', $TS);
            $table->string('coin', 20)->nullable();
            $table->decimal('range_start', $M, $D)->default(0);
            $table->decimal('range_end', $M, $D)->nullable();
            $table->decimal('value', $M, $C)->default(0);
            $table->string('unit', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->primary('id');
            $table->index([
                'applicable_id',
                'applicable_type',
                'type',
                'is_active',
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
        Schema::dropIfExists('fee_settings');
    }
}
