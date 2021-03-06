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
use Storage;
use Illuminate\Support\Facades\Log;

use App\Models\{
    Config,
    WfpayAccount,
    Wfpayment,
    Wftransfer
};

use App\Repos\Interfaces\{
    ConfigRepo,
};

class WfpayService implements WfpayServiceInterface
{
    public function __construct(
        ConfigRepo $ConfigRepo
    ) {
        $this->ConfigRepo = $ConfigRepo;
        /* $configs = config('services.wfpay');
        $this->configs = $configs;
        $this->url = $configs['link'];
        $this->account = $configs['account']; */
    }

    public function setAccount(WfpayAccount $wf_pay_account)
    {
        $this->wf_pay_account = $wf_pay_account;
        $this->url = $wf_pay_account['api_url'];
        $this->account = $wf_pay_account['id'];
        $this->private_key = $wf_pay_account['private_key'];
        $this->public_key = $wf_pay_account['public_key'];
    }

    public function getOrder(Wfpayment $wfpayment)
    {
        $wfpay_account = $wfpayment->wfpay_account;
        $this->setAccount($wfpay_account);

        $data = [
            "account_name" => $wfpay_account->id,
            "merchant_order_id" => $wfpayment->id,
            "timestamp" => Carbon::now()->toIso8601String(),
        ];

        $link = $this->link('orders/query');
        return $this->post($link, $data);
    }

    public function createOrder(
        WfpayAccount $wfpay_account,
        $id,
        $amount,
        $payment_method = 'bank',
        $real_name,
        $notify_url,
        $return_url,
        $force_matching = true
    ) {
        $this->setAccount($wfpay_account);
        $data = [
            "account_name" => $this->account,
            "merchant_order_id" => $id,
            "total_amount" => $amount,
            "timestamp" => Carbon::now()->toIso8601String(),
            "notify_url" => $notify_url,
            "return_url" => $return_url,
            "subject" => "??????",
            "guest_real_name" => $real_name,
            "payment_method" => $payment_method
        ];
        if ($force_matching) {
            $data['force_matching'] = true;
        }

        $config = $this->ConfigRepo->get(Config::ATTRIBUTE_WFPAY);
        if (data_get($config, 'deactivated')) {
            if ($payment_method === 'bank') {
                return [
                    'status' => 'init',
                    'bank_account' => [
                        'name' => 'Example Name',
                        'bank_name' => 'Some Bank Name',
                        'account_name' => '012345678901234',
                        'bank_branch_name' => 'Some Bank Banch Name',
                    ],
                ];
            } else {
                return [
                    'status' => 'init',
                    'payment_url' => 'https://www.baidu.com/',
                ];
            }
            return $data;
        }

        $link = $this->link('orders/payment');
        return $this->post($link, $data);
    }

    public function getTransfer(Wftransfer $wftransfer)
    {
        $wfpay_account = $wftransfer->wfpay_account;
        $this->setAccount($wfpay_account);
        $data = [
            "account_name" => $wfpay_account->id,
            "merchant_order_id" => $wftransfer->id,
            "timestamp" => Carbon::now()->toIso8601String(),
        ];

        $link = $this->link('orders/payment_transfer_query');
        return $this->post($link, $data);
    }

    public function createTransfer(
        WfpayAccount $wfpay_account,
        $id,
        $amount,
        $notify_url,
        $bank_name,
        $bank_province_name,
        $bank_city_name,
        $bank_account_no,
        $bank_account_type,
        $bank_account_name
    ) {
        $this->setAccount($wfpay_account);
        $data = [
            "account_name" => $this->account,
            "merchant_order_id" => $id,
            "total_amount" => $amount,
            "timestamp" => Carbon::now()->toIso8601String(),
            "notify_url" => $notify_url,
            "bank_name" => $bank_name,
            "bank_province_name" => $bank_province_name,
            "bank_city_name" => $bank_city_name,
            "bank_account_no" => $bank_account_no,
            "bank_account_type" => $bank_account_type,
            "bank_account_name" => $bank_account_name
        ];

        $config = $this->ConfigRepo->get(Config::ATTRIBUTE_WFPAY);
        if (data_get($config, 'deactivated')) {
            return $data;
        }

        $link = $this->link('orders/payment_transfer');
        return $this->post($link, $data);
    }

    public function rematch($id)
    {
        $data = [
            "account_name" => $this->account,
            "merchant_order_id" => $id,
            "timestamp" => Carbon::now()->toIso8601String(),
        ];

        $link = $this->link('orders/rematch');
        return $this->post($link, $data);
    }

    protected function link(string $path)
    {
        $domain = $this->url;
        return "$domain/merchant_api/v1/$path";
    }

    protected function get(string $link, array $data)
    {
        return $this->request('GET', $link, $data);
    }

    protected function post(string $link, array $data)
    {
        return $this->request('POST', $link, $data);
    }

    protected function request(
        string $method,
        string $link,
        ?array $data = null
    ) {
        try {
            $reporting_data = [
                'request_method' => $method,
                'request_link' => $link,
                'request_data' => $data,
            ];
            Log::info("WfpayService request.", $reporting_data);

            $request = $this->createRequest($method, $link, $data);
            $client = new Client();
            $response = $client->send($request);

            $json = json_decode((string)$response->getBody(), true);
            if (!is_null($json)) {
                Log::info("Wfservice response.", $json);
                return $json;
            }
            Log::alert('Wfservice Invalid json response from vendor: ' . (string)$response->getBody(), $reporting_data);
            throw new VendorException('Invalid json response from vendor.');
        } catch (ClientException $e) { # status code 4xx
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
            $json = json_decode((string)$response->getBody(), true);
            $msg = data_get($json, 'message', $reason);
            $message = "Error $status_code $msg from vendor.";

            $reporting_data['response_body'] = (!empty($json) and is_array($json)) ? $json : (string)$response->getBody();

            Log::alert("WfpayService request error. {$status_code} {$message}", $reporting_data);
            throw new BadRequestError(json_encode($json));
        } catch (RequestException $e) { # status code 5xx
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $status_code = $response->getStatusCode();
                $reason = $response->getReasonPhrase();
                $json = json_decode((string)$response->getBody(), true);
                $msg = data_get($json, 'message', $reason);
                $message = "Error $status_code $msg from vendor.";

                $reporting_data['response_body'] = (!empty($json) and is_array($json)) ? $json : (string)$response->getBody();

                Log::alert("Wfservice request error. {$status_code} {$message}", $reporting_data);
                throw new VendorException("Error $status_code $msg from vendor.");
            } else {
                $reporting_data['error_message'] = $e->getMessage();
                Log::alert("Wfservice vendor error. Unknown error", $reporting_data);
                throw new VendorException($e->getMessage());
            }
        } catch (\Throwable $e) {
            $reporting_data['error_message'] = $e->getMessage();
            Log::alert("Wfservice unknown error.", $reporting_data);
            throw new UnknownError($e->getMessage());
        }
    }

    protected function createRequest(
        string $method,
        string $link,
        ?array $data = null
    ) {
        $headers = [];
        if ($method === 'GET') {
            return new Request($method, $link, $headers);
        }

        $data_json_string = json_encode($data);
        $private_key = openssl_pkey_get_private($this->private_key);
        openssl_sign($data_json_string, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        $body = json_encode([
            "data" => $data_json_string,
            "signature" => $signature,
        ]);

        return new Request($method, $link, $headers, $body);
    }

    public function verifyRequest(WfpayAccount $wfpay_account, \Illuminate\Http\Request $request, $exception = true) : bool
    {
        $this->setAccount($wfpay_account);
        $content = $request->input("data");
        $signature = $request->input("signature");
        return $this->verifySignature($content, $signature, $exception);
    }

    public function verifySignature($content, $signature, $exception = true) : bool
    {
        $public_key = openssl_pkey_get_public($this->public_key);
        if (openssl_verify($content, base64_decode($signature), $public_key, OPENSSL_ALGO_SHA256) == '1') {
            return true;
        } else {
            Log::alert('Wfpay Service verifySignature fail', [
                'content' => $content,
                'signature' => $signature,
            ]);
            if ($exception) {
                throw new BadRequestError;
            }
            return false;
        }
    }
}
