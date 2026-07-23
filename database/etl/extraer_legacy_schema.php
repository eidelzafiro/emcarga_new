<?php

/*
 * Extrae el esquema del dump legacy (mysqldump) a JSON.
 *
 * Uso: php database/etl/extraer_legacy_schema.php <ruta_dump> [salida_json]
 *
 * Genera database/etl/legacy_schema.json con, por tabla:
 *   - columnas: nombre => {tipo, anulable, default, extra}
 *   - pk: columnas de la PRIMARY KEY (vacío si no tiene)
 *
 * El JSON sirve como documentación del origen y como insumo del test
 * EtlMapeoTest, que valida config/etl.php sin necesitar MySQL.
 */

$dump = $argv[1] ?? null;
$salida = $argv[2] ?? __DIR__.'/legacy_schema.json';

if (! $dump || ! is_file($dump)) {
    fwrite(STDERR, "Uso: php extraer_legacy_schema.php <ruta_dump> [salida_json]\n");
    exit(1);
}

$sql = file_get_contents($dump);

preg_match_all('/CREATE TABLE `(\w+)` \(\n(.*?)\n\) ENGINE/s', $sql, $matches, PREG_SET_ORDER);

$esquema = [];

foreach ($matches as $m) {
    [$todo, $tabla, $cuerpo] = $m;
    $columnas = [];
    $pk = [];

    foreach (explode("\n", $cuerpo) as $linea) {
        $linea = trim($linea);

        if (preg_match('/^`(\w+)` (\w+)(?:\(([^)]*)\))?(?: unsigned)?(?: NOT NULL)?( NULL)?(?: DEFAULT (NULL|\'[^\']*\'|[\w.]+|\(\w+\)))?(?: (AUTO_INCREMENT))?/i', $linea, $cm)) {
            $columnas[$cm[1]] = [
                'tipo' => strtolower($cm[2]).(isset($cm[3]) && $cm[3] !== '' ? "({$cm[3]})" : ''),
                'anulable' => ! str_contains($linea, 'NOT NULL'),
                'extra' => $cm[6] ?? '',
            ];
        } elseif (preg_match('/^PRIMARY KEY \(([^)]+)\)/i', $linea, $pm)) {
            preg_match_all('/`(\w+)`/', $pm[1], $pcols);
            $pk = $pcols[1];
        }
    }

    $esquema[$tabla] = ['columnas' => $columnas, 'pk' => $pk];
}

ksort($esquema);

file_put_contents($salida, json_encode($esquema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");

printf("Extraídas %d tablas → %s\n", count($esquema), $salida);
