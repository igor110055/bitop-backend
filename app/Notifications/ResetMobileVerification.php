<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\NexmoMessage;

use App\Channels\Messages\SmsMessage;
use App\Models\Verification;

class ResetMobileVerification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $verification;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Verification $verification, string $locale)
    {
        $this->verification = $verification;
        $this->locale = $locale;
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

    public function toNexmo($notifiable)
    {
        $content = __('notifications.sms.reset_mobile_verification', [
            'code' => $this->verification->code,
        ], $this->locale);

        return (new NexmoMessage)
            ->content($content)
            ->unicode();
    }

    public function toSms($notifiable)
    {
        $content = __('notifications.sms.reset_mobile_verification', [
            'code' => $this->verification->code,
        ], $this->locale);
        return new SmsMessage($content);
    }
}
