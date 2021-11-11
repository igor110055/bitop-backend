<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\Verification;

class EmailVerification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $verification;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Verification $verification)
    {
        $this->verification = $verification;
        $this->locale = \App::getLocale();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return $this->verification->channel;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $subject = __('notifications.email.email_verification.subject', [], $this->locale);
        $content = __('notifications.email.email_verification.content', [
            'code' => $this->verification->code,
        ], $this->locale);

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello!')
            ->line($content)
            ->line($this->verification->code);
    }
}
