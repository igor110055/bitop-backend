<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Log;
use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
    TimeConditionTrait,
};
use App\Notifications\AdvertisementUnavailableNotification;
use App\Http\Requests\Admin\{
    AdvertisementSearchRequest,
};
use App\Repos\Interfaces\{
    AdvertisementRepo,
    AdminActionRepo,
};
use App\Models\{
    Advertisement,
    AdminAction,
};
use App\Services\AdvertisementServiceInterface;

class AdvertisementController extends AdminController
{
    use DataTableTrait, TimeConditionTrait;

    public function __construct(
        AdvertisementRepo $AdvertisementRepo,
        AdminActionRepo $AdminActionRepo,
        AdvertisementServiceInterface $AdvertisementService
    ) {
        parent::__construct();
        $this->AdvertisementRepo = $AdvertisementRepo;
        $this->AdminActionRepo = $AdminActionRepo;
        $this->AdvertisementService = $AdvertisementService;
        $this->tz = config('core.timezone.default');

        $this->middleware(
            ['can:edit-advertisements'],
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
        return view('admin.advertisements', [
            'from' => Carbon::parse('today -1 month', $this->tz)->format($dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($dateFormat),
            'status' => [
                'all' => 'All',
                Advertisement::STATUS_AVAILABLE => 'Available',
                Advertisement::STATUS_UNAVAILABLE => 'Unavailable',
                Advertisement::STATUS_COMPLETED => 'Completed',
                Advertisement::STATUS_DELETED => 'Deleted',
            ],
            'express' => [
                'all' => 'All',
                '0' => '一般交易',
                '1' => '快捷交易',
            ],
            'coins' => $coins,
        ]);
    }

    public function show(string $advertisement)
    {
        $advertisement = Advertisement::find($advertisement);
        if ($advertisement->deleted_at) {
            $info = [];
            $info['deleted_at'] = datetime($advertisement->deleted_at);
            if ($admin_action = $advertisement->admin_actions()->first()) {
                $info['action'] = 'Admin';
                $info['admin'] = $admin_action->admin_id;
                $info['description'] = $admin_action->description;
            } else {
                $info['action'] = 'User';
            }
        }

        return view('admin.advertisement', [
            'advertisement' => $advertisement,
            'bank_accounts' => $advertisement->bank_accounts,
            'delete_info' => $info ?? null,
        ]);

    }

    public function update(Advertisement $advertisement, Request $request)
    {
        $description = $request->input('description');
        DB::transaction(function () use ($advertisement, $description) {
            $this->AdvertisementService->deactivate(
                $advertisement->owner,
                $advertisement
            );
            $this->AdminActionRepo->createByApplicable($advertisement, [
                'admin_id' => \Auth::id(),
                'type' => AdminAction::TYPE_UNAVAILABLE_ADVERTISEMENT,
                'description' => $description,
            ]);
        });

        # send notification
        $advertisement->owner->notify(new AdvertisementUnavailableNotification($advertisement, AdminAction::class));

        return redirect()->route('admin.advertisements.index')->with('flash_message', ['message' => '廣告資料操作成功']);

    }

    public function getAdvertisements(AdvertisementSearchRequest $request)
    {
        $values = $request->validated();
        $from = Carbon::parse(data_get($values, 'from', 'today -1 months'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $keyword = data_get($values, 'search.value');
        $status = data_get($values, 'status');
        $is_express = data_get($values, 'is_express');
        $coin = data_get($values, 'coin');

        $condition = [];

        if ($status !== 'all') {
            $condition[] = ['status', '=', $status];
        }
        if ($coin !== 'All') {
            $condition[] = ['coin', '=', $coin];
        }
        if (!empty(data_get($values, 'from'))) {
            $from = Carbon::parse(data_get($values, 'from'), $this->tz);
            $condition[] = ['created_at', '>=', $from];
        }
        if (!empty(data_get($values, 'to'))) {
            $to = Carbon::parse(data_get($values, 'to'), $this->tz)->addDay();
            $condition[] = ['created_at', '<', $to];
        }
        if ($is_express === '1') {
            $condition[] = ['is_express', '=', true];
        } elseif ($is_express === '0') {
            $condition[] = ['is_express', '=', false];
        }

        $query = $this->AdvertisementRepo->queryAdvertisement($condition, $keyword);
        $total = $this->AdvertisementRepo->countAll();
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->map(function ($item) {
                $item->remaining_amount = formatted_coin_amount($item->remaining_amount);
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
