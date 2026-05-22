<?php

namespace App\Mail;

use App\Models\AccessInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FreeAccessInvite extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AccessInvite $invite,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your complimentary access to {$this->invite->itemTitle()} — ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.free-access-invite',
        );
    }
}
