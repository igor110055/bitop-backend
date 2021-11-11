<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Transfer;

class TransferNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $transfer;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
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
        $locale = $this->transfer->dst_user->preferred_locale;
        $subject = __('notifications.email.transfer_notification.subject', [
            'time' => $this->transfer->created_at->toDateTimeString(),
        ], $locale);
        $greeting = __('notifications.email.transfer_notification.greeting', [], $locale);
        $content1 = __('notifications.email.transfer_notification.content1', [
            'amount' => comma_format(trim_zeros($this->transfer->amount)),
            'coin' => $this->transfer->coin,
            'source' => $this->transfer->src_user->username,
            'time' => $this->transfer->created_at->toDateTimeString(),
        ], $locale);
        if (is_null($this->transfer->message)) {
            $message = null;
        } else {
            $message = __('notifications.email.transfer_notification.message', [
                'source' => $this->transfer->src_user->username,
                'message' => $this->transfer->message,
            ], $locale);
        }
        $content2 = __('notifications.email.transfer_notification.content2', [], $locale);
        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content1)
            ->line($message)
            ->line($content2);
    }
}
