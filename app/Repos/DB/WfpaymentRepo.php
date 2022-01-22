<?php

namespace App\Repos\DB;

use App\Exceptions\{
    Core\BadRequestError,
};

use App\Models\{
    Order,
    User,
    Wfpayment,
};

use App\Services\{
    WfpayServiceInterface,
};

class WfpaymentRepo implements \App\Repos\Interfaces\WfpaymentRepo
{
    public function __construct(
        Wfpayment $wfpayment,
        WfpayServiceInterface $WfpayService
    ) {
        $this->wfpayment = $wfpayment;
        $this->WfpayService = $WfpayService;
    }

    public function find($id)
    {
        return $this->wfpayment->find($id);
    }

    public function findForUpdate($id)
    {
        return $this->wfpayment
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function findOrFail($id)
    {
        return $this->wfpayment->findOrFail($id);
    }

    public function findByRemoteId(string $remote_id)
    {
        return $this->wfpayment
            ->where('remote_id', $remote_id)
            ->first();
    }

    public function update(Wfpayment $wfpayment, array $values)
    {
        return $wfpayment->update($values);
    }

    public function create(array $values)
    {
        return $this->wfpayment->create($values);
    }

    public function createRemote(Wfpayment $wfpayment)
    {
        $WfpayAccountRepo = app()->make(WfpayAccountRepo::class);
        $wfpay_accounts = $WfpayAccountRepo->getByRank();
        $force_matching = ($wfpayment->payment_method === Wfpayment::METHOD_BANK);

        if ($force_matching) {
            $responses = [];
            foreach ($wfpay_accounts as $account) {
                $supported_payment_methods = data_get($account, 'configs.payment_methods', []);
                if (!in_array($wfpayment->payment_method, $supported_payment_methods)) {
                    continue;
                }
                try {
                    $result = $this->WfpayService
                        ->createOrder(
                            $account,
                            $wfpayment->id,
                            $wfpayment->total,
                            $wfpayment->payment_method,
                            $wfpayment->real_name,
                            $wfpayment->callback_url,
                            $wfpayment->return_url,
                            $force_matching
                        );
                    if (!isset($first_result)) {
                        $first_account = $account;
                        $first_result = $result;
                    }
                    if ($result['status'] === Wfpayment::STATUS_PENDINT_ALLOCATION) {
                        continue;
                    } else {
                        $responses[] = [
                            'wfpay_account_id' => $account->id,
                            'response' => $result
                        ];
                        return [$result, $account, $responses];
                    }
                } catch (\Throwable $e) {
                    $json = $e->getMessage();
                    $responses[] = [
                        'wfpay_account_id' => $account->id,
                        'response' => $json
                    ];
                    continue;
                }
            }

            if (isset($first_result)) {
                return [$first_result, $first_account, $responses];
            }
            # All wfpay_account failed, and since it's in a transaction, no data will be saved. so we send alert here
            \Log::alert('WfpaymentRepo/createRemote, All wfpay_account failed.', $wfpayment);
            throw new BadRequestError;
        } else {
            $responses = [];
            foreach ($wfpay_accounts as $account) {
                $supported_payment_methods = data_get($account, 'configs.payment_methods', []);
                if (!in_array($wfpayment->payment_method, $supported_payment_methods)) {
                    continue;
                }
                try {
                    $result = $this->WfpayService
                        ->createOrder(
                            $account,
                            $wfpayment->id,
                            $wfpayment->total,
                            $wfpayment->payment_method,
                            $wfpayment->real_name,
                            $wfpayment->callback_url,
                            $wfpayment->return_url,
                            $force_matching
                        );
                    if (!isset($first_result)) {
                        $first_account = $account;
                        $first_result = $result;
                    }
                    $responses[] = [
                        'wfpay_account_id' => $account->id,
                        'response' => $result
                    ];
                    return [$result, $account, $responses];
                } catch (\Throwable $e) {
                    $json = $e->getMessage();
                    $responses[] = [
                        'wfpay_account_id' => $account->id,
                        'response' => $json
                    ];
                    continue;
                }
            }
            if (isset($first_result)) {
                return [$first_result, $first_account, $responses];
            }
            # All wfpay_account failed, and since it's in a transaction, no data will be saved. so we send alert here
            \Log::alert('WfpaymentRepo/createRemote, All wfpay_account failed.', $wfpayment);
            throw new BadRequestError;
        }
    }

    public function createByOrder(Order $order, $payment_method = 'bank')
    {
        $data = [
            'order_id' => $order->id,
            'status' => Wfpayment::STATUS_INIT,
            'total' => $order->total,
            'payment_method' => $payment_method,
            'real_name' => $order->dst_user->name,
        ];

        $wfpayment = $this->create($data);
        list($remote, $wfpay_account, $responses) = $this->createRemote($wfpayment);
        $update = [
            'wfpay_account_id' => $wfpay_account->id,
            'remote_id' => data_get($remote, 'id'),
            'status' => data_get($remote, 'status'),
            'guest_payment_amount' => data_get($remote, 'guest_payment_amount'),
            'payment_url' => data_get($remote, 'payment_url'),
            'merchant_fee' => data_get($remote, 'merchant_fee'),
            'response' => $responses,
        ];
        $bank_account_info = data_get($remote, 'bank_account');
        if (!is_null($bank_account_info) && is_array($bank_account_info)) {
            $update['payment_info'] = $bank_account_info;
        }
        $this->update($wfpayment, $update);

        return $wfpayment->fresh();
    }

    public function getTheLatestByOrder(Order $order)
    {
        return $this->wfpayment
            ->where('order_id', $order->id)
            ->latest()
            ->first();
    }

    public function getAllPending()
    {
        $status = Wfpayment::$status_need_update;
        return $this->wfpayment
            ->whereIn('status', $status)
            ->whereNull('closed_at')
            ->get();
    }
}
