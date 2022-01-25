<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;

use App\Models\{
    ExportLog,
    User,
};

interface ExportLogRepo
{
    public function find($id);
    public function findOrFail($id);
    public function update(ExportLog $log, array $values);
    public function countAll();
    public function queryExportLogs($where = [], $keyword = null);
    public function getAllPending();
}
