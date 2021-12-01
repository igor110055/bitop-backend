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
    Wfpayment,
};

class WfpayService implements WfpayServiceInterface
{
    public function __construct() {
        $configs = config('services.wfpay');
        $this->configs = $configs;
        $this->url = $configs['link'];
        $this->account = $configs['account'];
    }

    public function getOrder($id)
    {
        $data = [
            "account_name" => $this->account,
            "merchant_order_id" => $id,
            "timestamp" => Carbon::now()->toIso8601String(),
        ];

        $link = $this->link('orders/query');
        return $this->post($link, $data);
    }

    public function createOrder(
        $id,
        $amount,
        $payment_method = 'bank',
        $real_name,
        $notify_url,
        $return_url,
        $force_matching = true
    ) {
        $data = [
            "account_name" => $this->account,
            "merchant_order_id" => $id,
            "total_amount" => $amount,
            "timestamp" => Carbon::now()->toIso8601String(),
            "notify_url" => $notify_url,
            "return_url" => $return_url,
            "subject" => "储值",
            "guest_real_name" => $real_name,
            "payment_method" => $payment_method
        ];
        if ($force_matching) {
            $data['force_matching'] = true;
        }

        $link = $this->link('orders/payment');
        return $this->post($link, $data);
    }

    public function getTransfer($id)
    {
        $data = [
            "account_name" => $this->account,
            "merchant_order_id" => $id,
            "timestamp" => Carbon::now()->toIso8601String(),
        ];

        $link = $this->link('orders/payment_transfer_query');
        return $this->post($link, $data);
    }

    public function createTranfer(
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

        if (data_get($this->configs, 'send_transfer', false)) {
            $link = $this->link('orders/payment_transfer');
            return $this->post($link, $data);
        }
        return $data;
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
        } catch (Throwable $e) {
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
        if (config('app.env') === 'local') {
            $private_content = Storage::get('wfpay/private_key.pem');
        } else {
            $private_content = Storage::disk('s3')->get('wfpay/private_key.pem');
        }
        $private_key = openssl_pkey_get_private($private_content);
        openssl_sign($data_json_string, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        $body = json_encode([
            "data" => $data_json_string,
            "signature" => $signature,
        ]);

        return new Request($method, $link, $headers, $body);
    }

    public function verifyRequest(\Illuminate\Http\Request $request, $exception = true) : bool
    {
        $content = $request->input("data");
        $signature = $request->input("signature");
        return $this->verifySignature($content, $signature, $exception);
    }

    public function verifySignature($content, $signature, $exception = true) : bool
    {
        if (config('app.env') === 'local') {
            $public_content = Storage::get('wfpay/public_key.pem');
        } else {
            $public_content = Storage::disk('s3')->get('wfpay/public_key.pem');
        }
        $public_key = openssl_pkey_get_public($public_content);
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
