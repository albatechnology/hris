<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

class SetupPasswordMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Set up your password!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // $encryptedEmail = urlencode(base64_encode(openssl_encrypt($this->user->email, 'AES-128-CBC', env('CRYPT_SECRET_KEY'), OPENSSL_RAW_DATA, env('CRYPT_IV'))));

        $encryptedEmail = Crypt::encryptString($this->user->email);

        return new Content(
            view: 'mails.setup-password',
            with: [
                'url'  => env('FRONTEND_URL') . '/set-password' . '?token=' . urlencode($encryptedEmail),
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
