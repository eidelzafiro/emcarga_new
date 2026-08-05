<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Insert missing marcas referenced by modelos with id_marca=0
        // modelos.id_marca is NOT NULL and has FK to marcas, but 197 rows have id_marca=0
        // Solution: change id_marca to nullable and set those rows to NULL
        Schema::table('modelos', function (Blueprint $table) {
            $table->foreignId('id_marca')->nullable()->change();
        });

        DB::statement('UPDATE modelos SET id_marca = NULL WHERE id_marca = 0');

        // 2. tipos_tractivos: orphan id_pais values (270-277) — set to NULL
        DB::statement('UPDATE tipos_tractivos SET id_pais = NULL WHERE id_pais IN (270,271,272,273,274,276,277)');

        // 3. tipos_tractivos: orphan id_marca=43 — set to NULL
        DB::statement('UPDATE tipos_tractivos SET id_marca = NULL WHERE id_marca = 43');

        // 4. osdes: orphan id_organismo values — set to NULL
        DB::statement('UPDATE osdes SET id_organismo = NULL WHERE id_organismo IN (102,104,174,327)');

        // 5. hotkeys: insert missing acciones_hotkeys records
        // The orphan hotkeys reference action IDs 28, 104 (lost during migration) and
        // 137, 142, 144 (data quality legacy — never existed in legacy acciones)
        $now = now();

        $existingCodigos = DB::table('acciones_hotkeys')->pluck('codigo')->toArray();

        $missingAcciones = [
            ['id' => 28, 'nombre' => 'ACCESOS DIRECTOS'],
            ['id' => 104, 'nombre' => 'HOJAS DE RUTA'],
            ['id' => 137, 'nombre' => 'SIN DEFINIR'],
            ['id' => 142, 'nombre' => 'SIN DEFINIR'],
            ['id' => 144, 'nombre' => 'SIN DEFINIR'],
        ];

        $counter = 1;
        foreach ($missingAcciones as &$accion) {
            $baseCodigo = str_replace(' ', '', $accion['nombre']);
            if ($accion['nombre'] === 'SIN DEFINIR') {
                $baseCodigo = 'SinDefinir'.$counter;
                $counter++;
            }
            while (in_array($baseCodigo, $existingCodigos)) {
                $baseCodigo .= '_'.$accion['id'];
            }
            $accion['codigo'] = $baseCodigo;
            $existingCodigos[] = $baseCodigo;
        }
        unset($accion);

        foreach ($missingAcciones as $accion) {
            DB::table('acciones_hotkeys')
                ->updateOrInsert(
                    ['id' => $accion['id']],
                    [
                        'codigo' => $accion['codigo'],
                        'nombre' => $accion['nombre'],
                        'activo' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
        }

        // 6. Add ETL mapping for rh_cargos → cargos and insert referenced rows
        // tipos_medios_cargo references id_cargo 26,33,36,38,40,42,43
        // We migrate these specific cargos from the legacy database
        $legacy = DB::connection('legacy');
        $referencedIds = [26, 33, 36, 38, 40, 42, 43];

        $legacyCargos = $legacy->table('rh_cargos')
            ->whereIn('idcargos', $referencedIds)
            ->get();

        foreach ($legacyCargos as $cargo) {
            DB::table('cargos')->updateOrInsert(
                ['id' => $cargo->idcargos],
                [
                    'codigo' => $cargo->idcargos,
                    'nombre' => $cargo->nombcargo,
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('modelos', function (Blueprint $table) {
            $table->foreignId('id_marca')->nullable(false)->change();
        });
    }
};
