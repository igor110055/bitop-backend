<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Models\User;

class UpdateUserInviter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:inviter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Update users' inviter to their group's owner";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = User::all();
        $root = User::find(env('ROOT_ID'));
        foreach ($users as $user) {
            $group_owner = $user->group->owner;
            if (is_null($group_owner)) {
                $group_owner = $root;
            }
            if ($group_owner->id === $user->id) {
                $group_owner = $root;
            }
            $user->update([
                'inviter_id' => $group_owner->id,
            ]);
        }
    }
}
