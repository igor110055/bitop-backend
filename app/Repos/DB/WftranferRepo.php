<?php

namespace App\Repos\DB;

use App\Exceptions\{
    Core\BadRequestError,
    VendorException,
};

use App\Models\{
    Order,
    User,
    Wftranfer,
};

use App\Services\{
    WfpayServiceInterface,
};

class WftranferRepo implements \App\Repos\Interfaces\WftranferRepo
{
    protected $log;

    public function __construct(
        Wftranfer $wftranfer,
        WfpayServiceInterface $WfpayService
    ) {
        $this->wftranfer = $wftranfer;
        $this->WfpayService = $WfpayService;
    }

    public function find($id)
    {
        return $this->wftranfer->find($id);
    }

    public function findForUpdate($id)
    {
        return $this->wftranfer
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function findOrFail($id)
    {
        return $this->wftranfer->findOrFail($id);
    }

    public function findByRemoteId(string $remote_id)
    {
        return $this->wftranfer
            ->where('remote_id', $remote_id)
            ->first();
    }

    public function update(Wftranfer $wftranfer, array $values)
    {
        return $wftranfer->update($values);
    }

    protected function create(array $values)
    {
        return $this->wftranfer->create($values);
    }

    protected function getRemoteInfo(Wftransfer $wftranfer)
    {
        $bank_account = $wftranfer->bank_account;
        try {
            $result = $this->WfpayService
                ->createTranfer(
                    $wftranfer->id,
                    $wftranfer->total,
                    $wftranfer->callback_url,
                    $bank_account->bank_name,
                    $bank_account->bank_province_name,
                    $bank_account->bank_city_name,
                    $bank_account->account,
                    $bank_account->type,
                    $bank_account->name
                );
        } catch (BadRequestError $e) {
            $json = $e->getMessage();
            $wftranfer = $this->update($wftranfer, ['response' => $json]);
            throw new BadRequestError;
        }

        return $result;
    }

    protected function createWithRemoteInfo(array $values)
    {
        $wftranfer = $this->create($values);
        $remote = $this->getRemoteInfo($wftranfer);
        $update = [
            'remote_id' => data_get($remote, 'id'),
            'status' => data_get($remote, 'status'),
            'merchant_fee' => data_get($remote, 'merchant_fee'),
            'response' => json_encode($remote),
        ];
        $this->update($wftranfer, $update);

        return $wftranfer->fresh();
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
        return $this->createWithRemoteInfo($data);
    }

    public function getTheLatestByOrder(Order $order)
    {
        return $this->wftransfer
            ->where('order_id', $order->id)
            ->latest()
            ->first();
    }
}
