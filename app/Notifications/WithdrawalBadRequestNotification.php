<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

use App\Models\Withdrawal;

class WithdrawalBadRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $withdrawal;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Withdrawal $withdrawal)
    {
        $this->withdrawal = $withdrawal;
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
        $locale = $this->withdrawal->user->preferred_locale;
        $subject = __('notifications.email.withdrawal_bad_request_notification.subject', [
            'time' => datetime($this->withdrawal->canceled_at),
        ], $locale);
        $greeting = __('notifications.email.withdrawal_bad_request_notification.greeting', [], $locale);
        $content1 = __('notifications.email.withdrawal_bad_request_notification.content1', [
            'confirmed_time' => datetime($this->withdrawal->confirmed_at),
        ], $locale);
        $data = '<table style="margin: 20px 0; font-size: 14px; border-spacing: 10px; border: none; color: #3d4852">';
        $data .= "<tr><td>".__('common.withdrawal', [], $locale)." ID</td><td>{$this->withdrawal->id}</td></tr>";
        $data .= "<tr><td>".__('common.amount', [], $locale)."</td><td>".comma_format(trim_zeros($this->withdrawal->amount)).' '.$this->withdrawal->coin."</td></tr>";
        $data .= "<tr><td>".__('common.address', [], $locale)."</td><td>{$this->withdrawal->address}</td></tr>";
        if (!is_null(data_get($this->withdrawal, 'tag'))) {
            $data .= "<tr><td>Tag</td><td>{$this->withdrawal->tag}</td></tr>";
        }
        $data .= '</table>';
        $content2 = __('notifications.email.withdrawal_bad_request_notification.content2', [
            'canceled_time' => datetime($this->withdrawal->canceled_at),
        ], $locale);
        $content3 = __('notifications.email.withdrawal_bad_request_notification.content3', [], $locale);
        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content1)
            ->line(new HtmlString($data))
            ->line($content2)
            ->line($content3);
    }
}
