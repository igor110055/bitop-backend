<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class GoogleChatLogHandler extends AbstractProcessingHandler
{
    public function __construct($level = Logger::ALERT, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $url = config('core.googlechat.webhook');
        $data = ['text' => substr(data_get($record, 'formatted', ''), 0, config('core.log_context_max_length'))];
        $request = new Request(
            'POST',
            config('core.googlechat.webhook'),
            ['Content-type' => 'application/json'],
            json_encode($data)
        );
        $client = new Client([
            'timeout' => '0.1',
        ]);
        try {
            $promise = $client->sendAsync($request)->wait();
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            # catch this exception because we minimize the request timeout
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }
    }
}
