<?php

declare(strict_types=1);

namespace App\Mail\Glc;

use App\Models\Glc\PlacementResultLink;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PlacementResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PlacementResultLink $link) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Placement Test Result — Greats Language Center',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'glc.emails.placement-result',
            with: [
                'candidateName' => $this->link->attempt->candidate_name,
                'url' => route('placement.result.show', $this->link->token),
                'expiresAt' => $this->link->expires_at->format('j F Y'),
            ],
        );
    }
}
