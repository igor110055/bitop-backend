<?php

namespace App\Repos\DB;

use DateTimeInterface;
use Carbon\Carbon;

use App\Models\{
    ExportLog,
};

class ExportLogRepo implements \App\Repos\Interfaces\ExportLogRepo
{
    protected $export_log;

    public function __construct(ExportLog $export_log) {
        $this->export_log = $export_log;
    }

    public function find($id)
    {
        return $this->export_log->find($id);
    }

    public function findOrFail($id)
    {
        return $this->export_log->findOrFail($id);
    }

    public function update(ExportLog $log, array $values)
    {
        $this->export_log
            ->where('id', data_get($log, 'id', $log))
            ->update($values);
    }

    public function countAll()
    {
        return $this->export_log
            ->count();
    }

    public function queryExportLogs($where = [], $keyword = null)
    {
        return $this->export_log
            ->when($where, function($query, $where){
                return $query->where($where);
            })
            ->when(($keyword and is_string($keyword)), function($query) use ($keyword) {
                return $query->where(function ($query) use ($keyword) {
                    $like = "%{$keyword}%";
                    return $query
                        ->orWhere('id', 'like', $like)
                        ->orWhere('loggable_id', 'like', $like);
                });
            })
            ->orderBy('id', 'desc');
    }

    public function getAllPending()
    {
        $to = Carbon::now();
        $from = $to->copy()->subDay();

        return $this->export_log
            ->whereNull('confirmed_at')
            ->where('created_at', '>', $from)
            ->where('created_at', '<=', $to)
            ->get();
    }
}
