<?php

namespace App\Jobs\Jpush;

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

class DealNotification extends ExpBackoffJob implements ShouldQueue
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
        $jpush = app()->make(JpushServiceInterface::class);
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
        $amount = comma_format(trim_zeros($this->order->amount));

        $subject = __('notifications.fcm.deal_notification.subject', [
            'order_id' => $this->order->id,
        ], $locale);
        $content = __('notifications.fcm.deal_notification.content', [
            'amount' => $amount,
            'coin' => $this->order->coin,
        ], $locale);
        return [
            'title' => $subject,
            'body' => $content,
        ];
    }

    public function failed(\Throwable $e)
    {
        \Log::error('Job: Jpush Deal Notification failed, FAILED EXCEPTION: '.$e);
        parent::failed($e);
    }
}
