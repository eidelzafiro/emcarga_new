<?php

namespace App\Support;

/**
 * Enrutamiento de transporte de correo por dominio del destinatario.
 *
 * Regla de negocio (2026-08-23):
 *   - Destinatario con correo terminado en ".cu" → servidor SMTP nacional.
 *   - Resto (gmail.com, etc.) → SMTP de Gmail.
 *   - Si el transporte elegido no tiene host configurado (credenciales
 *     pendientes) o no hay destinatario válido, cae al mailer por defecto
 *     (actualmente "log", útil para probar sin red).
 */
class MailRouter
{
    public static function paraEmail(?string $email): string
    {
        $email = strtolower(trim((string) $email));

        // El SMTP nacional puede ser un relay sin autenticación (basta host);
        // Gmail SIEMPRE exige usuario + contraseña de aplicación.
        $nacionalDisponible = (bool) config('mail.mailers.nacional.host');
        $gmailDisponible = (bool) (config('mail.mailers.gmail.username')
            && config('mail.mailers.gmail.password'));

        if ($email !== '' && str_ends_with($email, '.cu') && $nacionalDisponible) {
            return 'nacional';
        }

        if ($email !== '' && ! str_ends_with($email, '.cu') && $gmailDisponible) {
            return 'gmail';
        }

        return (string) config('mail.default');
    }
}
