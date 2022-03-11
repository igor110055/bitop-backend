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
    Withdrawal,
};

class WithdrawalVerification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $verification;
    protected $withdrawal;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Verification $verification, Withdrawal $withdrawal)
    {
        $this->verification = $verification;
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
        $url = url("api/withdrawals/confirm/${id}/${code}");

        $subject = __('notifications.email.withdrawal_verification.subject', [
            'time' => datetime(Carbon::now()),
        ], $locale);
        $greeting = __('notifications.email.withdrawal_verification.greeting', [], $locale);
        $content1 = __('notifications.email.withdrawal_verification.content1', [
            'amount' => comma_format(trim_zeros($this->withdrawal->amount)),
            'coin' => $this->withdrawal->coin,
        ], $locale);

        $data = '<table style="margin: 20px 0; font-size: 14px; border-spacing: 10px; border: none; color: #3d4852">';
        $data .= "<tr><td>".__('common.withdrawal', [], $locale)." ID</td><td>{$this->withdrawal->id}</td></tr>";
        $data .= "<tr><td>".__('common.amount', [], $locale)."</td><td>".comma_format(trim_zeros($this->withdrawal->amount)).' '.$this->withdrawal->coin."</td></tr>";
        $data .= "<tr><td>".__('common.address', [], $locale)."</td><td>{$this->withdrawal->address}</td></tr>";
        if (!is_null(data_get($this->withdrawal, 'tag'))) {
            $data .= "<tr><td>Tag</td><td>{$this->withdrawal->tag}</td></tr>";
        }
        $data .= '</table>';

        $content2 = __('notifications.email.withdrawal_verification.content2', [], $locale);
        $action = __('notifications.email.withdrawal_verification.action', [], $locale);
        $content3 = __('notifications.email.withdrawal_verification.content3', [], $locale);

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
