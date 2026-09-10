<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sanea el mojibake UTF-8 (doble/triple codificación con cp1252 intermedio)
 * que quedó en textos de la BD tras las migraciones de agosto (catálogos,
 * areas, firmas, bolsa, cargos, entidades, grupos_escala...).
 *
 * Daño original: texto UTF-8 legido como latin1/cp1252 y re-codificado a
 * UTF-8 una o más veces ("Descripción" → "DescripciÃ³n" → "DescripciÃƒÂ³n"...).
 * Algunos strings pasaron además por htmlentities, lo que añade capas y
 * residuos ("DescripciÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³n").
 *
 * Inverso: por cada codepoint del string se emite su byte cp1252 (tabla
 * manual), y el flujo resultante ES el UTF-8 de la capa anterior. Se
 * repite hasta 6 veces mientras el resultado siga siendo UTF-8 válido,
 * y se remata con html_entity_decode si emergen entidades.
 *
 * Idempotente: solo modifica filas cuyo texto saneado queda SIN señales
 * de mojibake (Ã/â€/Â sueltos).
 */
class SanarMojibake extends Command
{
    protected $signature = 'zafiro:sanar-mojibake {--dry-run : Solo mostrar cambios}';

    protected $description = 'Sanea textos con mojibake UTF-8 (ÃƒÆ’...) en tablas de catálogo y RRHH';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $targets = [
            ['catalogo_items', ['nombre', 'codigo']],
            ['catalogo_tipos', ['titulo', 'fields']],
            ['areas', ['nombre']],
            ['firmas', ['nombre', 'confecciona_nombre', 'confecciona_cargo', 'revisa_nombre', 'revisa_cargo', 'aprueba_nombre', 'aprueba_cargo']],
            ['grupos_escala', ['nombre']],
            ['bolsa', ['nombre', 'apellidos', 'direccion']],
            ['cargos', ['nombre']],
            ['tipos_incidencias', ['nombre']],
            ['tipos_penalizaciones', ['nombre']],
            ['entidades', ['nombre', 'abreviatura']],
            ['menu_items', ['label']],
        ];

        $total = 0;
        foreach ($targets as [$tabla, $cols]) {
            if (! Schema::hasTable($tabla)) {
                continue;
            }
            foreach ($cols as $col) {
                if (! Schema::hasColumn($tabla, $col)) {
                    continue;
                }
                $rows = DB::table($tabla)
                    ->whereRaw("`{$col}` regexp 'Ã|â€|Â'")
                    ->get(['id', $col]);

                foreach ($rows as $r) {
                    $fix = $this->sanar((string) $r->{$col});
                    // Limpieza de nbsp/espacios residuales de los grupos escala
                    $fix = str_replace("\xC2\xA0", ' ', $fix);
                    $fix = preg_replace('/ {2,}/', ' ', $fix) ?? $fix;
                    $fix = trim($fix);

                    if ($fix === '' || $fix === $r->{$col} || preg_match('/Ã|â€/u', $fix)) {
                        continue;
                    }

                    $this->line("{$tabla}.{$col} #{$r->id}: '{$r->{$col}}' → '{$fix}'");
                    if (! $dry) {
                        DB::table($tabla)->where('id', $r->id)->update([$col => $fix]);
                    }
                    $total++;
                }
            }
        }

        $this->info($dry ? "DRY-RUN: {$total} filas cambiarían." : "Saneadas {$total} filas.");

        return self::SUCCESS;
    }

    /** Byte cp1252 de un codepoint (tabla manual). */
    private function cpByte(int $cp): ?int
    {
        if ($cp < 0x100) {
            return $cp; // latin1 == cp1252 para 0x00-0xFF
        }

        return match ($cp) {
            0x20AC => 0x80, 0x201A => 0x82, 0x0192 => 0x83, 0x201E => 0x84,
            0x2026 => 0x85, 0x2020 => 0x86, 0x2021 => 0x87, 0x02C6 => 0x88,
            0x2030 => 0x89, 0x0160 => 0x8A, 0x2039 => 0x8B, 0x0152 => 0x8C,
            0x017D => 0x8E, 0x2018 => 0x91, 0x2019 => 0x92, 0x201C => 0x93,
            0x201D => 0x94, 0x2022 => 0x95, 0x2013 => 0x96, 0x2014 => 0x97,
            0x02DC => 0x98, 0x2122 => 0x99, 0x0161 => 0x9A, 0x203A => 0x9B,
            0x0153 => 0x9C, 0x017E => 0x9E, 0x0178 => 0x9F,
            default => null,
        };
    }

    /** Revierte UNA capa de doble-codificación (null si no es reversible). */
    private function unaCapa(string $s): ?string
    {
        $out = '';
        $i = 0;
        $n = strlen($s);
        while ($i < $n) {
            $b = ord($s[$i]);
            if ($b < 0x80) {
                $out .= $s[$i];
                $i++;

                continue;
            }
            if ($b >= 0xE0 && $i + 2 < $n) {
                $c = (($b & 0x0F) << 12) | ((ord($s[$i + 1]) & 0x3F) << 6) | (ord($s[$i + 2]) & 0x3F);
                $i += 3;
            } elseif ($b >= 0xC0 && $i + 1 < $n) {
                $c = (($b & 0x1F) << 6) | (ord($s[$i + 1]) & 0x3F);
                $i += 2;
            } else {
                return null;
            }
            $m = $this->cpByte($c);
            if ($m === null) {
                return null; // codepoint sin mapeo cp1252 → capa no reversible
            }
            $out .= chr($m);
        }

        return mb_check_encoding($out, 'UTF-8') ? $out : null;
    }

    /** Sana un string (hasta 6 capas + entidades). */
    private function sanar(string $s): string
    {
        for ($i = 0; $i < 6; $i++) {
            if (! preg_match('/\xC3[\x80-\xFF]|\xE2\x80[\x98-\x9F]/', $s)) {
                break;
            }
            $t = $this->unaCapa($s);
            if ($t === null || $t === $s) {
                break;
            }
            $s = $t;
        }
        $h = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
        if ($h !== $s && mb_check_encoding($h, 'UTF-8') && ! preg_match('/Ã|â€/u', $h)) {
            $s = $h;
        }

        return $s;
    }
}
