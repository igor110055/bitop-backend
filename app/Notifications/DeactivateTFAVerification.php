<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\Verification;

class DeactivateTFAVerification extends Notification implements ShouldQueue
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
        $subject = __('notifications.email.deactivate_tfa_verification.subject', [], $notifiable->preferred_locale);
        $content = __('notifications.email.deactivate_tfa_verification.content', [
            'code' => $this->verification->code,
        ], $notifiable->preferred_locale);

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello!')
            ->line($content)
            ->line($this->verification->code);
    }
}
