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

class SP1Mail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(private User $user, private Reprimand $reprimand)
    {
        $this->user->load(['positions' => fn($q) => $q->with([
            'department' => fn($q) => $q->select('id', 'name'),
            'position' => fn($q) => $q->select('id', 'name'),
        ])]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reprimand: SP 1',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $repMonthTypes = [];
        $ee = $this->reprimand->month_type;

        while (!is_null($ee)) {
            $repMonthTypes[] = $ee;
            $ee = $ee->previous();
        }

        dump($ee);
        dump($repMonthTypes);

        $allReprimands = [];
        $month = date('m', strtotime($this->reprimand->effective_date));
        $year = date('Y', strtotime($this->reprimand->effective_date));
        dump($month);
        dump($year);
        foreach ($repMonthTypes as $m) {
            // $rep = Reprimand::where
        }


        dd($this->reprimand);
        // $reps =

        $position = null;
        $department = null;
        if ($this->user->positions->count()) {
            $position = $this->user->positions[0]->position?->name ?? null;
            $department = $this->user->positions[0]->department?->name ?? null;
        }

        return new Content(
            view: 'mails.reprimand.sp-1',
            with: [
                'number' => rand(100, 999),
                'user_name' => $this->user->name,
                'user_title' => $this->user->gender->getTitle(),
                'position' => $position,
                'department' => $department,
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
