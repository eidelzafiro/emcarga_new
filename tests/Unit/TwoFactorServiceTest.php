<?php

namespace Tests\Unit;

use App\Services\TwoFactorService;
use PHPUnit\Framework\TestCase;

class TwoFactorServiceTest extends TestCase
{
    private TwoFactorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwoFactorService;
    }

    /**
     * Oráculo independiente (RFC 6238) para validar que el servicio
     * implementa TOTP correctamente, no solo de forma consistente.
     */
    private function totpOracle(string $base32Secret, int $timestamp): string
    {
        $key = $this->base32Decode($base32Secret);
        $counter = intdiv($timestamp, 30);
        $msg = '';
        for ($i = 7; $i >= 0; $i--) {
            $msg .= chr(($counter >> ($i * 8)) & 0xFF);
        }
        $hash = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper(rtrim($data, '='));
        $buffer = '';
        $bits = 0;
        $value = 0;
        foreach (str_split($data) as $char) {
            $index = strpos($alphabet, $char);
            if ($index === false) {
                continue;
            }
            $value = ($value << 5) | $index;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $buffer .= chr(($value >> $bits) & 0xFF);
            }
        }

        return $buffer;
    }

    /**
     * Vectores canónicos RFC 6238 (secreto "12345678901234567890" en Base32).
     */
    public static function rfc6238Vectors(): array
    {
        return [
            [59, '287082'],
            [1111111109, '081804'],
            [1111111111, '050471'],
            [1234567890, '005924'],
            [2000000000, '279037'],
        ];
    }

    public function test_verifica_vectores_rfc_6238(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        foreach (self::rfc6238Vectors() as [$timestamp, $esperado]) {
            $this->assertSame($esperado, $this->totpOracle($secret, $timestamp));
            $this->assertTrue($this->service->verify($secret, $esperado, $timestamp));
        }
    }

    public function test_rechaza_codigo_incorrecto(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->assertFalse($this->service->verify($secret, '000000', 1111111109));
    }

    public function test_ventana_de_tolerancia_un_minuto(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $codigo = $this->service->verify($secret, $this->totpOracle($secret, 1111111109), 1111111109 + 30);

        $this->assertTrue($codigo);
    }

    public function test_genera_secreto_base32_de_16_caracteres(): void
    {
        $secret = $this->service->generateSecret();
        $this->assertSame(16, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{16}$/', $secret);
    }

    public function test_genera_ocho_codigos_de_recuperacion(): void
    {
        $codigos = $this->service->generateRecoveryCodes();
        $this->assertCount(8, $codigos);
        foreach ($codigos as $codigo) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $codigo);
        }
    }
}
