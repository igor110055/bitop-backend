<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;

class DefaultGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $default_group_id = Group::DEFAULT_GROUP_ID;
        if (!Group::find($default_group_id)) {
            Group::create([
                'id' => 'default',
                'name' => 'default',
                'is_joinable' => true,
            ]);
        }
    }
}
