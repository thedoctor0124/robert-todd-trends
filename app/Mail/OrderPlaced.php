<?php

namespace App\Mail;

use App\Models\Purchase;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Purchase|Subscription $order,
        public string $orderType,
    ) {}

    public function envelope(): Envelope
    {
        $needsPost = $this->order->delivery_required ? 'POST REQUIRED' : 'Digital only';
        $itemName = $this->orderType === 'purchase'
            ? $this->order->publication->title
            : $this->order->season->name.' '.$this->order->season->year;

        return new Envelope(
            subject: "New order: {$itemName} ({$needsPost})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-placed',
        );
    }
}
