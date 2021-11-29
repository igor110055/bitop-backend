<?php

namespace App\Http\Controllers\Admin;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
    TimeConditionTrait,
};
use App\Http\Requests\Admin\{
    OrderUpdateRequest,
    OrderSearchRequest,
};
use App\Models\{
    Order,
    AdminAction,
};
use App\Repos\Interfaces\{
    OrderRepo,
    AdminActionRepo,
    UserRepo,
};
use App\Services\OrderServiceInterface;
use App\Notifications\{
    OrderCanceledNotification,
    OrderCompletedNotification,
    OrderCompletedSrcNotification,
};
use App\Jobs\Fcm\{
    OrderCanceledNotification as FcmOrderCanceledNotification,
    OrderCompletedNotification as FcmOrderCompletedNotification,
    OrderCompletedSrcNotification as FcmOrderCompletedSrcNotification,
};

class OrderController extends AdminController
{
    use DataTableTrait, TimeConditionTrait;

    public function __construct(
        OrderRepo $OrderRepo,
        AdminActionRepo $AdminActionRepo,
        UserRepo $UserRepo,
        OrderServiceInterface $OrderService
    ) {
        parent::__construct();
        $this->OrderRepo = $OrderRepo;
        $this->AdminActionRepo = $AdminActionRepo;
        $this->UserRepo = $UserRepo;
        $this->OrderService = $OrderService;
        $this->tz = config('core.timezone.default');
    }

    public function index()
    {
        $dateFormat = 'Y-m-d';
        return view('admin.orders', [
            'from' => Carbon::parse('today -10 days', $this->tz)->format($dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($dateFormat),
            'status' => [
                'all' => 'All',
                Order::STATUS_PROCESSING => 'Processing',
                Order::STATUS_CLAIMED => 'Claimed',
                Order::STATUS_COMPLETED => 'Completed',
                Order::STATUS_CANCELED => 'Canceled',
            ],
        ]);
    }

    public function show(Order $order)
    {
        if ($order->canceled_at) {
            $info = [];
            $info['canceled_at'] = $order->canceled_at->setTimezone($this->tz)->toDateTimeString();
            if ($order->system_actions()->first()) {
                $info['action'] = 'System';
            } elseif ($admin_action = $order->admin_actions()->first()) {
                $info['action'] = 'Admin';
                $info['admin'] = $admin_action->admin_id;
                $info['description'] = $admin_action->description;
            } else {
                $info['action'] = 'User';
            }
        }

        return view('admin.order', [
            'order' => $order,
            'src_user' => $order->src_user,
            'dst_user' => $order->dst_user,
            'bank_accounts' => $order->bank_accounts,
            'payment' => $order->payment,
            'cancel_info' => $info ?? null,
        ]);
    }

    public function update(Order $order, OrderUpdateRequest $request)
    {
        $values = $request->validated();

        DB::transaction(function () use ($order, $values) {
            if ($values['action'] === AdminAction::TYPE_COMPLETE_ORDER) {
                $this->OrderService->confirm($order->id);
                $this->AdminActionRepo->createByApplicable($order, [
                    'admin_id' => \Auth::id(),
                    'type' => AdminAction::TYPE_COMPLETE_ORDER,
                    'description' => $values['description'],
                ]);
            } elseif ($values['action'] === AdminAction::TYPE_CANCEL_ORDER) {
                $this->OrderService->cancel(
                    $order->dst_user,
                    $order->id,
                    AdminAction::class
                );
                $this->AdminActionRepo->createByApplicable($order, [
                    'admin_id' => \Auth::id(),
                    'type' => AdminAction::TYPE_CANCEL_ORDER,
                    'description' => $values['description'],
                ]);
            }
        });

        # send notification and add order count
        if ($values['action'] === AdminAction::TYPE_COMPLETE_ORDER) {
            $order->src_user->notify(new OrderCompletedSrcNotification($order));
            $order->dst_user->notify(new OrderCompletedNotification($order));
            FcmOrderCompletedSrcNotification::dispatch($order->src_user, $order)->onQueue(config('services.fcm.queue_name'));
            FcmOrderCompletedNotification::dispatch($order->dst_user, $order)->onQueue(config('services.fcm.queue_name'));
            $this->UserRepo->updateOrderCount($order->dst_user, true);
            $this->UserRepo->updateOrderCount($order->src_user, false);
        } elseif ($values['action'] === AdminAction::TYPE_CANCEL_ORDER) {
            $order->src_user->notify(new OrderCanceledNotification($order, AdminAction::class));
            $order->dst_user->notify(new OrderCanceledNotification($order, AdminAction::class));
            FcmOrderCanceledNotification::dispatch($order->src_user, $order, AdminAction::class)->onQueue(config('services.fcm.queue_name'));
            FcmOrderCanceledNotification::dispatch($order->dst_user, $order, AdminAction::class)->onQueue(config('services.fcm.queue_name'));
            $this->UserRepo->updateOrderCount($order->dst_user, false);
        }

        return redirect()->route('admin.orders.index')->with('flash_message', ['message' => '訂單資料操作成功']);
    }

    public function getOrders(OrderSearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $status = data_get($values, 'status');
        $from = Carbon::parse(data_get($values, 'from', 'today -10 days'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();

        if ($status !== 'all') {
            $condition = $this->searchConditionWithTimeInterval(
                [['status', '=', $status]],
                'created_at',
                $from,
                $to
            );
        } else {
            $condition = $this->timeIntervalCondition('created_at', $from, $to);
        }
        $query = $this->OrderRepo->queryOrder($condition, $keyword);
        $total = $this->OrderRepo->getOrdersCount();
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->amount = formatted_coin_amount($item->amount);
                $item->fee = formatted_coin_amount($item->fee);
                return $item;
            });
        return $this->draw(
            $this->result(
                $total,
                $filtered,
                $data
            )
        );
    }
}
