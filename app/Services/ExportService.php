<?php

namespace App\Services;

use Dec\Dec;
use Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\{ClientException, RequestException};
use App\Exceptions\{
    Core\UnknownError,
    Core\BadRequestError,
    VendorException,
};

use App\Models\{
    ExportLog,
    Transaction,
    User,
    Advertisement,
    Wfpayment,
    Wftransfer,
};
use App\Repos\Interfaces\{
    ExportLogRepo,
};

class ExportService implements ExportServiceInterface
{
    public function __construct(
        ExportLogRepo $ExportLogRepo,
        ExchangeServiceInterface $ExchangeService
    ) {
        $this->ExportLogRepo = $ExportLogRepo;
        $this->ExchangeService = $ExchangeService;
        $this->coins = config('coin');
        $this->currencies = config('currency');
        $this->base_currency = config('core.currency.base');
        $this->currency_decimal = config('core.currency.default_exp');
        $this->coin_decimal = config('core.coin.default_exp');
        $this->coin_types = ExportLog::COIN_TYPES;
        $this->order_sell_accounts = ExportLog::ORDER_SELL_ACCOUNTS;
        $this->member_accounts = ExportLog::MEMBER_ACCOUNTS;
        $this->link = config('services.export_log.link');
    }

    public function createWithdrawalLog($withdrawal)
    {
        $transaction = $withdrawal->transactions()
            ->where('type', Transaction::TYPE_WALLET_WITHDRAWAL)
            ->latest()
            ->first();

        $coin_price = $this->ExchangeService
            ->coinToCurrency(
                $withdrawal->user,
                $withdrawal->coin,
                $this->base_currency,
                null,
                1
            );

        $unit_price = $coin_price['unit_price'];
        $total = (string) Dec::mul($withdrawal->amount, $unit_price, $this->currency_decimal);
        $amount = (string) Dec::mul($withdrawal->amount, -1, $this->coin_decimal);
        $data = [
            'user_id' => $withdrawal->user_id,
            'transaction_id' => data_get($transaction, 'id'),
            'account' => data_get($this->member_accounts, $withdrawal->coin),
            'amount' => $total,
            'coin' => $amount,
            'bank_fee' => '0.000',
            'system_fee' => '0.000',
            'c_fee' => $withdrawal->fee,
            'type' => data_get($this->coin_types, $withdrawal->coin),
            'bankc_fee' => $unit_price,
            'handler_id' => $withdrawal->user_id,
        ];

        return $withdrawal->export_logs()->create($data);
    }

    public function createDepositLog($deposit)
    {
        $transaction = $deposit->transactions()
            ->where('type', Transaction::TYPE_WALLET_DEPOSIT)
            ->latest()
            ->first();

        $coin_price = $this->ExchangeService
            ->coinToCurrency(
                $deposit->user,
                $deposit->coin,
                $this->base_currency,
                null,
                1
            );

        $unit_price = $coin_price['unit_price'];
        $total = (string) Dec::mul($deposit->amount, $unit_price, $this->currency_decimal);
        $amount = (string) Dec::mul($deposit->amount, 1, $this->coin_decimal);
        $data = [
            'user_id' => $deposit->user_id,
            'transaction_id' => data_get($transaction, 'id'),
            'account' => data_get($this->member_accounts, $deposit->coin),
            'amount' => $total,
            'coin' => $amount,
            'bank_fee' => '0.000',
            'system_fee' => '0.000',
            'type' => data_get($this->coin_types, $deposit->coin),
            'bankc_fee' => $unit_price,
            'handler_id' => $deposit->user_id,
        ];

        return $deposit->export_logs()->create($data);
    }

    public function createOrderLogs($order)
    {
        # only export express order's log
        if (!$order->is_express) {
            return;
        }
        $advertisement = $order->advertisement;
        $account = null;
        if ($advertisement->type === Advertisement::TYPE_SELL) {
            $type = 'express-buy';
            $wfpayment = $order->payment_src;
            if (!is_null($wfpayment) and ($wfpayment instanceof Wfpayment)) {
                $wfpay_account = $wfpayment->wfpay_account;
                $account = $wfpay_account->id;
            }
        } else {
            $type = 'express-sell';
            $wftransfer = $order->payment_src;
            if (!is_null($wftransfer) and ($wftransfer instanceof Wftransfer)) {
                $wfpay_account = $wftransfer->wfpay_account;
                $account = $wfpay_account->id;
            }
        }

        # seller transaction
        $transaction = $order->transactions()
            ->where('type', Transaction::TYPE_SELL_ORDER)
            ->latest()
            ->first();

        $unit_price = $order->unit_price;
        $total = (string) Dec::mul($order->total, 1, $this->currency_decimal);
        $negative_total = (string) Dec::mul($order->total, -1, $this->currency_decimal);
        $amount = (string) Dec::mul($order->amount, 1, $this->coin_decimal);
        $fee = (string) Dec::mul(data_get($order, 'fee', 0), $unit_price, $this->coin_decimal);
        $data = [
            'user_id' => $order->src_user_id,
            'transaction_id' => data_get($transaction, 'id'),
            'account' => ($type === 'express-buy') ? data_get($this->order_sell_accounts, $order->coin) : data_get($this->member_accounts, $order->coin),
            'amount' => $negative_total,
            'coin' => $amount,
            'bank_fee' => '0.000',
            'system_fee' => '0.000',
            'c_fee' => $order->fee,
            'type' => data_get($this->coin_types, $order->coin),
            'bankc_fee' => $unit_price,
            'handler_id' =>  $order->src_user_id,
        ];

        $order->export_logs()->create($data);

        # buyer's 1st transaction
        $transaction = $order->transactions()
            ->where('type', Transaction::TYPE_BUY_ORDER)
            ->latest()
            ->first();

        $data = [
            'user_id' => $order->dst_user_id,
            'transaction_id' => data_get($transaction, 'id'),
            'account' => ($type === 'express-sell') ? data_get($this->order_sell_accounts, $order->coin) : data_get($this->member_accounts, $order->coin),
            'amount' => $total,
            'coin' => $amount,
            'bank_fee' => '0.000',
            'system_fee' => '0.000',
            'type' => data_get($this->coin_types, $order->coin),
            'bankc_fee' => $unit_price,
            'handler_id' =>  $order->dst_user_id,
        ];

        $order->export_logs()->create($data);

        # buyer's 2nd transaction
        $data = [
            'user_id' => $order->dst_user_id,
            'transaction_id' => data_get($transaction, 'id'),
            'account' => $account,
            'amount' => ($type === 'express-buy') ? $total : $negative_total,
            'coin' => $amount,
            'bank_fee' => '0.000',
            'system_fee' => '0.000',
            'type' => ($type === 'express-buy') ? '3' : 'W',
            'bankc_fee' => $unit_price,
            'handler_id' =>  $order->dst_user_id,
        ];

        $order->export_logs()->create($data);
    }

    public function submit($export_log) {
        $now = millitime();
        $update = [
            'submitted_at' => $now,
        ];
        try {
            $headers = [];
            $method = 'POST';
            $body = $this->formatData($export_log);
            $request = new Request($method, $this->link, $headers, $body);
            $client = new Client();
            $response = $client->send($request);
            if ((string) $response->getBody() == 'ok') {
                $update['confirmed_at'] = $now;
            } else {
                \Log::alert('ExportService/Submit response is not "ok", Response: '.(string) $response->getBody().', Request: '. (string) $request->getBody());
            }
        } catch (ClientException $e) { # status code 4xx
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
            $body = (string) $response->getBody();
            $message = "Error {$status_code} {$reason} {$body} from vendor.";

            \Log::alert("ExportService/submit request error. {$message}");
            throw new BadRequestError($message);
        } catch (RequestException $e) { # status code 5xx
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $status_code = $response->getStatusCode();
                $reason = $response->getReasonPhrase();
                $body = (string) $response->getBody();
                $message = "Error {$status_code} {$reason} {$body} from vendor.";

                \Log::alert("ExportService/submit request error. {$message}");
                throw new VendorException("Error $message from vendor.");
            } else {
                $reporting_data['error_message'] = $e->getMessage();
                \Log::alert("ExportService vendor error. Unknown error", $reporting_data);
                throw new VendorException($e->getMessage());
            }
        } catch (\Throwable $e) {
            $reporting_data['error_message'] = $e->getMessage();
            \Log::alert("ExportService unknown error.", $reporting_data);
            throw $e;
        }

        $this->ExportLogRepo->update($export_log, $update);
    }

    public function formatData($data)
    {
        $formatted_data = [
            "SerialNumber" => $data->serial,
            "LoginID" => $data->user_id,
            "Account" => $data->account,
            "Amount" => $data->amount,
            "BankFee" => $data->bank_fee,
            "SystemFee" => $data->system_fee,
            "cfee" => $data->c_fee,
            "type" => $data->type,
            "Coin" => $data->coin,
            "BankcFee" => $data->bankc_fee,
            "ConfirmTime" => $data->created_at->timestamp,
        ];
        return json_encode([$formatted_data]);
    }
}
