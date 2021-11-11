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
    Order,
};

class OrderCompletedNotification extends ExpBackoffJob implements ShouldQueue
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
        $fcm = app()->make(FcmServiceInterface::class);
        $notification = $this->getNotification();
        $res = $fcm->sendMessageToUser(
            $this->user,
            $notification,
            ['action' => 'order-detail', 'id' => $this->order->id]
        );
    }

    protected function getNotification()
    {
        $locale = $this->user->preferred_locale;
        $subject = __('notifications.email.order_completed_notification.subject', [
            'order_id' => $this->order->id,
        ], $locale);

        if ($this->role === User::class) {
            $content = __("notifications.email.order_completed_notification.user.content", [
                'order_id' => $this->order->id,
                'username' => $this->order->src_user->username,
            ], $locale);
        } elseif ($this->role === AdminAction::class) {
            $content = __("notifications.email.order_completed_notification.admin.content", [
                'order_id' => $this->order->id,
            ], $locale);

        }
        return [
            'title' => $subject,
            'body' => isset($content) ? $content : '',
        ];
    }

    public function failed(\Throwable $e)
    {
        \Log::error('Job: Fcm Order Completed Notification failed, FAILED EXCEPTION: '.$e);
        parent::failed($e);
    }
}
