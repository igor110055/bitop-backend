<?php

namespace App\Services;

use Carbon\Carbon;
use Dec\Dec;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\{ClientException, RequestException};
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Exceptions\{
    Core\UnknownError,
    Core\BadRequestError,
    WrongAddressFormatError,
    ServiceUnavailableError,
    VendorException,
};
use App\Exceptions\Exception as InternelServerError;

use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\{
    FeeSetting,
    Transaction,
};
use App\Repos\Interfaces\{
    AccountRepo,
    DepositRepo,
    WithdrawalRepo,
};

class WalletService implements WalletServiceInterface
{
    const BadSignatureHeaderError = 'E40000';
    const BadSignatureAlgorithmError = 'E40001';
    const BadSignatureError = 'E40002';
    const BadBodyError = 'E40003';
    const BadTimestampError = 'E40004';
    const BadBalanceError = 'E40010';
    const BadUtxoBalanceError = 'E40011';
    const UnauthenticatedError = 'E40100';
    const BadTokenHeaderError = 'E40001';
    const PermissionDeniedError = 'E40300';
    const BadAccountError = 'E40301';
    const NotFoundError = 'E40400';
    const NotFoundAccountError = 'E40401';
    const NotFoundCurrencyError = 'E40402';
    const NotFoundAddressError = 'E40403';
    const NotFoundDepositError = 'E40404';
    const NotFoundWithdrawalError = 'E40405';
    const ConflictError = 'E40900';
    const BadBalanceHeaderError = 'E41700';
    const BadParameterError = 'E42200';
    const BadAddressError = 'E42201';

    const AlertErrors = [
        self::BadBalanceError,
        self::BadUtxoBalanceError,
    ];

    const ErrorNames = [
        self::BadSignatureHeaderError => 'BadSignatureHeader',
        self::BadSignatureAlgorithmError => 'BadSignatureAlgorithm',
        self::BadSignatureError => 'BadSignature',
        self::BadBodyError => 'BadBody',
        self::BadTimestampError => 'BadTimestamp',
        self::BadBalanceError => 'BadBalance',
        self::BadUtxoBalanceError => 'BadUtxoBalance',
        self::UnauthenticatedError => 'Unauthenticated',
        self::BadTokenHeaderError => 'BadTokenHeaderError',
        self::PermissionDeniedError => 'PermissionDenied',
        self::BadAccountError => 'BadAccount',
        self::NotFoundError => 'NotFound',
        self::NotFoundAccountError => 'NotFoundAccount',
        self::NotFoundCurrencyError => 'NotFoundCurrency',
        self::NotFoundAddressError => 'NotFoundAddress',
        self::NotFoundDepositError => 'NotFoundDeposit',
        self::NotFoundWithdrawalError => 'NotFoundWithdrawal',
        self::ConflictError => 'Conflict',
        self::BadBalanceHeaderError => 'BadBalanceHeader',
        self::BadParameterError => 'BadParameter',
        self::BadAddressError => 'BadAddress',
    ];

    public function __construct(
        AccountRepo $AccountRepo,
        DepositRepo $DepositRepo,
        WithdrawalRepo $WithdrawalRepo,
        FeeServiceInterface $FeeService
    ) {
        $configs = config('services.wallet');
        $this->configs = $configs;
        $this->domain = data_get($configs, 'env') === 'mainnet' ? $configs['mainnet'] : $configs['testnet'];
        $this->account = $configs['account'];
        $this->token = $configs['token'];
        $this->key = $configs['key'];
        $this->coin_map = $configs['coin_map'];
        $this->coins = config('core.coin.all');
        $this->need_tag_coins = config('core.coin.require_tag');

        $this->AccountRepo = $AccountRepo;
        $this->DepositRepo = $DepositRepo;
        $this->WithdrawalRepo = $WithdrawalRepo;
        $this->FeeService = $FeeService;
    }

    public function serverTime()
    {
        $link = $this->publicLink('now');
        return $this->get($link);
    }

    public function getSupportedCoinList()
    {
        $link = $this->publicLink('currencies');
        return $this->get($link);
    }

    public function getSupportedErrors()
    {
        $link = $this->publicLink('errors');
        return $this->get($link);
    }

    public function getAddressValidation(string $coin, string $address)
    {
        $api_coin = $this->coin_map[$coin];
        $query = [
            'currency' => $api_coin,
            'payload' => $address,
        ];
        $link = $this->publicLink('address').'?'.http_build_query($query);
        try {
            return $this->get($link);
        } catch (BadRequestError $e) {
        } catch (VendorException $e) {
        }
    }

    public function getWithdrawalStats(string $coin)
    {
        $api_coin = $this->coin_map[$coin];
        $link = $this->link("{$api_coin}/withdrawals/stats");
        return $this->get($link, true);
    }

    public function createAddress($coin, $client_id, array $callback)
    {
        $api_coin = $this->coin_map[$coin];
        $link = ($coin === 'BTC') ? $this->link("{$api_coin}/addresses?type=p2pkh") : $this->link("{$api_coin}/addresses");
        $timestamp = data_get($this->serverTime(), 'timestamp');
        $data = [
            'client_id' => $client_id,
            'callback' => $callback,
            'timestamp' => $timestamp,
        ];
        return $this->post($link, $data);
    }

    public function getAddress($coin, $address, $tag = null)
    {
        $coin = $this->coin_map[$coin];
        $link = $this->link("{$coin}/addresses/{$address}/{$tag}");
        return $this->get($link, true);
    }

    public function getAllAddress($coin)
    {
        $coin = $this->coin_map[$coin];
        $link = $this->link("{$coin}/addresses");
        return $this->get($link, true);
    }

    public function updateAddressCallback($coin, $address, $tag = null, array $callback)
    {
        $coin = $this->coin_map[$coin];
        $link = $this->link("{$coin}/addresses/{$address}/{$tag}");
        $timestamp = data_get($this->serverTime(), 'timestamp');
        $data = [
            'callback' => $callback,
            'timestamp' => $timestamp,
        ];
        return $this->patch($link, $data);
    }

    public function getAllBalance()
    {
        $link = $this->link("balances");
        return $this->get($link, true);
    }

    public function getBalanceByCoin($coin)
    {
        $coin = $this->coin_map[$coin];
        $link = $this->link("balances/$coin");
        return $this->get($link, true);
    }

    public function checkInternalAddress($address, $coin)
    {
        if (in_array($coin, config('core.coin.support_internal_transfer'))) {
            try {
                $dryrun = $this->withdrawal(
                    $coin,
                    $address,
                    null, # tag
                    '0.0001',
                    url('nowhere'),
                    (string) Str::uuid(), # client_withdrawal_id,
                    true, # is_full_payment
                    true # dryrun
                );
                if (data_get($dryrun, 'type') === 'internal') {
                    return true;
                }
            } catch (BadRequestError $e) { # invalid address situation
                return false;
            } catch (Throwable $e) {
                Log::alert('WalletService/checkInternalAddress. unknown error: '. $e->getMessage());
            }
        }
        return false;
    }

    public function withdrawal($coin, $address, $tag, $amount, $callback, $client_withdrawal_id, $is_full_payment = true, $dryrun = false)
    {
        $coin = $this->coin_map[$coin];
        $link = $this->link("{$coin}/withdrawals");
        $timestamp = data_get($this->serverTime(), 'timestamp');
        $data = [
            'address' => $address,
            'tag' => $tag,
            'amount' => $amount,
            'is_full_payment' => $is_full_payment,
            'callback' => $callback,
            'client_id' => $client_withdrawal_id,
            'timestamp' => $timestamp,
            'dryrun' => $dryrun,
        ];
        return $this->post($link, $data);
    }

    public function getWithdrawal($coin, $transactoin, $client_withdrawal_id)
    {
        $coin = $this->coin_map[$coin];
        if (empty($transaction) and empty($client_withdrawal_id)) {
            throw new BadRequestError;
        } elseif (empty($transaction)) {
            $transaction = '_';
        }
        $link = $this->link("{$coin}/withdrawals/{$transaction}/{$client_withdrawal_id}");
        return $this->get($link, true);
    }

    public function getAllWithdrawals($coin)
    {
        $coin = $this->coin_map[$coin];
        $link = $this->link("{$coin}/withdrawals");
        return $this->get($link, true);
    }

    public function getAllDeposits($coin)
    {
        $coin = $this->coin_map[$coin];
        $link = $this->link("{$coin}/deposits");
        return $this->get($link, true);
    }

    protected function checkAmount($amount)
    {
        if (!validate_amount($amount)) { # validate_amount is in general helper
            Log::alert('WalletService/checkAmount. invalid amount: ' . $amount);
            throw new VendorException('Invalid amount.');
        }
    }

    protected function checkAccount($account)
    {
        if ($account !== $this->account) {
            Log::alert('WalletService/checkAccount. account inconsistent: ' . $account);
            throw new VendorException('Invalid account.');
        }
    }

    public function checkWalletInternalCallbackParameter(array $values)
    {
        $required_keys = [
            'id',
            'address',
            'account',
            'transaction',
            'currency',
            'fee',
        ];

        if (!array_keys_exists($required_keys, $values)) {
            Log::alert('checkWalletInternalCallbackParameter. required keys missing in response.', $values);
            throw new VendorException('Invalid wallet internal callback parameter.');
        }

        if (!array_keys_not_null($required_keys, $values)) {
            Log::alert('checkWalletInternalCallbackParameter. Some of the required value is null.', $values);
            throw new VendorException('Invalid wallet internal parameter.');
        }

        $this->checkAccount(data_get($values, 'account'));
        $this->checkAmount(data_get($values, 'fee'));
    }

    public function checkDepositCallbackParameter(array $values)
    {
        $required_keys = [
            'id',
            'address',
            'account',
            'currency',
            'amount',
            'confirmed_at'
        ];

        if (!array_keys_exists($required_keys, $values)) {
            Log::alert('checkDepositCallbackParameter. required keys missing in response.', $values);
            throw new VendorException('Invalid deposit callback parameter.');
        }

        if (!array_keys_not_null($required_keys, $values)) {
            Log::alert('checkDepositCallbackParameter. Some of the required value is null.', $values);
            throw new VendorException('Invalid deposit callback parameter.');
        }

        $this->checkAccount(data_get($values, 'account'));
        $this->checkAmount(data_get($values, 'amount'));
    }

    public function checkWithdrawalResponseParameter(array $values)
    {
        $required_keys = [
            'id',
            'address',
            'account',
            'transaction',
            'currency',
            'amount',
            'src_amount',
            'dst_amount',
            'fee',
            'is_full_payment',
            'callback',
            'client_id',
        ];
        # We don't have and 'fee_currency' here since it may be not exist.

        if (!array_keys_exists($required_keys, $values)) {
            Log::alert('checkWithdrawalResponse. required keys missing in response.', $values);
            throw new VendorException('Invalid withdrawal response.');
        }

        $this->checkAccount(data_get($values, 'account'));

        $not_null_keys = [
            'id',
            'address',
            'account',
            'currency',
            'amount',
            'src_amount',
            'dst_amount',
            'is_full_payment',
            'callback',
            'client_id',
        ];
        # We don't have and 'tag' here since it may be null.

        if (!array_keys_not_null($not_null_keys, $values)) {
            Log::alert('checkWithdrawalResponse. Some of the required value is null.', $values);
            throw new VendorException('Invalid withdrawal response.');
        }
    }

    protected function publicLink(string $path)
    {
        $domain = $this->domain;
        return "$domain/api/$path";
    }

    protected function link(string $path)
    {
        $domain = $this->domain;
        $account = $this->account;
        return "$domain/api/$account/$path";
    }

    protected function get(string $link, $authorized = false)
    {
        $token = $authorized ? $this->token : null;
        return $this->request('GET', $link, $token);
    }

    protected function post(string $link, array $data)
    {
        return $this->request('POST', $link, $this->token, $this->key, $data);
    }

    protected function patch(string $link, array $data)
    {
        return $this->request('PATCH', $link, $this->token, $this->key, $data);
    }

    protected function request(
        string $method,
        string $link,
        string $token = null,
        ?string $key = null,
        ?array $data = null
    ) {
        try {
            $reporting_data = [
                'request_method' => $method,
                'request_link' => $link,
                'request_data' => $data,
            ];
            Log::info("WalletService request.", $reporting_data);

            $request = $this->createRequest($method, $link, $token, $key, $data);
            $client = new Client();
            $response = $client->send($request);

            $json = json_decode((string)$response->getBody(), true);
            if (!is_null($json)) {
                Log::info("WalletService response.", $json);
                return $json;
            }
            Log::alert('WalletService Invalid json response from vendor: ' . (string)$response->getBody(), $reporting_data);
            throw new VendorException('Invalid json response from vendor.');
        } catch (ClientException $e) { # status code 4xx
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
            $json = json_decode((string)$response->getBody(), true);
            $msg = data_get($json, 'message', $reason);
            $message = "Error $status_code $msg from vendor.";

            if ($error_code = data_get($json, 'code')) {
                $reporting_data['error_code'] = $error_code;
                $reporting_data['error_name'] = data_get(self::ErrorNames, $error_code);
            }
            $reporting_data['response_body'] = (!empty($json) and is_array($json)) ? $json : (string)$response->getBody();

            if ($status_code === 409) {
                Log::error("WalletService request error. {$status_code} {$message}", $reporting_data);
                throw new ConflictHttpException;
            }
            if ($status_code === 404) {
                Log::error("WalletService request error. {$status_code} {$message}", $reporting_data);
                throw new ModelNotFoundException;
            }

            # for address validation api
            if ($status_code === 422) {
                if (data_get($reporting_data, 'response_body.code') === self::BadAddressError) {
                    throw new WrongAddressFormatError;
                }
            }

            # throw VendorException, show success message to user if badBalance error
            if (!is_null($error_code) and in_array($error_code, self::AlertErrors)) {
                Log::alert("WalletService request error. {$status_code} {$message}", $reporting_data);
                throw new VendorException('BadBalanceError or BadUtxoBalanceError');
            }

            Log::alert("WalletService request error. {$status_code} {$message}", $reporting_data);
            throw new BadRequestError($message);

        } catch (RequestException $e) { # status code 5xx
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $status_code = $response->getStatusCode();
                $reason = $response->getReasonPhrase();
                $json = json_decode((string)$response->getBody(), true);
                $msg = data_get($json, 'message', $reason);
                $message = "Error $status_code $msg from vendor.";

                if ($error_code = data_get($json, 'code')) {
                    $reporting_data['error_code'] = $error_code;
                    $reporting_data['error_name'] = data_get(self::ErrorNames, $error_code);
                }
                $reporting_data['response_body'] = (!empty($json) and is_array($json)) ? $json : (string)$response->getBody();

                $reporting_level = (!is_null($error_code) and in_array($error_code, self::AlertErrors)) ? 'alert' : 'critical';
                Log::{$reporting_level}("WalletService request error. {$status_code} {$message}", $reporting_data);
                throw new VendorException("Error $status_code $msg from vendor.");
            } else {
                $reporting_data['error_message'] = $e->getMessage();
                Log::alert("Wallet service vendor error. Unknown error", $reporting_data);
                throw new VendorException($e->getMessage());
            }
        } catch (Throwable $e) {
            $reporting_data['error_message'] = $e->getMessage();
            Log::alert("Wallet service unknown error.", $reporting_data);
            throw new UnknownError($e->getMessage());
        }
    }

    protected function createRequest(
        string $method,
        string $link,
        string $token = null,
        ?string $key = null,
        ?array $data = null
    ) {
        $headers = ['Content-Type' => 'application/json'];
        if (!is_null($token)) {
            $headers['X-Wallet-Token'] = $token;
        }

        if ($method === 'GET') {
            return new Request($method, $link, $headers);
        }

        $body = json_encode($data);
        $signature = hash_hmac('sha512', $body, $key);
        $headers['X-Wallet-Signature'] = "sha512=$signature";
        return new Request($method, $link, $headers, $body);
    }

    public function verifyRequest(\Illuminate\Http\Request $request, $exception = true) : bool
    {
        $content = $request->getContent();
        $signature = $request->header("X-Wallet-Signature");
        return $this->verifySignature($content, $signature, $exception);
    }

    public function verifySignature($content, $signature, $exception = true) : bool
    {
        $calculated = "sha512=".hash_hmac('sha512', $content, $this->key);
        if ($calculated !== $signature) {
            Log::alert('Wallet Service verifySignature fail', [
                'content' => $content,
                'calculated' => $calculated,
                'signature' => $signature,
            ]);
            if ($exception) {
                throw new VendorException;
            }
            return false;
        }
        return true;
    }
}
