<?php

namespace App\Repos\DB;

use App\Exceptions\{
    Core\BadRequestError,
    ServiceUnavailableError,
    UnavailableStatusError,
};

use App\Models\{
    Order,
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
            'wfpay_account_id' => config('services.wfpay.account'),
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

        $WfpayAccountRepo = app()->make(WfpayAccountRepo::class);
        $wfpay_accounts = $WfpayAccountRepo->getByTransferRank();

        foreach ($wfpay_accounts as $wfpay_account) {
            try {
                $result = $this->WfpayService
                    ->createTransfer(
                        $wfpay_account,
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
                break;
            } catch (BadRequestError $e) {
                # Todo: create logs.
                $json = $e->getMessage();
                $array = json_decode($json, true);
                if (!is_array($array)) {
                    $array = ['response' => $json];
                }
                $array['wfpay_account_id'] = $wfpay_account->id;
                $errors = data_get($wftransfer, 'errors', []);
                $errors[] = $array;
                $this->update($wftransfer, [
                    'wfpay_account_id' => $wfpay_account->id,
                    'errors' => $errors,
                    'submitted_at' => millitime(),
                ]);
                $wftransfer->fresh();
                # Check remote if transfer exists.
                try {
                    $remote = $this->WfpayService->getTransfer($wftransfer);
                } catch (\Throwable $e) {
                    $msg = $e->getMessage();
                    $msg = json_decode($msg, true);
                    if (data_get($msg, 'error_key') === 'order_not_found') {
                        # transfer doesn't exist, try next account
                        continue;
                    } else {
                        throw $e;
                    }
                }
            } catch (\Throwable $e) {
                $this->update($wftransfer, [
                    'wfpay_account_id' => $wfpay_account->id,
                    'submitted_at' => millitime(),
                ]);
                throw $e;
            }
        }

        if (!isset($result)) {
            # TODO: notify admins
            throw new ServiceUnavailableError("Send wftransfer {$wftransfer->id} failed with all accounts");
        }

        $update = [
            'wfpay_account_id' => $wfpay_account->id,
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
