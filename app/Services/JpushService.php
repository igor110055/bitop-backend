<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

use App\Models\{
    DeviceToken,
    User,
};
use App\Repos\Interfaces\DeviceTokenRepo;

class JpushService implements JpushServiceInterface
{
    public function __construct(DeviceTokenRepo $DeviceTokenRepo)
    {
        $this->config = config('services.jpush');
        $this->DeviceTokenRepo = $DeviceTokenRepo;
    }

    public function sendMessageToUser(User $user, array $notification = null, array $data = null, array $option = null)
    {
        $platforms = [DeviceToken::PLATFORM_IOS, DeviceToken::PLATFORM_ANDROID];
        foreach ($platforms as $platform) {
            $tokens = $this->DeviceTokenRepo
                ->getUserActiveTokens($user, $platform)
                ->pluck('token')
                ->all();
            if (!empty($tokens)) {
                $res = $this->sendMessage($platform, $tokens, $notification, $data, $option);
                $this->handleResponse($tokens, $res);
            }
        }
    }

    public function sendMessage($platform ,$tokens, array $notification = null, array $data = null, array $option = null)
    {
        if (!$this->config['key'] or !$this->config['secret']) {
            return;
        }
        $client = new Client(['timeout' => $this->config['timeout']]);
        try {
            $res= $client->send(
                $this->request($this->formatMessagingData($platform, $tokens, $notification, $data, $option))
            );
            return $res->getBody()->getContents();
        } catch (\Throwable $e) {
            \Log::alert('Google FCM service unavailable error', ['error' => strip_tags($e->getMessage())]);
            throw $e;
        }
    }

    protected function request(array $data)
    {
        $auth_string = base64_encode("{$this->key}:{$this->secret}");
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Basic {$auth_string}",
        ];
        return new Request(
            'POST',
            $this->config['link'],
            $headers,
            json_encode($data)
        );
    }

    protected function formatMessagingData($platform, $tokens, array $notification = null, array $data = null, array $option = null)
    {
        $request = [];
        if (is_array($tokens)) {
            $request += ['registration_ids' => $tokens];
        } elseif (is_string($tokens)) {
            $request += ['to' => $tokens];
        }
        # Handle special fcm format for android devices.
        # There will be no 'notification' field in android fcms, and we put elemet from `notification` to `data`
        if ($platform === DeviceToken::PLATFORM_ANDROID) {
            $data = array_merge((array) $data, (array) $notification); # type cast is necessary or an error will be raised when null
        } else {
            if ($notification) {
                $request += ['notification' => $notification];
            }
        }
        if ($data) {
            $request += ['data' => $data];
        }
        if ($option) {
            foreach ($option as $key => $value) {
                $request += [$key => $value];
            }
        }
        return $request;
    }

    protected function handleResponse($tokens, string $response)
    {
        if (!config('services.fcm.key')) {
            return;
        }
        $res = json_decode($response, true);
        if (data_get($res, 'success') !== count($tokens)) { # failure happens
            for ($i = 0; $i < count($tokens); $i++) {
                \Log::error("Fcm push notification receive 200 but fail", ['token' => data_get($tokens, "$i"), 'error_message' => data_get($res, "results.$i.error")]);
            }
        }
    }
}
