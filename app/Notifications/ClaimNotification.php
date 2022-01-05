<?php

namespace App\Notifications;

use Carbon\Carbon;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage;

use App\Channels\Messages\SmsMessage;
use App\Models\Order;

class ClaimNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $role;
    protected $action;

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

    /* public function toNexmo($notifiable)
    {
        $locale = $notifiable->preferred_locale;
        $content = __('notifications.sms.claim_notification', [
            'order_id' => $this->order->id,
        ], $locale);

        return (new NexmoMessage)
            ->content($content)
            ->unicode();
    }

    public function toSms($notifiable)
    {
        $locale = $notifiable->preferred_locale;
        $content = __('notifications.sms.claim_notification', [
            'order_id' => $this->order->id,
        ], $locale);
        return new SmsMessage($content);
    } */

    public function toMail($notifiable)
    {
        $locale = $notifiable->preferred_locale;
        $username = $this->order->dst_user->username;
        $path = Order::FRONTEND_DETAIL_PATH;
        $url = url($path.$this->order->id);

        $subject = __('notifications.email.claim_notification.subject', [
                'order_id' => $this->order->id,
            ], $locale);
        $greeting = __('notifications.email.claim_notification.greeting', [
                'order_id' => $this->order->id,
            ], $locale);
        $content = __("notifications.email.claim_notification.content", [
                'order_id' => $this->order->id,
                'username' => $username,
            ], $locale);
        $action = __('notifications.email.claim_notification.action', [], $locale);

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content)
            ->action($action, $url);
    }
}
