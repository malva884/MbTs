<?php

namespace App\Mail;

use App\Models\QtSupplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeSupplierMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Passiamo l'intero oggetto Supplier
    public function __construct(public QtSupplier $supplier) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('portale.metallurgica@stl.tech', 'Metallurgica Bresciana S.p.a.'),
            cc: [
                //new Address('certificazioni.metallurgica@stl.tech'),
            ],
            subject: 'Portale Qualifica Fornitori / New Supplier Qualification Portal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email_welcome_supplier',
        );
    }
}
