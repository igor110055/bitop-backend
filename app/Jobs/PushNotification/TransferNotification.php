<?php

namespace App\Jobs\PushNotification;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\ExpBackoffJob;
use App\Services\JpushServiceInterface;
use App\Models\{
    User,
    Transfer,
};

class TransferNotification extends ExpBackoffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $transfer;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, Transfer $transfer)
    {
        $this->user = $user;
        $this->transfer = $transfer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $jpush = app()->make(JpushServiceInterface::class);
        $notification = $this->getNotification();
        $res = $jpush->sendMessageToUser($this->user, $notification);
    }

    protected function getNotification()
    {
        $locale = $this->user->preferred_locale;
        $subject = __('notifications.email.transfer_notification.subject', [
            'time' => $this->transfer->created_at->toDateTimeString(),
        ], $locale);
        $content = __('notifications.email.transfer_notification.content1', [
            'amount' => comma_format(trim_zeros($this->transfer->amount)),
            'coin' => $this->transfer->coin,
            'source' => $this->transfer->src_user->username,
            'time' => $this->transfer->created_at->toDateTimeString(),
        ], $locale);
        return [
            'title' => $subject,
            'body' => $content,
        ];
    }

    public function failed(\Throwable $e)
    {
        \Log::error('Job: Jpush Transfer Notification failed, FAILED EXCEPTION: '.$e);
        parent::failed($e);
    }
}
