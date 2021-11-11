<?php

namespace App\Logging;

use DB;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

use Illuminate\Support\Facades\Log;
class UserLogHandler extends AbstractProcessingHandler
{
    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $user_id = \Auth::id() ?? \Auth::guard('web')->id();
        $data = [
            'user_id' => $user_id ?? $record['context']['id'],
            'message' => $record['message'],
            'context' => json_encode($record['context']),
            'remote_addr' => request_ip(),
            'user_agent' => data_get($record['context'], 'agent'),
            'created_at' => $record['datetime']->format('Uv'),
            'updated_at' => $record['datetime']->format('Uv'),
        ];

        try {
            $data['context'] = substr($data['context'], 0, config('core.log_context_max_length'));
            DB::table('user_logs')->insert($data);
        } catch (\Throwable $e) {
            Log::error($e);
        }
    }
}
