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
    Withdrawal,
};

class WithdrawalBadRequestNotification extends ExpBackoffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $withdrawal;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, Withdrawal $withdrawal)
    {
        $this->user = $user;
        $this->withdrawal = $withdrawal;
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
        $res = $fcm->sendMessageToUser($this->user, $notification);
    }

    protected function getNotification()
    {
        $locale = $this->user->preferred_locale;
        $subject = __('notifications.email.withdrawal_bad_request_notification.subject', [
            'time' => datetime($this->withdrawal->canceled_at),
        ], $locale);
        $content = __('notifications.email.withdrawal_bad_request_notification.content2', [
            'canceled_time' => datetime($this->withdrawal->canceled_at),
        ], $locale);
        return [
            'title' => $subject,
            'body' => $content,
        ];
    }

    public function failed(\Throwable $e)
    {
        \Log::error('Job: Fcm Withdrawal Fail Notification failed, FAILED EXCEPTION: '.$e);
        parent::failed($e);
    }
}
