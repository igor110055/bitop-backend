<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use DB;

use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
    TimeConditionTrait,
};
use App\Http\Requests\Admin\{
    AssetManipulationRequest,
    AssetTransactionsSearchRequest,
};
use App\Models\{
    Asset,
    AssetTransaction,
    Manipulation,
};
use App\Repos\Interfaces\{
    AssetRepo,
    AssetTransactionRepo,
};
use App\Services\{
    AssetServiceInterface,
};

class AssetController extends AdminController
{
    use DataTableTrait;

    public function __construct(
        AssetRepo $AssetRepo,
        AssetTransactionRepo $AssetTransactionRepo,
        AssetServiceInterface $AssetService
    ) {
        parent::__construct();
        $this->AssetRepo = $AssetRepo;
        $this->AssetTransactionRepo = $AssetTransactionRepo;
        $this->AssetService = $AssetService;
        $this->currency = "TWD";
        $this->tz = config('core.timezone.default');
        $this->middleware(['role:super-admin']);
    }

    public function show(Asset $asset)
    {
        $dateFormat = 'Y-m-d';
        $agency = $asset->agency;

        return view('admin.asset', [
            'base_currency' => $this->currency,
            'agency' => $agency,
            'asset' => $asset,
            'from' => Carbon::parse('today -30 days', $this->tz)->format($dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($dateFormat),
        ]);
    }

    public function createManipulation(Asset $asset)
    {
        $agency = $asset->agency;
        $types = [
            AssetTransaction::TYPE_MANUAL_DEPOSIT => '手動充值',
            AssetTransaction::TYPE_MANUAL_WITHDRAWAL => '手動提領',
        ];

        return view('admin.asset_manipulation', [
            'base_currency' => $this->currency,
            'agency' => $agency,
            'asset' => $asset,
            'types' => $types,
        ]);
    }

    public function storeManipulation(AssetManipulationRequest $request, Asset $asset)
    {
        $user = auth()->user();
        $values = $request->validated();
        $agency = $asset->agency;

        $this->AssetService->manipulate(
            $asset,
            $user,
            data_get($values, 'type'),
            data_get($values, 'amount'),
            data_get($values, 'unit_price'),
            data_get($values, 'note')
        );

        return redirect()->route('admin.assets.show', ['agency' => $asset->id])->with('flash_message', ['message' => '手動操作完成']);
    }

    public function getTransactions(AssetTransactionsSearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $from = Carbon::parse(data_get($values, 'from', 'today -30 days'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();

        $asset = $this->AssetRepo
            ->findOrFail($values['id']);

        $query = $this->AssetTransactionRepo
            ->getFilteringQuery($asset, $from, $to, $keyword, true);

        $total = $this->AssetTransactionRepo
            ->getAssetTransactionsCount($asset);
        $filtered = $query->count();

        $data = $this->queryPagination($query, $total)
            ->loadMorph('transactable', [
                Manipulation::class => ['user'],
            ]);
        return $this->draw(
            $this->result(
                $total,
                $filtered,
                $data
            )
        );
    }
}
