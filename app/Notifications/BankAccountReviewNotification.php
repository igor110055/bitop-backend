<?php

namespace App\Notifications;

use Carbon\Carbon;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\Models\BankAccount;

class BankAccountReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bank;
    protected $action;
    protected $reasons;
    protected $other_reason;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($action, $bank, $reasons = [], $other_reason = null)
    {
        $this->action = $action;
        $this->bank = $bank;
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
        $bank_name = data_get($this->bank->foreign_name, $locale, $this->bank->name);
        $name = empty($notifiable->first_name) ? __('notifications.email.bank_account_verification_notificaiton.guest', [], $locale) : $notifiable->name;
        $status = __("notifications.email.bank_account_verification_notificaiton.status.{$this->action}", [], $locale);
        $url = url('/');

        $subject = __('notifications.email.bank_account_verification_notificaiton.subject', [], $locale)." - {$status}";
        $greeting = __('notifications.email.bank_account_verification_notificaiton.greeting', [
                'name' => $name,
            ], $locale);
        $result = __("notifications.email.bank_account_verification_notificaiton.result.{$this->action}", ['bank' => $bank_name], $locale);
        $explain = __('notifications.email.bank_account_verification_notificaiton.explain', [], $locale);
        $follow_up = __("notifications.email.bank_account_verification_notificaiton.follow_up.{$this->action}", [], $locale);
        $action = __('notifications.email.bank_account_verification_notificaiton.action', [], $locale);

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($result);

        if ($this->action === 'reject') {
            $message->error()->line($explain);
            if (!empty($this->reasons)) {
                foreach ($this->reasons as $reason) {
                    $message->line(__("messages.bank_account.reject_reasons.{$reason}", [], $locale));
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
