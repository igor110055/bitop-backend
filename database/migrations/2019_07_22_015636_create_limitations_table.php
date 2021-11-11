<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Model;

class CreateLimitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('limitations', function (Blueprint $table) {
            $TS = Model::TYPE_SIZE;

            $M = config('core.coin.precision');
            $D = config('core.coin.rate_scale');

            $table->uuid('id');
            $table->string('type', $TS);
            $table->string('coin', 20);
            $table->decimal('min', $M, $D)->default(0);
            $table->decimal('max', $M, $D);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->primary('id');
            $table->index([
                'coin',
                'type',
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
        Schema::dropIfExists('limitations');
    }
}
