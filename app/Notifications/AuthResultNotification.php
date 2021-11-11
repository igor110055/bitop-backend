<?php

namespace App\Notifications;

use Carbon\Carbon;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\Authentication;

class AuthResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reasons;
    protected $other_reason;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($reasons = [], $other_reason = null)
    {
        $this->reasons = $reasons;
        $this->other_reason = $other_reason;
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
        $authentication_status = $notifiable->authentication_status;
        $name = empty($notifiable->first_name) ? __('notifications.email.auth_result_notificaiton.guest', [], $locale) : $notifiable->name;
        $status = __("notifications.email.auth_result_notificaiton.status.{$authentication_status}", [], $locale);
        $url = url('/');

        $subject = __('notifications.email.auth_result_notificaiton.subject', [
            'status' => $status,
            ], $locale);
        $greeting = __('notifications.email.auth_result_notificaiton.greeting', [
                'name' => $name,
            ], $locale);
        $result = __('notifications.email.auth_result_notificaiton.result', [
                'status' => $status,
            ], $locale);
        $explain = __('notifications.email.auth_result_notificaiton.explain', [], $locale);
        $follow_up = __("notifications.email.auth_result_notificaiton.follow_up.{$authentication_status}", [], $locale);
        $action = __('notifications.email.auth_result_notificaiton.action', [], $locale);

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($result);

        if ($authentication_status === Authentication::REJECTED) {
            $message->error()->line($explain);
            if (!empty($this->reasons)) {
                foreach ($this->reasons as $reason) {
                    $message->line(__("messages.authentication.reject_reasons.{$reason}", [], $locale));
                }
            }
            if ($this->other_reason) {
                $message->line($this->other_reason);
            }
        }
        $message->line($follow_up)
            ->action($action, $url);

        return $message;
    }
}
