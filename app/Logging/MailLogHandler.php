<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use App\Notifications\CriticalErrorAlert;

class MailLogHandler extends AbstractProcessingHandler
{
    public function __construct($level = Logger::CRITICAL, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $receiver = config('core.critical_error.mail');
        try {
            Notification::route('mail', $receiver)->notify(new CriticalErrorAlert($record['formatted']));
        } catch (\Throwable $e) {
            Log::error($e);
        }
    }
}
