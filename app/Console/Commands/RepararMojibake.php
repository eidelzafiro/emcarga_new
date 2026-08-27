<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repara mojibake (doble/triple codificación UTF-8 / Windows-1252) en todas las
 * columnas de texto de la base de datos nueva. Idempotente: solo modifica los
 * valores que realmente contienen los marcadores de mojibake.
 *
 * Importante: recolecta primero (solo lectura) y actualiza al final, para no
 * alterar el conjunto del WHERE durante la paginación (lo que provocaría saltos).
 *
 * Uso:
 *   php artisan zafiro:reparar-mojibake           # simula y muestra conteo
 *   php artisan zafiro:reparar-mojibake --apply   # aplica los cambios
 *   php artisan zafiro:reparar-mojibake --tabla=clientes
 */
class RepararMojibake extends Command
{
    protected $signature = 'zafiro:reparar-mojibake
        {--apply : Aplica los cambios (sin esta opción solo simula)}
        {--tabla= : Solo una tabla}
        {--chunk=1000 : Tamaño de chunk}';

    protected $description = 'Repara caracteres mojibake (UTF-8 doble codificado) en la BD';

    /** Tablas de sistema que no deben tocarse. */
    private const DENYLIST = [
        'migrations', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'sessions',
        'password_histories', 'password_reset_tokens', 'model_has_roles',
        'role_has_permissions', 'permissions', 'roles',
    ];

    public function handle(): int
    {
        $aplicar = $this->option('apply');
        $soloTabla = $this->option('tabla');
        $chunk = (int) $this->option('chunk');

        $db = DB::connection()->getDatabaseName();

        $this->info($aplicar ? 'MODO APLICAR' : 'MODO SIMULACIÓN (--apply para escribir)');

        $cols = DB::select("
            SELECT TABLE_NAME AS t, COLUMN_NAME AS c
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND DATA_TYPE IN ('char','varchar','text','tinytext','mediumtext','longtext')
            ORDER BY TABLE_NAME, ORDINAL_POSITION
        ", [$db]);

        $totalCeldas = 0;
        $pendientes = []; // [tabla, columna, pk, valorArreglado]

        foreach ($cols as $col) {
            $tabla = $col->t;
            $columna = $col->c;

            if (in_array($tabla, self::DENYLIST, true)) {
                continue;
            }
            if ($soloTabla && $tabla !== $soloTabla) {
                continue;
            }

            $pk = $this->primaryKey($tabla);
            if ($pk === null) {
                $this->warn("  $tabla.$columna: sin PK, omitida");
                continue;
            }

            // Filtro binario para no sobre-matchear por acentos.
            $query = DB::table($tabla)
                ->whereRaw("`$columna` COLLATE utf8mb4_bin LIKE '%Ã%' OR `$columna` COLLATE utf8mb4_bin LIKE '%Â%'")
                ->select([$pk, $columna])
                ->orderBy($pk);

            $reparadas = 0;

            $query->chunk($chunk, function ($filas) use ($tabla, $columna, $pk, $aplicar, &$reparadas, &$pendientes) {
                foreach ($filas as $fila) {
                    $original = $fila->{$columna};
                    $arreglado = $this->reparar($original);
                    if ($arreglado === $original) {
                        continue;
                    }
                    $reparadas++;
                    if ($aplicar) {
                        $pendientes[] = [$tabla, $columna, $pk, $fila->{$pk}, $arreglado];
                    }
                }
            });

            if ($reparadas > 0) {
                $this->line("  <fg=cyan>$tabla.$columna</>: $reparadas celdas a reparar");
                $totalCeldas += $reparadas;
            }
        }

        if ($aplicar && $pendientes !== []) {
            $this->info('Aplicando ' . count($pendientes) . ' actualizaciones...');
            $bar = $this->output->createProgressBar(count($pendientes));
            foreach ($pendientes as [$tabla, $columna, $pk, $id, $valor]) {
                DB::table($tabla)->where($pk, $id)->update([$columna => $valor]);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info('TOTAL: ' . $totalCeldas . ' celdas ' . ($aplicar ? 'reparadas' : 'detectadas (simulación)'));

        return self::SUCCESS;
    }

    /**
     * Repara doble/triple codificación UTF-8 (mojibake) de forma idempotente.
     * Convierte de Windows-1252 a UTF-8 hasta 5 pasadas (cubre mojibake múltiple).
     */
    private function reparar(?string $valor): ?string
    {
        if ($valor === null || $valor === '' || ! mb_check_encoding($valor, 'UTF-8')) {
            return $valor;
        }
        if (! preg_match('/[ÃÂ]/u', $valor)) {
            return $valor;
        }

        $out = $valor;
        for ($i = 0; $i < 5; $i++) {
            $candidato = @mb_convert_encoding($out, 'Windows-1252', 'UTF-8');
            if ($candidato === false || $candidato === $out || ! mb_check_encoding($candidato, 'UTF-8')) {
                break;
            }
            $out = $candidato;
            if (! preg_match('/[ÃÂ]/u', $out)) {
                break;
            }
        }

        return $out;
    }

    private function primaryKey(string $tabla): ?string
    {
        try {
            $keys = DB::select("SHOW KEYS FROM `$tabla` WHERE Key_name = 'PRIMARY'");
        } catch (\Throwable $e) {
            return null;
        }

        if (! empty($keys)) {
            return $keys[0]->Column_name;
        }

        $cols = DB::select("SHOW COLUMNS FROM `$tabla`");
        return $cols[0]->Field ?? null;
    }
}
