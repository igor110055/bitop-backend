<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agency;

class DefaultAgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default_agency_id = Agency::DEFAULT_AGENCY_ID;
        if (!Agency::find($default_agency_id)) {
            Agency::create([
                'id' => 'default',
                'name' => 'default',
            ]);
        }
    }
}
