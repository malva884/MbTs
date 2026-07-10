<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProduzioneUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public $idProduzione;
    public $updateType;
    public $userName;
    public $ordine;

    /**
     * Create a new message instance.
     */
    public function __construct($idProduzione, $updateType, $userName = null, $ordine = null)
    {
        $this->idProduzione = $idProduzione;
        $this->updateType = $updateType;
        $this->userName = $userName;
        $this->ordine = $ordine;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->updateType) {
            'fabbisogni' => 'Aggiornamento Fabbisogni - Produzione ID: ' . $this->idProduzione,
            'avanzamento_fabbisogni' => 'Aggiornamento Avanzamento e Fabbisogni - Produzione ID: ' . $this->idProduzione,
            default => 'Aggiornamento Produzione - ID: ' . $this->idProduzione,
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.email_produzione_update',
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
