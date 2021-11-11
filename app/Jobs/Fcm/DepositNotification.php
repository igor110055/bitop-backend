<?php

namespace App\Jobs\Fcm;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\ExpBackoffJob;
use App\Services\FcmServiceInterface;
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
        $fcm = app()->make(FcmServiceInterface::class);
        $notification = $this->getNotification();
        $fcm->sendMessageToUser($this->user, $notification);
    }

    protected function getNotification()
    {
        $locale = $this->user->preferred_locale;
        $subject = __('notifications.email.deposit_notification.subject', [
            'time' => $this->deposit->confirmed_at->toDateTimeString(),
        ], $locale);
        $content = __('notifications.email.deposit_notification.content1', [
            'amount' => comma_format(trim_zeros($this->deposit->amount)),
            'coin' => $this->deposit->coin,
            'time' => $this->deposit->confirmed_at->toDateTimeString(),
        ], $locale);
        return [
            'title' => $subject,
            'body' => $content,
        ];
    }

    public function failed(\Throwable $e)
    {
        \Log::error('Job: Fcm Deposit Notification failed, FAILED EXCEPTION: '.$e);
        parent::failed($e);
    }
}
