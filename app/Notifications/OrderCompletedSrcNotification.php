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

class OrderCompletedSrcNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
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
        $path = Order::FRONTEND_DETAIL_PATH;
        $url = url($path.$this->order->id);

        $subject = __('notifications.email.order_completed_src_notification.subject', [
                'order_id' => $this->order->id,
            ], $locale);
        $greeting = __('notifications.email.order_completed_src_notification.greeting', [
                'order_id' => $this->order->id,
            ], $locale);
        $action = __('notifications.email.order_completed_src_notification.action', [], $locale);

        $content = __("notifications.email.order_completed_src_notification.content", [
            'order_id' => $this->order->id,
        ], $locale);

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content)
            ->action($action, $url);
    }
}
