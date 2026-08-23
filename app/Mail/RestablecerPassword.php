<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo de restablecimiento de contraseña.
 *
 * El transporte (nacional .cu / Gmail) lo elige MailRouter al momento de
 * enviar; este Mailable solo define el contenido y el remitente según el
 * transporte usado.
 */
class RestablecerPassword extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $usuario,
        public string $urlRestablecer,
        public string $transporte = 'default',
    ) {}

    public function envelope(): Envelope
    {
        $remitente = $this->transporte === 'nacional'
            ? config('mail.from.nacional', ['address' => config('mail.from.address'), 'name' => config('mail.from.name')])
            : ($this->transporte === 'gmail'
                ? config('mail.from.gmail', ['address' => config('mail.from.address'), 'name' => config('mail.from.name')])
                : ['address' => config('mail.from.address'), 'name' => config('mail.from.name')]);

        return new Envelope(
            from: $remitente['address'],
            replyTo: [$remitente['address']],
            subject: __('Restablecimiento de contraseña — :app', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.restablecer-password',
        );
    }
}
