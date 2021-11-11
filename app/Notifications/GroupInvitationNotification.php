<?php

namespace App\Notifications;

use Carbon\Carbon;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\GroupInvitation;

class GroupInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $group_invitation;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(GroupInvitation $group_invitation, $preferred_locale = 'en')
    {
        $this->group_invitation = $group_invitation;
        $this->locale = $preferred_locale;
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
        $group = $this->group_invitation->group;
        $locale = $this->locale;
        $username = $this->group_invitation->group->owner->username;
        $url = url("auth/register?invitation={$this->group_invitation->invitation_code}");

        $subject = __('notifications.email.group_invitation_notification.subject', [
                'group_name' => $group->name,
            ], $locale);
        $greeting = __('notifications.email.group_invitation_notification.greeting', [
            ], $locale);
        $content = __("notifications.email.group_invitation_notification.content", [
                'group_name' => $group->name,
                'username' => $username,
            ], $locale);
        $action = __('notifications.email.group_invitation_notification.action', [], $locale);
        $content2 = __("notifications.email.group_invitation_notification.content2", [
                'invitation_code' => $this->group_invitation->invitation_code,
            ], $locale);
        $content3 = __("notifications.email.group_invitation_notification.content3", [
                'expired_at' => $this->group_invitation->expired_at,
            ], $locale);

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content)
            ->action($action, $url)
            ->line($content2)
            ->line($content3);
    }
}
