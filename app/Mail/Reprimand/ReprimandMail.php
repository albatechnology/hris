<?php

namespace App\Mail\Reprimand;

use App\Models\Reprimand;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReprimandMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private User $user, private Reprimand $reprimand)
    {
        $this->user->load([
            'department' => fn($q) => $q->select('id', 'name'),
            'position' => fn($q) => $q->select('id', 'name'),
        ]);
        // $this->user->load(['positions' => fn($q) => $q->with([
        //     'department' => fn($q) => $q->select('id', 'name'),
        //     'position' => fn($q) => $q->select('id', 'name'),
        // ])]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reprimand',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $department = $this->user->department ? $this->user->department->name : null;
        $position = $this->user->position ? $this->user->position->name : null;
        // $position = null;
        // $department = null;
        // if ($this->user->positions->count()) {
        //     $position = $this->user->positions[0]->position?->name ?? null;
        //     $department = $this->user->positions[0]->department?->name ?? null;
        // }

        return new Content(
            view: 'mails.reprimand.reprimand',
            with: [
                'number' => rand(100, 999),
                'user_name' => $this->user->name,
                'user_title' => $this->user->gender->getTitle(),
                'department' => $department,
                'position' => $position,
                'download_url' => $this->reprimand->file  && isset($this->reprimand->file['url']) ?  $this->reprimand->file['url'] : null,
            ],
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
