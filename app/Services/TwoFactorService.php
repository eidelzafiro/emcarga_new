<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * 2FA (TOTP, RFC 6238) implementado en PHP puro (sin librerías externas, por el
 * entorno offline). Genera el secreto en Base32, calcula el código de 6 dígitos
 * por intervalo de 30 s y verifica con una ventana de tolerancia.
 *
 * Diseñado para perfiles privilegiados (SUPERADMIN / CONFIGURACIONES).
 */
class TwoFactorService
{
    private const DIGITS = 6;

    private const PERIOD = 30;

    private const WINDOW = 1; // intervalos de tolerancia (±30 s)

    private const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Genera un secreto Base32 aleatorio de 16 caracteres (~80 bits).
     */
    public function generateSecret(): string
    {
        $secret = '';
        $bytes = random_bytes(10);
        foreach (str_split($bytes, 1) as $b) {
            $secret .= self::B32[ord($b) & 31];
        }

        return $secret;
    }

    /**
     * URI otpauth para apps autenticadoras (Google Authenticator, FreeOTP, etc.).
     */
    public function otpauthUri(User $user, string $secret): string
    {
        $label = rawurlencode('EMCARGA:'.$user->username);
        $issuer = rawurlencode('EMCARGA');

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&period=".self::PERIOD.'&digits='.self::DIGITS;
    }

    /**
     * Verifica un código contra el secreto, con ventana de tolerancia.
     */
    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $counter = intdiv(time(), self::PERIOD);
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if ($this->totp($secret, $counter + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica un código de recuperación de un solo uso.
     */
    public function verifyRecovery(User $user, string $code): bool
    {
        $recovery = $user->two_factor_recovery ?? [];
        $code = strtoupper(Str::afterLast($code, '-'));

        foreach ($recovery as $i => $stored) {
            if (hash_equals(strtoupper($stored), $code)) {
                // Consume el código usado.
                $restantes = $recovery;
                unset($restantes[$i]);
                $user->update(['two_factor_recovery' => array_values($restantes)]);

                return true;
            }
        }

        return false;
    }

    /**
     * Genera 8 códigos de recuperación con formato XXXX-XXXX.
     */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))->map(
            fn () => strtoupper(Str::random(4).'-'.Str::random(4))
        )->all();
    }

    private function totp(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $msg = $this->intToBytes($counter);

        $hash = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $bin = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($bin % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(rtrim($secret, '='));
        $buffer = '';
        $bits = 0;
        $value = 0;

        foreach (str_split($secret) as $char) {
            $idx = strpos(self::B32, $char);
            if ($idx === false) {
                continue;
            }
            $value = ($value << 5) | $idx;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $buffer .= chr(($value >> $bits) & 0xFF);
            }
        }

        return $buffer;
    }

    private function intToBytes(int $n): string
    {
        $bytes = '';
        for ($i = 7; $i >= 0; $i--) {
            $bytes .= chr(($n >> ($i * 8)) & 0xFF);
        }

        return $bytes;
    }
}
