<?php

namespace App\Notifications;

use Carbon\Carbon;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\{
    Order,
    User,
    AdminAction,
};

class OrderCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $role;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order, string $role = User::class)
    {
        $this->order = $order;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $locale = $notifiable->preferred_locale;
        $username = $this->order->src_user->username;
        $path = Order::FRONTEND_DETAIL_PATH;
        $url = url($path.$this->order->id);

        $subject = __('notifications.email.order_completed_notification.subject', [
                'order_id' => $this->order->id,
            ], $locale);
        $greeting = __('notifications.email.order_completed_notification.greeting', [
                'order_id' => $this->order->id,
            ], $locale);
        $action = __('notifications.email.order_completed_notification.action', [], $locale);

        if ($this->role === User::class) {
            $content = __("notifications.email.order_completed_notification.user.content", [
                'order_id' => $this->order->id,
                'username' => $username,
            ], $locale);
        } elseif ($this->role === AdminAction::class) {
            $content = __("notifications.email.order_completed_notification.admin.content", [
                'order_id' => $this->order->id,
            ], $locale);

        } else {
            return;
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content)
            ->action($action, $url);
    }
}
