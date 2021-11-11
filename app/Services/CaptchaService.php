<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Exceptions\{
    Auth\WrongCaptchaError,
};

class CaptchaService implements CaptchaServiceInterface
{
    public function __construct()
    {
        $this->secret = config('services.captcha.secret');
        $this->link = config('services.captcha.link');
    }

    protected function request(string $token = null)
    {
        $parameters = [
            'secret' => $this->secret,
            'response' => $token,
        ];
        $headers = [
            "Accepts: application/x-www-form-urlencoded",
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->link,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($parameters),
            CURLOPT_RETURNTRANSFER => 1 # ask for raw response instead of bool
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    public function verify(string $token = null)
    {
        try {
            $response = $this->request($token);
            if (data_get($response, 'success') === false) {
                throw new WrongCaptchaError("Captcha verification failed: ".data_get($response, 'error-codes')[0]);
            }
        } catch (WrongCaptchaError $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::critical($e->getMessage());
        }
    }
}
