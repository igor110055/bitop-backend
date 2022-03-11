<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;
use App\Models\{
    Verification,
    Transfer,
};

class TransferVerification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $verification;
    protected $transfer;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Verification $verification, Transfer $transfer)
    {
        $this->verification = $verification;
        $this->transfer = $transfer;
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
        $locale = $notifiable->preferred_locale;
        $id = $this->verification->id;
        $code = $this->verification->code;
        $url = url("api/transfers/confirm/${id}/${code}");

        $subject = __('notifications.email.transfer_verification.subject', [
            'time' => datetime(Carbon::now()),
        ], $locale);
        $greeting = __('notifications.email.transfer_verification.greeting', [], $locale);
        $content1 = __('notifications.email.transfer_verification.content1', [], $locale);
        $content2 = __('notifications.email.transfer_verification.content2', [], $locale);
        $action = __('notifications.email.transfer_verification.action', [], $locale);
        $content3 = __('notifications.email.transfer_verification.content3', [], $locale);

        $data = '<table style="margin: 20px 0; font-size: 14px; border-spacing: 10px; border: none; color: #3d4852">';
        $data .= "<tr><td>".__('common.transfer', [], $locale)." ID</td><td>{$this->transfer->id}</td></tr>";
        $data .= "<tr><td>".__('common.amount', [], $locale)."</td><td>".comma_format(trim_zeros($this->transfer->amount)).' '.$this->transfer->coin."</td></tr>";
        $data .= "<tr><td>".__('common.dst_user', [], $locale)." ID</td><td>{$this->transfer->dst_user_id}</td></tr>";
        $data .= "<tr><td>".__('common.dst_user', [], $locale)."</td><td>{$this->transfer->dst_user->username}</td></tr>";
        $data .= '</table>';

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($content1)
            ->line(new HtmlString($data))
            ->line($content2)
            ->action($action, $url)
            ->line($content3);
    }
}
