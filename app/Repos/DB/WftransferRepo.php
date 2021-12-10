<?php

namespace App\Repos\DB;

use App\Exceptions\{
    Core\BadRequestError,
    UnavailableStatusError,
    VendorException,
};

use App\Models\{
    Order,
    User,
    Wftransfer,
};

use App\Services\{
    WfpayServiceInterface,
};

class WftransferRepo implements \App\Repos\Interfaces\WftransferRepo
{
    public function __construct(
        Wftransfer $wftransfer,
        WfpayServiceInterface $WfpayService
    ) {
        $this->wftransfer = $wftransfer;
        $this->WfpayService = $WfpayService;
    }

    public function find($id)
    {
        return $this->wftransfer->find($id);
    }

    public function findForUpdate($id)
    {
        return $this->wftransfer
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function findOrFail($id)
    {
        return $this->wftransfer->findOrFail($id);
    }

    public function findByRemoteId(string $remote_id)
    {
        return $this->wftransfer
            ->where('remote_id', $remote_id)
            ->first();
    }

    public function update(Wftransfer $wftransfer, array $values)
    {
        return $wftransfer->update($values);
    }

    protected function create(array $values)
    {
        return $this->wftransfer->create($values);
    }

    public function createByOrder(Order $order)
    {
        $bank_account = $order->bank_accounts->first();
        $data = [
            'order_id' => $order->id,
            'bank_account_id' => data_get($bank_account, 'id'),
            'status' => Wftransfer::STATUS_INIT,
            'total' => $order->total,
            'account_name' => config('services.wfpay.account'),
        ];
        return $this->create($data);
    }

    public function getTheLatestByOrder(Order $order)
    {
        return $this->wftransfer
            ->where('order_id', $order->id)
            ->latest()
            ->first();
    }

    public function send(Wftransfer $wftransfer)
    {
        $bank_account = $wftransfer->bank_account;
        if (is_null($bank_account)) {
            throw new UnavailableStatusError;
        }
        try {
            $result = $this->WfpayService
                ->createTranfer(
                    $wftransfer->id,
                    $wftransfer->total,
                    $wftransfer->callback_url,
                    $bank_account->bank_name,
                    $bank_account->bank_province_name,
                    $bank_account->bank_city_name,
                    $bank_account->account,
                    $bank_account->type,
                    $bank_account->name
                );
        } catch (BadRequestError $e) {
            $json = $e->getMessage();
            $wftransfer = $this->update($wftransfer, [
                'response' => $json,
                'submitted_at' => millitime(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            $wftransfer = $this->update($wftransfer, [
                'submitted_at' => millitime(),
            ]);
            throw $e;
        }

        $update = [
            'remote_id' => data_get($result, 'id'),
            'status' => data_get($result, 'status', 'init'),
            'merchant_fee' => data_get($result, 'merchant_fee'),
            'response' => json_encode($result),
            'submitted_at' => millitime(),
        ];
        $this->update($wftransfer, $update);

        return $wftransfer->fresh();
    }

    public function getAllPending()
    {
        $status = Wftransfer::$status_need_update;
        return $this->wftransfer
            ->whereIn('status', $status)
            ->whereNull('closed_at')
            ->get();
    }
}
