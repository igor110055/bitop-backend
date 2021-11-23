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

    public function createPayment(
        $id,
        $amount,
        $payment_method = 'bank',
        $real_name,
        $notify_url,
        $return_url
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

        $link = $this->link('orders/payment');
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
            Log::critical('Wfservice Invalid json response from vendor: ' . (string)$response->getBody(), $reporting_data);
            throw new VendorException('Invalid json response from vendor.');
        } catch (ClientException $e) { # status code 4xx
            $response = $e->getResponse();
            $status_code = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
            $json = json_decode((string)$response->getBody(), true);
            $msg = data_get($json, 'message', $reason);
            $message = "Error $status_code $msg from vendor.";

            $reporting_data['response_body'] = (!empty($json) and is_array($json)) ? $json : (string)$response->getBody();

            /* if ($status_code === 409) {
                Log::error("Wfservice request error. {$status_code} {$message}", $reporting_data);
                throw new ConflictHttpException;
            }
            if ($status_code === 404) {
                Log::error("Wfservice request error. {$status_code} {$message}", $reporting_data);
                throw new ModelNotFoundException;
            }

            # for address validation api
            if ($status_code === 422) {
                if (data_get($reporting_data, 'response_body.code') === self::BadAddressError) {
                    throw new WrongAddressFormatError;
                }
            } */

            Log::alert("WfpayService request error. {$status_code} {$message}", $reporting_data);
            throw new BadRequestError($message);

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

        $private_content = Storage::get('wfpay/private_key.pem');
        $private_key = openssl_pkey_get_private($private_content);
        openssl_sign($data_json_string, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        $body = json_encode([
            "data" => $data_json_string,
            "signature" => $signature,
        ]);

        return new Request($method, $link, $headers, $body);
    }
}
