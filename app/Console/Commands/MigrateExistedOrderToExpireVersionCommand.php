<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use App\Models\Order;

class MigrateExistedOrderToExpireVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:order-expired-version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existed order to expired version';

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
        DB::transaction(function () {
            $orders = Order::where('status', Order::STATUS_CANCELED)->get();
            foreach ($orders as $order) {
                $order->update([
                    'canceled_at' => $order->completed_at,
                    'completed_at' => null,
                ]);
            }
        });
        $this->line('update existed orders successfully');
    }
}
