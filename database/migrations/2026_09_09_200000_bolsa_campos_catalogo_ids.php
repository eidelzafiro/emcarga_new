<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte los campos descriptivos de `bolsa` (sexo, color_piel,
 * nivel_educacional, estado_civil, ubicacion_defensa) de TEXTO a ids de
 * `catalogo_items` (tipo tipos_sexo / tipos_color_piel / tipos_nivel_educacion
 * / tipos_estado_civil / tipos_ubicacion_defensa), re-apuntando desde el
 * legacy rh_bolsa por id de bolsa. Idempotente: si ya son ids válidos no toca.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: NULL los valores texto (MariaDB no convierte texto→bigint).
        DB::table('bolsa')->update([
            'sexo' => null, 'color_piel' => null, 'nivel_educacional' => null,
            'estado_civil' => null, 'ubicacion_defensa' => null,
        ]);

        // Paso 2: tipos de columna texto → unsignedBigInteger nullable.
        Schema::table('bolsa', function (Blueprint $table) {
            $table->unsignedBigInteger('sexo')->nullable()->change();
            $table->unsignedBigInteger('color_piel')->nullable()->change();
            $table->unsignedBigInteger('nivel_educacional')->nullable()->change();
            $table->unsignedBigInteger('estado_civil')->nullable()->change();
            $table->unsignedBigInteger('ubicacion_defensa')->nullable()->change();
        });

        $mapa = [
            'sexo' => ['idtiposexo', 'tipos_sexo'],
            'color_piel' => ['idcolorpiel', 'tipos_color_piel'],
            'nivel_educacional' => ['idtiponiveducacion', 'tipos_nivel_educacion'],
            'estado_civil' => ['idtipoestadocivil', 'tipos_estado_civil'],
            'ubicacion_defensa' => ['idtipoubicdefensa', 'tipos_ubicacion_defensa'],
        ];

        // Catálogo: origen_id → id (por tipo).
        $catalogo = [];
        foreach (DB::table('catalogo_items')->whereIn('tipo', array_values(array_column($mapa, 1)))
            ->get(['id', 'tipo', 'origen_id']) as $item) {
            $catalogo[$item->tipo][(int) $item->origen_id] = (int) $item->id;
        }

        // Legacy: idbolsa → id legacy del campo (re-apunta los ids del texto).
        $legacy = [];
        $colsLegacy = array_merge(['idbolsa'], array_column($mapa, 0));
        $legacyRows = DB::connection('legacy')->table('rh_bolsa')->get($colsLegacy);
        foreach ($legacyRows as $row) {
            $legacy[(int) $row->idbolsa] = $row;
        }

        $updates = [];
        foreach (DB::table('bolsa')->get(array_merge(['id'], array_keys($mapa))) as $b) {
            $set = [];
            $leg = $legacy[(int) $b->id] ?? null;

            foreach ($mapa as $campo => [$colLegacy, $tipo]) {
                $idCatalogo = null;
                if ($leg && isset($leg->{$colLegacy}) && (int) $leg->{$colLegacy} > 0) {
                    $idCatalogo = $catalogo[$tipo][(int) $leg->{$colLegacy}] ?? null;
                }
                if ($idCatalogo !== null) {
                    $set[$campo] = $idCatalogo;
                }
            }

            if ($set) {
                $updates[(int) $b->id] = $set;
            }
        }

        foreach ($updates as $id => $set) {
            DB::table('bolsa')->where('id', $id)->update($set);
        }

        // FKs hacia catalogo_items (tolerantes a re-ejecución).
        $fks = [
            ['bolsa', 'sexo'],
            ['bolsa', 'color_piel'],
            ['bolsa', 'nivel_educacional'],
            ['bolsa', 'estado_civil'],
            ['bolsa', 'ubicacion_defensa'],
        ];
        foreach ($fks as [$tabla, $col]) {
            $nombre = "fk_bolsa_{$col}_catalogo";
            $existe = collect(DB::select("SHOW CREATE TABLE {$tabla}"))->first()->{'Create Table'};
            if (! str_contains((string) $existe, "`{$nombre}`")) {
                DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT `{$nombre}` FOREIGN KEY (`{$col}`) REFERENCES `catalogo_items`(`id`) ON DELETE SET NULL");
            }
        }
    }

    public function down(): void
    {
        // Revertir tipos de columna a texto y limpiar FKs.
        foreach (['sexo', 'color_piel', 'nivel_educacional', 'estado_civil', 'ubicacion_defensa'] as $col) {
            $nombre = "fk_bolsa_{$col}_catalogo";
            $existe = collect(DB::select("SHOW CREATE TABLE bolsa"))->first()->{'Create Table'};
            if (str_contains((string) $existe, "`{$nombre}`")) {
                DB::statement("ALTER TABLE bolsa DROP FOREIGN KEY `{$nombre}`");
            }
        }

        Schema::table('bolsa', function (Blueprint $table) {
            $table->string('sexo', 10)->nullable()->change();
            $table->string('color_piel', 50)->nullable()->change();
            $table->string('nivel_educacional', 100)->nullable()->change();
            $table->string('estado_civil', 50)->nullable()->change();
            $table->string('ubicacion_defensa', 200)->nullable()->change();
        });

        // Los valores quedan como ids (no reversibles a texto sin el legacy).
    }
};
