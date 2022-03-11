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
    Deposit,
};

class DepositNotification extends ExpBackoffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $deposit;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, Deposit $deposit)
    {
        $this->user = $user;
        $this->deposit = $deposit;
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
        $jpush->sendMessageToUser($this->user, $notification);
    }

    protected function getNotification()
    {
        $locale = $this->user->preferred_locale;
        $subject = __('notifications.email.deposit_notification.subject', [
            'time' => datetime($this->deposit->confirmed_at),
        ], $locale);
        $content = __('notifications.email.deposit_notification.content1', [
            'amount' => comma_format(trim_zeros($this->deposit->amount)),
            'coin' => $this->deposit->coin,
            'time' => datetime($this->deposit->confirmed_at),
        ], $locale);
        return [
            'title' => $subject,
            'body' => $content,
        ];
    }

    public function failed(\Throwable $e)
    {
        \Log::error('Job: Jpush Deposit Notification failed, FAILED EXCEPTION: '.$e);
        parent::failed($e);
    }
}
