<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SubscriptionCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Subscription $subscription) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activate Subscription',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $user = $this->subscription->user;
        $encryptedEmail = Crypt::encryptString($user->email);
        Log::info("USER SUBSCRIBED", [
            'email' => $user->email,
            'encrypted' => $encryptedEmail,
            'encrypted_urlencode' => urlencode($encryptedEmail),
        ]);

        return new Content(
            view: 'mails.subscription.created',
            with: [
                'url'  => env('FRONTEND_URL') . '/set-password' . '?token=' . urlencode($encryptedEmail),
                'user' => $user,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
