<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\{
    AccountServiceInterface,
    WalletServiceInterface,
};
use App\Repos\Interfaces\UserRepo;

class UpdateDepositCallback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:deposit-callback {user?} {--coin=} {--address=} {--M|main}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update user deposit callback';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        AccountServiceInterface $AccountService,
        WalletServiceInterface $WalletService,
        UserRepo $UserRepo
    ) {
        parent::__construct();
        $this->AccountService = $AccountService;
        $this->WalletService = $WalletService;
        $this->UserRepo = $UserRepo;
        $this->coins = array_keys(config('coin'));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        # update main account deposit callback
        if (($coin = $this->option('coin')) and ($address = $this->option('address')) and $this->option('main')) {
            assert(in_array($coin, $this->coins));

            if (config('app.env') === 'local') {
                if (is_null(config('services.wallet.callback_proxy_domain'))) {
                    throw new InternalServerError('Must set WALLET_CALLBACK_PROXY_DOMAIN in .env file for wallet callback');
                }
                $deposit_callback_url = config('services.wallet.callback_proxy_domain')."/api/wallet/manual-deposit-callback";
            } else {
                $deposit_callback_url = config('app.url')."/api/wallet/manual-deposit-callback";
            }

            $callback = ['deposit' => $deposit_callback_url];
            dump($this->WalletService->updateAddressCallback($coin, $address, null, $callback));
            return;
        }

        if ($user_id = $this->argument('user')) {
            $user = $this->UserRepo->findOrFail($user_id);
            $users = [$user];
        } else {
            $users = $this->UserRepo->getAllUsers();
        }
        try {
            foreach ($users as $user) {
                $this->info("User {$user->id}");
                $result = $this->AccountService->updateUserDepositCallbacks($user);
                $this->info(print_r($result, true));
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}
