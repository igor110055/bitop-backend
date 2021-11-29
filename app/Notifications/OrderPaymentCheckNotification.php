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

class OrderPaymentCheckNotification extends Notification implements ShouldQueue
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

        $subject = __('notifications.email.order_payment_check.subject', [
                'order_id' => $this->order->id,
            ], $locale);
        $greeting = __('notifications.email.order_payment_check.greeting', [
                'order_id' => $this->order->id,
            ], $locale);

        $content = __("notifications.email.order_payment_check.content", [
            'order_id' => $this->order->id,
        ], $locale);

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content);
    }
}
