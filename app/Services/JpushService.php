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
        $tokens = $this->DeviceTokenRepo
            ->getUserActiveTokens($user)
            ->pluck('token')
            ->all();
        $res = $this->sendMessage('all', $tokens, $notification, $data, $option);
        $this->handleResponse($tokens, $res);
        if (!empty($tokens)) {
            $res = $this->sendMessage('all', $tokens, $notification, $data, $option);
            $this->handleResponse($tokens, $res);
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
            \Log::alert('Jpush service unavailable error', ['error' => strip_tags($e->getMessage())]);
            throw $e;
        }
    }

    protected function request(array $data)
    {
        $auth_string = base64_encode("{$this->config['key']}:{$this->config['secret']}");
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
        $request['platform'] = $platform;
        if (is_array($tokens)) {
            $request['registration_id'] = $tokens;
        } elseif (is_string($tokens)) {
            $request['registration_id'] = [$tokens];
        }
        $request['notification'] = [
            'ios' => [
                'alert' => $notification,
                'extras' => $data,
            ],
            'android' => [
                'alert' => $notification['body'],
                'title' => $notification['title'],
                'extras' => $data,
            ],
        ];

        if ($option) {
            foreach ($option as $key => $value) {
                $request += [$key => $value];
            }
        }
        \Log::alert(json_encode($request));
        return $request;
    }

    protected function handleResponse($tokens, string $response)
    {
        if (!config('services.jpush.key')) {
            return;
        }
        $res = json_decode($response, true);
        if (!data_get($res, 'msg_id')) { # failure happens
            \Log::error("Jpush push notification receive 200 but fail", ['tokens' => $tokens, 'res' => $res]);
        }
    }
}
