<?php

namespace App\Services\Etl;

use phpseclib3\Crypt\Rijndael;

/**
 * Descifra valores generados por CI_Encrypt (CodeIgniter 2/3, librería
 * legacy basada en mcrypt) usados por EMCARGA para las contraseñas.
 *
 * Algoritmo legacy (system/libraries/Encrypt.php):
 *
 *   encode($string):
 *     $key  = md5(config['encryption_key'])          // 'PRUEBA'
 *     $iv   = random(32 bytes)                        // RIJNDAEL_256 => bloque 32
 *     $data = $iv . mcrypt_encrypt(RIJNDAEL_256, $key, $string, CBC, $iv)
 *     $data = add_cipher_noise($data, $key)           // suma byte a byte con sha1($key)
 *     return base64_encode($data)
 *
 * mcrypt fue removido de PHP 7.2+; Rijndael-256-CBC (bloque de 256 bits)
 * no existe en OpenSSL, por eso se usa phpseclib.
 */
class LegacyDecryptor
{
    /** Tamaño de bloque/IV de MCRYPT_RIJNDAEL_256 (32 bytes). */
    private const IV_SIZE = 32;

    public function __construct(
        private readonly string $encryptionKey = 'PRUEBA',
    ) {}

    /**
     * Descifra un valor base64 producido por CI_Encrypt::encode().
     * Devuelve null si el valor no es descifrable.
     */
    public function decrypt(string $encoded): ?string
    {
        $data = base64_decode($encoded, true);

        if ($data === false || strlen($data) <= self::IV_SIZE) {
            return null;
        }

        $key = md5($this->encryptionKey);
        $data = $this->removeCipherNoise($data, $key);

        $iv = substr($data, 0, self::IV_SIZE);
        $ciphertext = substr($data, self::IV_SIZE);

        $cipher = new Rijndael('cbc');
        $cipher->setBlockLength(256);
        $cipher->setKey($key);
        $cipher->setIV($iv);
        $cipher->disablePadding(); // el legacy no aplica padding PKCS7; rellena con \0

        $plain = $cipher->decrypt($ciphertext);

        return $plain === false ? null : rtrim($plain, "\0");
    }

    /**
     * Inversa de _add_cipher_noise(): resta byte a byte el hash sha1 de
     * la clave (cadena hexadecimal de 40 caracteres), en módulo 256.
     */
    private function removeCipherNoise(string $data, string $key): string
    {
        $hash = sha1($key);
        $hashLength = strlen($hash);
        $result = '';

        for ($i = 0, $j = 0, $ld = strlen($data); $i < $ld; $i++, $j++) {
            if ($j >= $hashLength) {
                $j = 0;
            }

            $temp = ord($data[$i]) - ord($hash[$j]);

            if ($temp < 0) {
                $temp += 256;
            }

            $result .= chr($temp);
        }

        return $result;
    }
}
