<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Models\{
    Order,
    SystemAction,
};
use App\Repos\Interfaces\{
    OrderRepo,
    UserRepo,
    SystemActionRepo,
};
use App\Services\OrderServiceInterface;
use App\Notifications\OrderCanceledNotification;
use App\Jobs\PushNotification\{
    OrderCanceledNotification as PushOrderCanceledNotification,
};

class CancelExpiredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cancel:expired-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Cancel expired orders and unlock users' balance.";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        OrderRepo $OrderRepo,
        UserRepo $UserRepo,
        SystemActionRepo $SystemActionRepo,
        OrderServiceInterface $OrderService
    ) {
        $this->OrderRepo = $OrderRepo;
        $this->UserRepo = $UserRepo;
        $this->SystemActionRepo = $SystemActionRepo;
        $this->OrderService = $OrderService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $orders = $this->OrderRepo->getExpiredOrders();
        foreach ($orders as $order) {
            try {
                DB::transaction(function () use ($order) {
                    $this->OrderService->cancel(
                        $order->dst_user,
                        $order->id,
                        SystemAction::class
                    );
                    $this->SystemActionRepo->createByApplicable($order, [
                        'type' => SystemAction::TYPE_CANCEL_ORDER,
                        'description' => 'System cancel this order due to expiration',
                    ]);
                });
                Log::info("CancelExpiredOrders, {$order->id} canceled.");
            } catch (\Throwable $e) {
                Log::alert("CancelExpiredOrders, order {$order->id} unknown error. {$e->getMessage()}");
            }
            # send notification
            $order->src_user->notify(new OrderCanceledNotification($order, SystemAction::class));
            $order->dst_user->notify(new OrderCanceledNotification($order, SystemAction::class));
            PushOrderCanceledNotification::dispatch($order->src_user, $order, SystemAction::class)->onQueue(config('services.push_notification.queue_name'));
            PushOrderCanceledNotification::dispatch($order->dst_user, $order, SystemAction::class)->onQueue(config('services.push_notification.queue_name'));

            # add order count
            $this->UserRepo->updateOrderCount($order->dst_user, false);
        }
    }
}
