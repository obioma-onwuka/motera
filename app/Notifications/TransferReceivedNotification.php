<?php

namespace App\Notifications;

use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Transaction $transaction, public BankAccount $counterpartyAccount)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->transaction->amount, 2);

        return (new MailMessage)
            ->subject('You received $'.$amount)
            ->greeting("Hello, {$notifiable->name}!")
            ->line('You have received a transfer to your account.')
            ->line('Amount: $'.$amount)
            ->line('Reference: '.$this->transaction->reference)
            ->line('From account: '.$this->counterpartyAccount->account_number)
            ->action('View Transactions', url(route('transactions.index')))
            ->line('Thank you for using MOTERA!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Transfer Received',
            'amount' => (string) $this->transaction->amount,
            'reference' => $this->transaction->reference,
            'counterparty_account_number' => $this->counterpartyAccount->account_number,
            'message' => 'You received $'.number_format((float) $this->transaction->amount, 2),
        ];
    }
}
