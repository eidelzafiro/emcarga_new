<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordFuerte implements ValidationRule
{
    /**
     * Reglas portadas de Usuarios::strong_password() del sistema legacy:
     * mayúscula, minúscula, número, carácter especial y mínimo 6 caracteres.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $errores = [];

        if (! preg_match('@[A-Z]@', (string) $value)) {
            $errores[] = 'al menos una letra mayúscula';
        }

        if (! preg_match('@[a-z]@', (string) $value)) {
            $errores[] = 'al menos una letra minúscula';
        }

        if (! preg_match('@[0-9]@', (string) $value)) {
            $errores[] = 'al menos un número';
        }

        if (! preg_match('@[!\@#.$%\?&\*\(\)_\-\+=]@', (string) $value)) {
            $errores[] = 'al menos un carácter especial (!@#.$%?&*()_-+=)';
        }

        if (strlen((string) $value) < 6) {
            $errores[] = 'un mínimo de 6 caracteres';
        }

        if ($errores) {
            $fail('La contraseña debe contener: '.implode(', ', $errores).'.');
        }
    }
}
