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
    Advertisement,
};
use App\Repos\Interfaces\{
    OrderRepo,
    AdminActionRepo,
    UserRepo,
    WftransferRepo,
};
use App\Services\OrderServiceInterface;
use App\Notifications\{
    OrderCanceledNotification,
    OrderCompletedNotification,
    OrderCompletedSrcNotification,
};
use App\Jobs\PushNotification\{
    OrderCanceledNotification as PushOrderCanceledNotification,
    OrderCompletedNotification as PushOrderCompletedNotification,
    OrderCompletedSrcNotification as PushOrderCompletedSrcNotification,
};

class OrderController extends AdminController
{
    use DataTableTrait, TimeConditionTrait;

    public function __construct(
        OrderRepo $OrderRepo,
        AdminActionRepo $AdminActionRepo,
        UserRepo $UserRepo,
        WftransferRepo $WftransferRepo,
        OrderServiceInterface $OrderService
    ) {
        parent::__construct();
        $this->OrderRepo = $OrderRepo;
        $this->AdminActionRepo = $AdminActionRepo;
        $this->UserRepo = $UserRepo;
        $this->WftransferRepo = $WftransferRepo;
        $this->OrderService = $OrderService;
        $this->tz = config('core.timezone.default');

        $this->middleware(
            ['can:edit-orders'],
            ['only' => [
                'update',
            ]]
        );
    }

    public function index()
    {
        $dateFormat = 'Y-m-d';
        $coins = array_merge(['All'], array_keys(config('coin')));
        $coins = array_combine($coins, $coins);
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
            'express' => [
                'all' => 'All',
                '0' => '一般交易',
                '1' => '快捷交易',
            ],
            'coins' => $coins,
        ]);
    }

    public function show(Order $order)
    {
        if ($order->canceled_at) {
            $info = [];
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

        if ($order->is_express) {
            if ($order->advertisement->type === Advertisement::TYPE_SELL) {
                $action = 'express-buy';
            } else {
                $action = 'express-sell';
            }
        } else {
            if ($order->advertisement->type === Advertisement::TYPE_SELL) {
                $action = 'buy';
            } else {
                $action = 'sell';
            }
        }

        return view('admin.order', [
            'order' => $order,
            'action' => $action,
            'src_user' => $order->src_user,
            'dst_user' => $order->dst_user,
            'ad' => $order->advertisement,
            'ad_owner' => $order->advertisement->owner,
            'bank_accounts' => $order->bank_accounts,
            'wfpayments' => $order->wfpayments,
            'wftransfers' => $order->wftransfers,
            'payment_dst' => $order->payment_dst,
            'payment_src' => $order->payment_src,
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
            } elseif ($values['action'] === AdminAction::TYPE_NEW_ORDER_TRANSFER) {
                if (!$order->is_express) {
                    throw new BadRequestError;
                }
                $wftransfer = $this->WftransferRepo
                    ->createByOrder($order);
                $order->payment_src()->associate($wftransfer);
                $order->save();
                # Send the transfer
                try {
                    $wftransfer = $this->WftransferRepo
                        ->send($wftransfer);
                } catch (\Throwable $e) {
                    \Log::alert("Send wftransfer {$wftransfer->id} failed. ". $e->getMessage());
                }
                $this->AdminActionRepo->createByApplicable($wftransfer, [
                    'admin_id' => \Auth::id(),
                    'type' => AdminAction::TYPE_NEW_ORDER_TRANSFER,
                    'description' => $values['description'],
                ]);
            }
        });

        # send notification and add order count
        if ($values['action'] === AdminAction::TYPE_COMPLETE_ORDER) {
            $order->src_user->notify(new OrderCompletedSrcNotification($order));
            $order->dst_user->notify(new OrderCompletedNotification($order));
            PushOrderCompletedSrcNotification::dispatch($order->src_user, $order)->onQueue(config('services.push_notification.queue_name'));
            PushOrderCompletedNotification::dispatch($order->dst_user, $order)->onQueue(config('services.push_notification.queue_name'));
            $this->UserRepo->updateOrderCount($order->dst_user, true);
            $this->UserRepo->updateOrderCount($order->src_user, false);
        } elseif ($values['action'] === AdminAction::TYPE_CANCEL_ORDER) {
            $order->src_user->notify(new OrderCanceledNotification($order, AdminAction::class));
            $order->dst_user->notify(new OrderCanceledNotification($order, AdminAction::class));
            PushOrderCanceledNotification::dispatch($order->src_user, $order, AdminAction::class)->onQueue(config('services.push_notification.queue_name'));
            PushOrderCanceledNotification::dispatch($order->dst_user, $order, AdminAction::class)->onQueue(config('services.push_notification.queue_name'));
            $this->UserRepo->updateOrderCount($order->dst_user, false);
        }

        return redirect()->route('admin.orders.show', ['order' => $order])->with('flash_message', ['message' => '訂單資料操作成功']);
    }

    public function getOrders(OrderSearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $status = data_get($values, 'status');
        $from = Carbon::parse(data_get($values, 'from', 'today -10 days'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $is_express = data_get($values, 'is_express');
        $coin = data_get($values, 'coin');
        $sorting = null;

        $sort_map = [
            0 => 'created_at',
            1 => 'id',
            2 => 'is_express',
            3 => 'src_user_id',
            4 => 'dst_user_id',
            5 => 'coin',
            6 => 'amount',
            7 => 'total',
            8 => 'unit_price',
            9 => 'status',
            10 => 'completed_at',
        ];
        $column_key = data_get($values, 'order.0.column');
        if (array_key_exists($column_key, $sort_map)) {
            $sorting = [
                'column' => $sort_map[$column_key],
                'dir' => data_get($values, 'order.0.dir'),
            ];
        }

        $condition = [];
        if ($status !== 'all') {
            $condition[] = ['status', '=', $status];
        }
        if ($coin !== 'All') {
            $condition[] = ['coin', '=', $coin];
        }
        $condition[] = ['created_at', '>=', $from];
        $condition[] = ['created_at', '<', $to];
        if ($is_express === '1') {
            $condition[] = ['is_express', '=', true];
        } elseif ($is_express === '0') {
            $condition[] = ['is_express', '=', false];
        }

        $query = $this->OrderRepo->queryOrder($condition, $keyword, null, $sorting);
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
