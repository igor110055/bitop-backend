<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\{
    Advertisement,
    AdminAction,
    SystemAction,
};

class AdvertisementUnavailableNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $advertisement;
    protected $role;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Advertisement $advertisement, string $role = AdminAction::class)
    {
        $this->advertisement = $advertisement;
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

        $subject = __('notifications.email.ad_unavailable_notification.subject', [
                'advertisement_id' => $this->advertisement->id,
            ], $locale);
        $greeting = __('notifications.email.ad_unavailable_notification.greeting', [
                'advertisement_id' => $this->advertisement->id,
            ], $locale);

        if ($this->role === AdminAction::class) {
            $content = __("notifications.email.ad_unavailable_notification.admin.content", [
                'advertisement_id' => $this->advertisement->id,
            ], $locale);
        } elseif ($this->role === SystemAction::class) {
            $content = __("notifications.email.ad_unavailable_notification.system.content", [
                'advertisement_id' => $this->advertisement->id,
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
