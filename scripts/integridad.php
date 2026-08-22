<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$db = config('database.connections.mysql.database');
echo "BD: $db\n";
$fks = DB::select("
  SELECT kcu.TABLE_NAME AS tabla, kcu.COLUMN_NAME AS col,
         kcu.REFERENCED_TABLE_NAME AS ref_tabla, kcu.REFERENCED_COLUMN_NAME AS ref_col
  FROM information_schema.KEY_COLUMN_USAGE kcu
  JOIN information_schema.TABLE_CONSTRAINTS tc
    ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME AND tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
  WHERE kcu.TABLE_SCHEMA = ? AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
    AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
  ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME
", [$db]);

echo "FKs encontradas: ".count($fks)."\n";
$problemas = 0; $revisadas = 0;
foreach ($fks as $fk) {
    $tabla = $fk->tabla; $col = $fk->col; $rt = $fk->ref_tabla; $rc = $fk->ref_col;
    // Tabla referenciada podría no existir (raro); skip si no.
    try {
        $n = DB::table("$tabla as c")
            ->leftJoin("$rt as p", "c.$col", '=', "p.$rc")
            ->whereNotNull("c.$col")
            ->whereNull("p.$rc")
            ->count();
    } catch (\Throwable $e) { continue; }
    $revisadas++;
    if ($n > 0) {
        $problemas++;
        echo "  ORFANOS: $tabla.$col -> $rt.$rc = $n filas\n";
    }
}
echo "\nFKs revisadas: $revisadas | con hijos huérfanos: $problemas\n";
