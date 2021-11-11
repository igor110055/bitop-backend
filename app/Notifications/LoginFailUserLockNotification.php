<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\UserLock;

class LoginFailUserLockNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $user_lock;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(UserLock $user_lock)
    {
        $this->user_lock = $user_lock;
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
        $subject = __('notifications.email.login_fail_user_lock.subject', [
            'time' => Carbon::now()->toDateTimeString(),
        ], $locale);
        $greeting = __('notifications.email.login_fail_user_lock.greeting', [
            'name' => $notifiable->username,
        ], $locale);
        $content = __("notifications.email.login_fail_user_lock.content", [
            'IP' => $this->user_lock->ip,
        ], $locale);
        $content2 = __("notifications.email.login_fail_user_lock.content2", [
        ], $locale);
        $content3 = __("notifications.email.login_fail_user_lock.content3", [
        ], $locale);

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content)
            ->line($content2)
            ->line($content3);
    }
}
