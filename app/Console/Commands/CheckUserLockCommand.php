<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use App\Repos\Interfaces\{
    UserRepo,
    SystemActionRepo,
};
use App\Models\{
    UserLog,
    SystemAction,
};
use Carbon\Carbon;

class CheckUserLockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unlock:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'unlock users that pass expired time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(UserRepo $ur, SystemActionRepo $sar)
    {
        $this->UserRepo = $ur;
        $this->SystemActionRepo = $sar;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $locks = $this->UserRepo->getAllUserLocks();
        foreach ($locks as $lock) {
            DB::transaction(function () use ($lock) {
                $this->UserRepo->unlockUserLock($lock);
                $this->SystemActionRepo->createByApplicable($lock, [
                    'type' => SystemAction::TYPE_UNLOCK_USERLOCK,
                    'description' => 'System unlock this userlock due to expiration',
                ]);
            });
        }
        $this->line('Unlock user locks done');
    }
}
