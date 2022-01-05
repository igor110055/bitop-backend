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

class DealNotification extends Notification implements ShouldQueue
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
    public function __construct(Order $order, $role, $action)
    {
        $this->order = $order;
        $this->role = $role;
        $this->action = $action;
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

        # remove sms since user don't have mobile anymore.
        /* if ($this->order->is_express) {
            return ['mail'];
        }
        if (($this->action === 'buy') && $notifiable->is($this->order->src_user)) {
            return ['mail', 'sms'];
        } elseif(($this->action === 'sell') && $notifiable->is($this->order->dst_user)) {
            return ['mail', 'sms'];
        } else {
            return ['mail'];
        } */
    }

    /* public function toNexmo($notifiable)
    {
        $locale = $notifiable->preferred_locale;
        $content = __('notifications.sms.deal_notification', [
            'order_id' => $this->order->id,
        ], $locale);

        return (new NexmoMessage)
            ->content($content)
            ->unicode();
    }

    public function toSms($notifiable)
    {
        $locale = $notifiable->preferred_locale;
        $content = __('notifications.sms.deal_notification', [
            'order_id' => $this->order->id,
        ], $locale);
        return new SmsMessage($content);
    } */

    public function toMail($notifiable)
    {
        $locale = $notifiable->preferred_locale;
        $username = ($this->role === 'src_user') ? $this->order->dst_user->username : $this->order->src_user->username;
        $amount = comma_format(trim_zeros($this->order->amount));
        $coin = $this->order->coin;
        $path = Order::FRONTEND_DETAIL_PATH;
        $url = url($path.$this->order->id);

        $subject = __('notifications.email.deal_notification.subject', [
                'order_id' => $this->order->id,
            ], $locale);
        $greeting = __('notifications.email.deal_notification.greeting', [
                'order_id' => $this->order->id,
            ], $locale);
        $content = __("notifications.email.deal_notification.content.{$this->role}.{$this->action}", [
                'username' => $username,
                'amount' => $amount,
                'coin' => $coin,
            ], $locale);
        $action = __('notifications.email.deal_notification.action', [], $locale);
        if ($this->role === 'dst_user') {
            $reminder =  __('notifications.email.deal_notification.dst_user_reminder', [
                'time' => $this->order->expired_at->toDateTimeString(),
            ], $locale);
        } else {
            $reminder = '';
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content)
            ->action($action, $url)
            ->line($reminder);
    }
}
