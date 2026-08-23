<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * REVERSO parcial de la fase 3: `tipos_cargas` vuelve a ser tabla propia
 * porque el motor de cotización de aforos (AforoCotizadorService, paridad
 * legacy Aforo.php) tiene semántica de ids legacy incrustada (tc3/tc4,
 * cereales=18, especiales 117/118, distribución 104-108).
 *
 * - Reconstruye la tabla desde su espejo en catalogo_items (id = origen_id).
 * - Devuelve las columnas de negocio (solicitudes, tarifas, tasas, giros,
 *   detalle_prefacturas) al espacio legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tipos_cargas')) {
            Schema::create('tipos_cargas', function ($t) {
                $t->id();
                $t->string('codigo', 191)->nullable();
                $t->string('nombre', 191);
                $t->boolean('activo')->default(true);
            });
        }

        foreach (DB::table('catalogo_items')->where('tipo', 'tipos_cargas')->get() as $item) {
            DB::table('tipos_cargas')->updateOrInsert(
                ['id' => $item->origen_id],
                [
                    'codigo' => $item->codigo,
                    'nombre' => $item->nombre,
                    'activo' => $item->activo,
                ]
            );
        }

        // Columnas de negocio vuelven al espacio legacy (origen_id)
        $pares = [
            ['detalle_prefacturas', 'id_tipo_carga'],
            ['giros', 'id_tipo_carga'],
            ['solicitudes_servicio', 'id_tipo_carga'],
            ['solicitudes_servicio', 'id_tipo_carga2'],
            ['tarifas', 'id_tipo_carga'],
            ['tasas', 'id_tipo_carga'],
        ];

        foreach ($pares as [$tabla, $columna]) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
                continue;
            }

            foreach ($this->fksResiduales($tabla, $columna) as $nombre) {
                try {
                    DB::statement("ALTER TABLE {$tabla} DROP FOREIGN KEY {$nombre}");
                } catch (\Throwable) {
                }
            }

            DB::statement("
                UPDATE {$tabla} t
                JOIN catalogo_items ci ON ci.tipo = 'tipos_cargas' AND ci.id = t.{$columna}
                SET t.{$columna} = ci.origen_id
            ");

            DB::statement("
                ALTER TABLE {$tabla} ADD CONSTRAINT fk_{$tabla}_{$columna}_tiposcargas
                FOREIGN KEY ({$columna}) REFERENCES tipos_cargas(id)
            ");
        }
    }

    private function fksResiduales(string $tabla, string $columna): array
    {
        $nombres = [];
        try {
            $create = DB::selectOne("SHOW CREATE TABLE {$tabla}")->{'Create Table'} ?? '';
            if (preg_match_all(
                '/CONSTRAINT\s+`([^`]+)`\s+FOREIGN\s+KEY\s*\(`([^`]+)`\)\s+REFERENCES\s+`catalogo_items`/i',
                $create, $m, PREG_SET_ORDER
            )) {
                foreach ($m as [$_, $nombre, $col]) {
                    if ($col === $columna) {
                        $nombres[$nombre] = true;
                    }
                }
            }
        } catch (\Throwable) {
        }

        return array_keys($nombres);
    }

    public function down(): void
    {
        //
    }
};
