<?php

namespace App\Notifications\Compliance;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\KycSubmission;

class KycStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public KycSubmission $submission)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = ucfirst($this->submission->status);
        $message = $this->submission->status === 'approved' 
            ? 'Congratulations! Your identity verification has been approved. Your account limits have been increased.'
            : 'Unfortunately, your identity verification was rejected. Reason: ' . $this->submission->admin_note;

        return (new MailMessage)
            ->subject("Identity Verification {$status}")
            ->greeting("Hello, {$notifiable->name}!")
            ->line($message)
            ->action('View Dashboard', url(route('dashboard')))
            ->line('Thank you for using MOTERA!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Identity Verification ' . ucfirst($this->submission->status),
            'message' => $this->submission->status === 'approved' 
                ? 'Your KYC documents have been verified.' 
                : 'Your KYC submission was rejected.',
            'type' => 'compliance',
        ];
    }
}
