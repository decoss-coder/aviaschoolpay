<?php

namespace App\Mail;

use App\Models\Paiement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaiementDirectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Paiement $paiement,
        public string $subjectLine,
        public string $bodyMessage,
        public string $variant = 'confirme',
    ) {
        $this->paiement->loadMissing(['eleve', 'etablissement', 'inscription.classe']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.paiements.direction',
            with: [
                'paiement' => $this->paiement,
                'bodyMessage' => $this->bodyMessage,
                'variant' => $this->variant,
                'url' => route('paiements.show', $this->paiement),
            ],
        );
    }
}
