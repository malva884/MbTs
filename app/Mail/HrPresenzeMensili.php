<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HrPresenzeMensili extends Mailable
{
    use Queueable, SerializesModels;

    public $meseDescrizione;
    public $filePath;

    public function __construct(string $meseDescrizione, string $filePath)
    {
        $this->meseDescrizione = $meseDescrizione;
        $this->filePath = $filePath;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Report Presenze Mensili - ' . $this->meseDescrizione,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email_presenze_mensili',
        );
    }

    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromPath($this->filePath),
        ];
    }
}
