<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repos\Interfaces\{
    UserRepo,
    OrderRepo,
};
use App\Models\{
    Order,
    AdminAction,
};

class MigrateUserOrderCountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:user-order-count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existed user order counts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(UserRepo $ur, OrderRepo $or)
    {
        parent::__construct();
        $this->UserRepo = $ur;
        $this->OrderRepo = $or;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->UserRepo->getAllUsers() as $user) {
            $buy_valid = $this->OrderRepo->getUserBuyOrdersCount($user);
            $sell_valid = $this->OrderRepo->getUserSellOrdersCount($user, Order::STATUS_COMPLETED);
            $buy_complete = $this->OrderRepo->getUserBuyOrdersCount($user, Order::STATUS_COMPLETED);
            $sell_complete = 0;
            foreach($this->OrderRepo->getUserSellOrders($user) as $order) {
                if ($order->status === Order::STATUS_COMPLETED and !$order->admin_actions()->first()) {
                    $sell_complete++;
                }
            }
            $user->update([
                'valid_order_count' => $buy_valid + $sell_valid,
                'complete_order_count' => $buy_complete + $sell_complete,
            ]);
        }
    }
}
