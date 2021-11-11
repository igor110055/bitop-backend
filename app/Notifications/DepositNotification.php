<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\Deposit;

class DepositNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $deposit;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Deposit $deposit)
    {
        $this->deposit = $deposit;
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
        $locale = $this->deposit->user->preferred_locale;
        $subject = __('notifications.email.deposit_notification.subject', [
            'time' => $this->deposit->confirmed_at->toDateTimeString(),
        ], $locale);
        $greeting = __('notifications.email.deposit_notification.greeting', [], $locale);
        $content1 = __('notifications.email.deposit_notification.content1', [
            'amount' => comma_format(trim_zeros($this->deposit->amount)),
            'coin' => $this->deposit->coin,
            'time' => $this->deposit->confirmed_at->toDateTimeString(),
        ], $locale);
        $content2 = __('notifications.email.deposit_notification.content2', [], $locale);
        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content1)
            ->line($content2);
    }
}
