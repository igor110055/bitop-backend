<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    Group,
    User,
    ShareSetting,
};

class RootSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $root_id = env('ROOT_ID');
        $root_email = env('ROOT_EMAIL');
        $password = '12345678';
        if (!User::find($root_id)) {
            $root = User::create([
                'id' => $root_id,
                'email' => $root_email,
                'password' => \Hash::make($password),
                'security_code' => \Hash::make($password),
                'mobile' => '',
                'group_id' => Group::DEFAULT_GROUP_ID,
                'nationality' => 'TW',
                'authentication_status' => 'passed',
                'is_admin' => true,
                'agency_id' => 'default',
                'first_name' => 'Admin',
                'last_name' => 'Admin',
                'username' => 'Admin',
            ]);

            ShareSetting::where('group_id', null)
                ->where('is_prior', false)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            ShareSetting::create([
                'group_id' => null,
                'user_id' => $root->id,
                'percentage' => '100.00',
                'is_prior' => false,
                'is_active' => true,
            ]);
        }
    }
}
