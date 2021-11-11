<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\{
    Order,
    AdminAction,
    SystemAction,
    User,
};

class OrderCanceledNotification extends Notification implements ShouldQueue
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

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $locale = $notifiable->preferred_locale;
        $user_id = $notifiable->id;
        $seller_id = $this->order->src_user->id;
        $buyer_id = $this->order->dst_user->id;

        $subject = __('notifications.email.order_canceled_notification.subject', [
                'order_id' => $this->order->id,
            ], $locale);
        $greeting = __('notifications.email.order_canceled_notification.greeting', [
                'order_id' => $this->order->id,
            ], $locale);

        if ($this->role === User::class) {
            if ($user_id === $seller_id) {
                $content = __("notifications.email.order_canceled_notification.user.seller.content", [
                ], $locale);
            } elseif ($user_id === $buyer_id) {
                $content = __("notifications.email.order_canceled_notification.user.buyer.content", [
                ], $locale);
            }
        } elseif ($this->role === SystemAction::class) {
            if ($user_id === $seller_id) {
                $content = __("notifications.email.order_canceled_notification.system.seller.content", [
                ], $locale);
            } elseif ($user_id === $buyer_id) {
                $content = __("notifications.email.order_canceled_notification.system.buyer.content", [
                ], $locale);
            }
        } elseif ($this->role === AdminAction::class) {
            $content = __("notifications.email.order_canceled_notification.admin.content", [
            ], $locale);
        } else {
            return;
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content);
    }
}
