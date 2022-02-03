<?php

namespace App\Services;

interface ExportServiceInterface
{
    public function createWithdrawalLog($withdrawal);
    public function createDepositLog($deposit);
    public function createOrderLogs($order);
    public function submit($export_log);
    public function formatData($data);
}
