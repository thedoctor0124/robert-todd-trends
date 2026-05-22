<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FreeAccessGranted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $accessType,
        public $item,
    ) {}

    public function envelope(): Envelope
    {
        $itemName = $this->accessType === 'publication'
            ? $this->item->title
            : $this->item->name.' ('.$this->item->year.')';

        return new Envelope(
            subject: "You've been granted access to {$itemName} — LoopTrends",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.free-access-granted',
        );
    }
}
