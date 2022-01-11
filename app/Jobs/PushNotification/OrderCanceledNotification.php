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

class OrderCanceledNotification extends ExpBackoffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $order;
    public $role;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, Order $order, string $role = User::class)
    {
        $this->user = $user;
        $this->order = $order;
        $this->role = $role;
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
        $res = $jpush->sendMessageToUser(
            $this->user,
            $notification,
            ['action' => 'order-detail', 'id' => $this->order->id]
        );
    }

    protected function getNotification()
    {
        $locale = $this->user->preferred_locale;
        $subject = __('notifications.email.order_canceled_notification.subject', [
            'order_id' => $this->order->id,
        ], $locale);

        $seller_id = $this->order->src_user->id;
        $buyer_id = $this->order->dst_user->id;

        if ($this->role === User::class) {
            if ($this->user->id === $seller_id) {
                $content = __("notifications.email.order_canceled_notification.user.seller.content", [
                ], $locale);
            }
        } elseif ($this->role === SystemAction::class) {
            if ($this->user->id === $seller_id) {
                $content = __("notifications.email.order_canceled_notification.system.seller.content", [
                ], $locale);
            } elseif ($this->user->id === $buyer_id) {
                $content = __("notifications.email.order_canceled_notification.system.buyer.content", [
                ], $locale);
            }
        } elseif ($this->role === AdminAction::class) {
            $content = __("notifications.email.order_canceled_notification.admin.content", [
            ], $locale);
        }

        return [
            'title' => $subject,
            'body' => isset($content) ? $content : '',
        ];
    }

    public function failed(\Throwable $e)
    {
        \Log::error('Job: Jpush Order Canceled Notification failed, FAILED EXCEPTION: '.$e);
        parent::failed($e);
    }
}
