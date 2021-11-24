<?php

namespace App\Repos\DB;

use App\Exceptions\{
    Core\BadRequestError,
    VendorException,
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
    protected $log;

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

    public function createWithPaymentInfo(array $values)
    {
        $wfpayment = $this->create($values);
        try {
            $remote = $this->getPaymentInfo($wfpayment);
        } catch (BadRequestError $e) {
            $json = $e->getMessage();
            $wfpayment = $this->update($wfpayment, ['response' => $json]);
            $data = json_decode($json);
            throw new BadRequestError(data_get($data, 'message'));
        } catch (\Throwable $e) {
            throw $e;
            throw new VendorException;
        }
        $update = [
            'remote_id' => data_get($remote, 'id'),
            'status' => data_get($remote, 'status'),
            'guest_payment_amount' => data_get($remote, 'guest_payment_amount'),
            'payment_url' => data_get($remote, 'payment_url'),
            'merchant_fee' => data_get($remote, 'merchant_fee'),
            'response' => json_encode($remote),
        ];
        $bank_account_info = data_get($remote, 'bank_account');
        if (!is_null($bank_account_info) && is_array($bank_account_info)) {
            $update['payment_info'] = $bank_account_info;
        }
        $this->update($wfpayment, $update);

        return $wfpayment->fresh();
    }

    public function getPaymentInfo(Wfpayment $wfpayment)
    {
        $force_matching = ($wfpayment->payment_method === Wfpayment::METHOD_BANK);
        $result = $this->WfpayService
            ->createPayment(
                $wfpayment->id,
                $wfpayment->total,
                $wfpayment->payment_method,
                $wfpayment->real_name,
                $wfpayment->callback_url,
                $wfpayment->return_url,
                $force_matching
            );

        if ($force_matching) {
            $tries = 2;
            while ($result['status'] === Wfpayment::STATUS_PENDINT_ALLOCATION && $tries > 0) {
                sleep(5);
                $result = $this->WfpayService
                    ->rematch($wfpayment->id);
                $tries--;
            }
        }

        return $result;
    }

    public function createByOrder(Order $order, $payment_method = 'bank')
    {
        $data = [
            'order_id' => $order->id,
            'status' => Wfpayment::STATUS_INIT,
            'total' => $order->total,
            'payment_method' => $payment_method,
            'real_name' => $order->dst_user->name,
            'account_name' => config('services.wfpay.account'),
        ];
        return $this->createWithPaymentInfo($data);
    }

    public function getTheLatestByOrder(Order $order)
    {
        return $this->wfpayment
            ->where('order_id', $order->id)
            ->latest()
            ->first();
    }
}
