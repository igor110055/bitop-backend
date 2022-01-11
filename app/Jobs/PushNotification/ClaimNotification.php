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
    Order,
};

class ClaimNotification extends ExpBackoffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $order;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, Order $order)
    {
        $this->user = $user;
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $jpush = app()->make(JpusherviceInterface::class);
        $notification = $this->getNotification();
        $jpush->sendMessageToUser(
            $this->user,
            $notification,
            ['action' => 'order-detail', 'id' => $this->order->id]
        );
    }

    protected function getNotification()
    {
        $locale = $this->user->preferred_locale;
        $subject = __('notifications.email.claim_notification.subject', [
            'order_id' => $this->order->id,
        ], $locale);
        $content = __("notifications.email.claim_notification.content", [
            'order_id' => $this->order->id,
            'username' => $this->order->dst_user->username,
        ], $locale);
        return [
            'title' => $subject,
            'body' => $content,
        ];
    }

    public function failed(\Throwable $e)
    {
        \Log::error('Job: Jpush Claim Notification failed, FAILED EXCEPTION: '.$e);
        parent::failed($e);
    }
}
