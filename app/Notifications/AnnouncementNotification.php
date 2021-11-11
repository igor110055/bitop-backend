<?php

namespace App\Notifications;

use Carbon\Carbon;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\{
    User,
    Announcement,
};

class AnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $announcement;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
        $this->queue = config('core.broadcast.queue');
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
        try {
        $locale = $notifiable->preferred_locale;
        $url = url('/');
        $content = $this->announcement
            ->announcement_contents()
            ->where('locale', $locale)
            ->first();
        if (is_null($content)) {
            $content = $this->announcement
                ->announcement_contents()
                ->where('locale', \App::getLocale())
                ->first();
        }

        $subject = __('notifications.email.announcement_notification.subject', [
            'title' => $content->title,
            'time' => $this->announcement->released_at->toDateTimeString(),
            ], $locale);
        $greeting = __('notifications.email.announcement_notification.greeting', [
                'name' => $notifiable->username,
            ], $locale);
        $action = __('notifications.email.announcement_notification.action', [], $locale);
        $email_content = __('notifications.email.announcement_notification.content', [
            'content' => strip_tags($content->content),
        ], $locale);

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($email_content)
            ->action($action, $url);

        } catch (\Throwable $e) {
            \Log::alert($e->getMessage());
        }
    }
}
