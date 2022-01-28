<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Repos\Interfaces\ExportLogRepo;
use App\Http\Controllers\Admin\Traits\{
    DataTableTrait,
    TimeConditionTrait,
};
use App\Http\Requests\Admin\SearchRequest;
use App\Models\{
    ExportLog,
};
use App\Services\ExportServiceInterface;

class ExportLogController extends AdminController
{
    use DataTableTrait, TimeConditionTrait;

    public function __construct(
        ExportLogRepo $ExportLogRepo,
        ExportServiceInterface $ExportService
    ) {
        $this->ExportLogRepo = $ExportLogRepo;
        $this->ExportService = $ExportService;
        $this->tz = config('core.timezone.default');
        $this->dateFormat = 'Y-m-d';
    }

    public function index()
    {
        return view('admin.export_logs', [
            'from' => Carbon::parse('today - 3 months', $this->tz)->format($this->dateFormat),
            'to' => Carbon::parse('today', $this->tz)->format($this->dateFormat),
        ]);
    }

    public function search(SearchRequest $request)
    {
        $values = $request->validated();
        $keyword = data_get($values, 'search.value');
        $from = Carbon::parse(data_get($values, 'from', 'today - 3 months'), $this->tz);
        $to = Carbon::parse(data_get($values, 'to', 'today'), $this->tz)->addDay();
        $condition = $this->timeIntervalCondition('created_at', $from, $to);
        $query = $this->ExportLogRepo
            ->queryExportLogs($condition, $keyword);
        $total = $this->ExportLogRepo->countAll();
        $filtered = $query->count();
        $data = $this->queryPagination($query, $total);

        return $this->draw(
            $this->result(
                $total,
                $filtered,
                $data
            )
        );
    }

    public function submit(Request $request)
    {
        $export_log = $this->ExportLogRepo->findOrFail($request->input('id'));
        $this->ExportService->submit($export_log);
        return response('1', 200);
    }
}
