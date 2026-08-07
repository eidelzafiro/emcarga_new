<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Salva y restaura la base de datos MySQL (emcarga_new).
 *
 * Los dumps se guardan en database/backups/ con formato:
 *   emcarga_new_AAAA-MM-DD_HH-MM(-sufijo).sql
 *
 * Si mysqldump está disponible en el entorno (producción), se usa por rapidez
 * y fidelidad. En el contenedor app (sin binario) degrada a un dump propio
 * generado desde la conexión PDO, incluyendo estructura + datos con
 * SET FOREIGN_KEY_CHECKS=0 para preservar los ids legacy.
 *
 * Uso:
 *   php artisan zafiro:salva             -> crea una salva completa
 *   php artisan zafiro:salva --restaurar -> restaura la última salva
 *   php artisan zafiro:salva --listar    -> lista las salvaciones
 */
class DatabaseBackupService
{
    public function directorio(): string
    {
        return database_path('backups');
    }

    public function ultimoArchivo(): ?string
    {
        $archivos = $this->listar();

        return $archivos[0] ?? null;
    }

    public function listar(): array
    {
        $dir = $this->directorio();

        if (! is_dir($dir)) {
            return [];
        }

        $archivos = glob($dir.'/emcarga_new_*.sql');

        rsort($archivos);

        return array_values($archivos);
    }

    public function salvar(?string $sufijo = null): string
    {
        $dir = $this->directorio();

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $fecha = date('Y-m-d_H-i-s');
        $archivo = $dir.'/emcarga_new_'.$fecha.($sufijo ? '_'.$sufijo : '').'.sql';

        // Prioridad: binario mysqldump si existe (producción), si no dump PDO.
        if ($this->todosMysqldump()) {
            $this->dumpConBin($archivo);
        } else {
            $this->salvarConPdo($archivo);
        }

        return $archivo;
    }

    public function restaurar(?string $archivo = null): string
    {
        $archivo = $archivo ?: $this->ultimoArchivo();

        if (! $archivo || ! is_file($archivo)) {
            throw new \RuntimeException('No hay ninguna salva disponible para restaurar.');
        }

        $sql = file_get_contents($archivo);

        if ($sql === false || trim($sql) === '') {
            throw new \RuntimeException('La salva está vacía o corrupta.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->partirSql($sql) as $sentencia) {
            try {
                DB::statement($sentencia);
            } catch (\Throwable $e) {
                // Continúa: algunas sentencias pueden depender de otras ya aplicadas.
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return $archivo;
    }

    private function todosMysqldump(): bool
    {
        return ! empty(shell_exec('command -v mysqldump 2>/dev/null'));
    }

    private function dumpConBin(string $archivo): void
    {
        $config = config('database.connections.mysql');
        $cmd = sprintf(
            "mysqldump -h '%s' -P %d -u '%s' -p'%s' --single-transaction --routines --triggers --no-tablespaces '%s' > '%s'",
            $config['host'],
            $config['port'] ?? 3306,
            $config['username'],
            $config['password'],
            $config['database'],
            $archivo
        );

        // mysqldump avisa del password en CLI a stderr; no debe considerarse error.
        $out = shell_exec($cmd.' 2>&1');

        if ($out !== null && str_contains($out, 'mysqldump:') && ! str_contains($out, 'Warning')) {
            @unlink($archivo);
            throw new \RuntimeException('Falló la salva vía mysqldump: '.trim($out));
        }

        if (! is_file($archivo) || filesize($archivo) === 0) {
            @unlink($archivo);
            throw new \RuntimeException('No se generó la salva vía mysqldump.');
        }
    }

    /**
     * Dump propio desde la conexión PDO: estructura + datos, preservando ids.
     */
    private function salvarConPdo(string $archivo): void
    {
        $bd = config('database.connections.mysql.database');
        $pdo = DB::connection()->getPdo();

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $contenido = [];
        $contenido[] = '-- Zafiro dump (php) '.date('Y-m-d H:i:s');
        $contenido[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $contenido[] = '';
        $contenido[] = "CREATE DATABASE IF NOT EXISTS `{$bd}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        $contenido[] = "USE `{$bd}`;";
        $contenido[] = '';

        $tablas = DB::select('SHOW TABLES');
        $key = array_keys((array) $tablas[0])[0];

        foreach ($tablas as $t) {
            $tabla = $t->{$key};
            $this->dumpTabla($pdo, $tabla, $contenido);
        }

        $contenido[] = 'SET FOREIGN_KEY_CHECKS=1;';

        file_put_contents($archivo, implode("\n", $contenido));

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function dumpTabla(\PDO $pdo, string $tabla, array &$contenido): void
    {
        $crear = $pdo->query("SHOW CREATE TABLE `{$tabla}`")->fetch(\PDO::FETCH_ASSOC);
        $createSql = array_values($crear)[1] ?? '';
        $createSql = preg_replace('/AUTO_INCREMENT=\d+/', 'AUTO_INCREMENT=1', $createSql);

        $contenido[] = 'DROP TABLE IF EXISTS `'.$tabla.'`;';
        $contenido[] = $createSql.';';
        $contenido[] = '';

        $filas = $pdo->query("SELECT * FROM `{$tabla}`");
        $columnas = $this->columnasDe($pdo, $tabla);

        $batch = [];
        foreach ($filas as $fila) {
            $batch[] = '('.$this->valuesFila($fila, $columnas).')';

            if (count($batch) >= 200) {
                $this->flushBatch($pdo, $tabla, $columnas, $batch, $contenido);
            }
        }

        if ($batch) {
            $this->flushBatch($pdo, $tabla, $columnas, $batch, $contenido);
        }
    }

    private function columnasDe(\PDO $pdo, string $tabla): array
    {
        $cols = $pdo->query("SHOW COLUMNS FROM `{$tabla}`")->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn ($c) => $c['Field'], $cols);
    }

    private function valuesFila(array $fila, array $columnas): string
    {
        $partes = [];
        foreach ($columnas as $col) {
            $val = $fila[$col] ?? null;
            if ($val === null) {
                $partes[] = 'NULL';
            } elseif (is_int($val) || is_float($val)) {
                $partes[] = (string) $val;
            } else {
                $partes[] = "'".addslashes((string) $val)."'";
            }
        }

        return implode(',', $partes);
    }

    private function flushBatch(\PDO $pdo, string $tabla, array $columnas, array &$batch, array &$contenido): void
    {
        $cols = '`'.implode('`,`', $columnas).'`';
        $contenido[] = "INSERT INTO `{$tabla}` ({$cols}) VALUES\n".implode(",\n", $batch).';';
        $contenido[] = '';
        $batch = [];
    }

    /**
     * Divide un dump SQL en sentencias individuales respetando delimitadores
     * y cadenas de texto (para triggers/procedimientos con ';' internos).
     */
    private function partirSql(string $sql): array
    {
        $sentencias = [];
        $actual = '';
        $delimiter = ';';
        $len = strlen($sql);
        $i = 0;
        $enString = null; // null, "'", '"' o '`'

        while ($i < $len) {
            $char = $sql[$i];

            // Delimitador DELIMITER (línea propia al inicio)
            if ($char === "\n" || $char === "\r") {
                $ltrim = ltrim($actual);
                if (preg_match('/^DELIMITER\s+(\S+)\s*$/i', $ltrim, $m)) {
                    $delimiter = $m[1];
                    $actual = '';
                    $i++;
                    continue;
                }
            }

            if ($enString !== null) {
                $actual .= $char;
                if ($char === '\\' && $i + 1 < $len) {
                    $actual .= $sql[++$i];
                } elseif ($char === $enString) {
                    $enString = null;
                }
                $i++;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $enString = $char;
                $actual .= $char;
                $i++;
                continue;
            }

            // Línea de comentario
            if ($char === '-' && substr($sql, $i, 3) === '-- ') {
                $fin = strpos($sql, "\n", $i);
                $fin = $fin === false ? $len : $fin;
                $actual .= substr($sql, $i, $fin - $i);
                $i = $fin;
                continue;
            }

            $dlen = strlen($delimiter);
            if (substr($sql, $i, $dlen) === $delimiter) {
                $sentencias[] = trim($actual);
                $actual = '';
                $i += $dlen;
                continue;
            }

            $actual .= $char;
            $i++;
        }

        if (trim($actual) !== '') {
            $sentencias[] = trim($actual);
        }

        return array_values(array_filter($sentencias));
    }
}