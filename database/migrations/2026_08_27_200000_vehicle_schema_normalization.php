<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalización de esquema de vehículos (2026-08-27).
 *
 * - tractivos: se elimina id_tipo_equipo (se deriva vía id_tipo_vehiculo),
 *   descripcion, cta_combustible, capacidad_m3, numero_chasis, vin y estado
 *   (varchar). Se añaden id_marca/id_modelo (FK catalogo_items).
 * - arrastres: se elimina estado (varchar); se añaden id_tipo_estado,
 *   id_marca, id_modelo.
 * - tipos_tractivos: se añaden id_lubricante_hidraulico y cap_hidraulico
 *   (movidos desde tractivos, pues son specs del tipo).
 * - vehiculos_documentacion: se añaden nro_chasis y vin (polimórfico).
 * - neumaticos/baterias: se migra marca/modelo de texto libre a FK
 *   catalogo_items y se eliminan las columnas de texto.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- 1. LEER DATOS ANTES DE BORRAR ----
        $tractivos = DB::table('tractivos')
            ->select('id', 'numero_chasis', 'vin', 'id_lubricante_hidraulico', 'cap_hidraulico', 'id_tipo_vehiculo')
            ->get();

        $tvMap = DB::table('tipo_vehiculos')
            ->select('id', 'id_marca', 'id_modelo', 'id_tipo_tractivo')
            ->get()
            ->keyBy('id');

        $docTractivo = [];
        $ttLub = [];
        $trMarca = [];
        foreach ($tractivos as $t) {
            if ($t->numero_chasis || $t->vin) {
                $docTractivo[$t->id] = [$t->numero_chasis, $t->vin];
            }
            $tv = $tvMap->get($t->id_tipo_vehiculo);
            if ($tv && $tv->id_tipo_tractivo && ! isset($ttLub[$tv->id_tipo_tractivo])) {
                $ttLub[$tv->id_tipo_tractivo] = [
                    'id_lubricante_hidraulico' => $t->id_lubricante_hidraulico,
                    'cap_hidraulico' => $t->cap_hidraulico,
                ];
            }
            $trMarca[$t->id] = $tv ? [$tv->id_marca, $tv->id_modelo] : [null, null];
        }

        // arrastres: leer de legacy tec_tractivos (idgrupo=8)
        $legArr = DB::connection('legacy')->table('tec_tractivos')
            ->where('idgrupo', 8)
            ->select('idtractivos', 'idtipoestados', 'chassis', 'vin', 'idtipotractivos')
            ->get();

        $arrEstado = [];
        $arrDoc = [];
        $arrMarca = [];
        foreach ($legArr as $a) {
            $arrEstado[$a->idtractivos] = $a->idtipoestados;
            if ($a->chassis || $a->vin) {
                $arrDoc[$a->idtractivos] = [$a->chassis, $a->vin];
            }
            $tv = DB::table('tipo_vehiculos')
                ->where('id', $a->idtipotractivos)
                ->where('clase', 'arrastre')
                ->select('id_marca', 'id_modelo')
                ->first();
            $arrMarca[$a->idtractivos] = $tv ? [$tv->id_marca, $tv->id_modelo] : [null, null];
        }

        $estadosExistentes = DB::table('estados_componentes')->pluck('id')->flip();

        // ---- 2. ESQUEMA ----
        Schema::table('tractivos', function (Blueprint $t) {
            $t->dropForeign(['id_tipo_equipo']);
            $t->dropIndex('tractivos_estado_index');
            $t->dropColumn(['id_tipo_equipo', 'descripcion', 'cta_combustible', 'capacidad_m3', 'numero_chasis', 'vin', 'estado']);
            $t->foreignId('id_marca')->nullable()->constrained('catalogo_items')->nullOnDelete();
            $t->foreignId('id_modelo')->nullable()->constrained('catalogo_items')->nullOnDelete();
        });

        Schema::table('arrastres', function (Blueprint $t) {
            $t->dropIndex('tractivos_estado_index');
            $t->dropColumn('estado');
            $t->foreignId('id_tipo_estado')->nullable()->constrained('estados_componentes')->nullOnDelete();
            $t->foreignId('id_marca')->nullable()->constrained('catalogo_items')->nullOnDelete();
            $t->foreignId('id_modelo')->nullable()->constrained('catalogo_items')->nullOnDelete();
        });

        Schema::table('tipos_tractivos', function (Blueprint $t) {
            $t->foreignId('id_lubricante_hidraulico')->nullable()->constrained('lubricantes')->nullOnDelete();
            $t->decimal('cap_hidraulico', 8, 2)->nullable();
        });

        Schema::table('vehiculos_documentacion', function (Blueprint $t) {
            $t->string('nro_chasis', 100)->nullable();
            $t->string('vin', 100)->nullable();
        });

        Schema::table('neumaticos', function (Blueprint $t) {
            $t->foreignId('id_marca')->nullable()->constrained('catalogo_items')->nullOnDelete();
            $t->foreignId('id_modelo')->nullable()->constrained('catalogo_items')->nullOnDelete();
        });

        Schema::table('baterias', function (Blueprint $t) {
            $t->foreignId('id_marca')->nullable()->constrained('catalogo_items')->nullOnDelete();
            $t->foreignId('id_modelo')->nullable()->constrained('catalogo_items')->nullOnDelete();
        });

        // ---- 3. ESCRIBIR DATOS ----
        foreach ($docTractivo as $vid => [$ch, $vn]) {
            DB::table('vehiculos_documentacion')->updateOrInsert(
                ['vehiculo_type' => 'tractivo', 'vehiculo_id' => $vid],
                ['nro_chasis' => $ch ?: null, 'vin' => $vn ?: null, 'updated_at' => now()]
            );
        }
        foreach ($arrDoc as $vid => [$ch, $vn]) {
            DB::table('vehiculos_documentacion')->updateOrInsert(
                ['vehiculo_type' => 'arrastre', 'vehiculo_id' => $vid],
                ['nro_chasis' => $ch ?: null, 'vin' => $vn ?: null, 'updated_at' => now()]
            );
        }
        foreach ($ttLub as $idtt => $v) {
            DB::table('tipos_tractivos')->where('id', $idtt)->update(array_merge($v, ['updated_at' => now()]));
        }
        foreach ($trMarca as $vid => [$m, $mo]) {
            DB::table('tractivos')->where('id', $vid)->update(['id_marca' => $m, 'id_modelo' => $mo, 'updated_at' => now()]);
        }
        foreach ($arrMarca as $vid => [$m, $mo]) {
            DB::table('arrastres')->where('id', $vid)->update(['id_marca' => $m, 'id_modelo' => $mo, 'updated_at' => now()]);
        }
        foreach ($arrEstado as $vid => $legId) {
            $id = isset($estadosExistentes[$legId]) ? $legId : null;
            DB::table('arrastres')->where('id', $vid)->update(['id_tipo_estado' => $id, 'updated_at' => now()]);
        }

        // neumaticos/baterias: texto -> FK catalogo_items
        $marcasCat = DB::table('catalogo_items')->where('tipo', 'marcas')
            ->get()->mapWithKeys(fn ($r) => [mb_strtoupper(trim($r->nombre)) => $r->id]);
        $modelosCat = DB::table('catalogo_items')->where('tipo', 'modelos')
            ->get()->mapWithKeys(fn ($r) => [mb_strtoupper(trim($r->nombre)) => $r->id]);

        foreach (DB::table('neumaticos')->select('id', 'marca', 'modelo')->get() as $n) {
            $mid = $n->marca ? ($marcasCat[mb_strtoupper(trim($n->marca))] ?? null) : null;
            $mod = $n->modelo ? ($modelosCat[mb_strtoupper(trim($n->modelo))] ?? null) : null;
            DB::table('neumaticos')->where('id', $n->id)->update(['id_marca' => $mid, 'id_modelo' => $mod, 'updated_at' => now()]);
        }
        foreach (DB::table('baterias')->select('id', 'marca', 'modelo')->get() as $b) {
            $mid = $b->marca ? ($marcasCat[mb_strtoupper(trim($b->marca))] ?? null) : null;
            $mod = $b->modelo ? ($modelosCat[mb_strtoupper(trim($b->modelo))] ?? null) : null;
            DB::table('baterias')->where('id', $b->id)->update(['id_marca' => $mid, 'id_modelo' => $mod, 'updated_at' => now()]);
        }

        Schema::table('neumaticos', function (Blueprint $t) {
            $t->dropColumn(['marca', 'modelo']);
        });
        Schema::table('baterias', function (Blueprint $t) {
            $t->dropColumn(['marca', 'modelo']);
        });
    }

    public function down(): void
    {
        Schema::table('tractivos', function (Blueprint $t) {
            $t->dropForeign(['id_marca']);
            $t->dropForeign(['id_modelo']);
            $t->dropColumn(['id_marca', 'id_modelo']);
            $t->foreignId('id_tipo_equipo')->nullable()->constrained('tipos_equipos')->nullOnDelete();
            $t->string('descripcion')->nullable();
            $t->string('cta_combustible', 100)->nullable();
            $t->decimal('capacidad_m3', 8, 2)->nullable();
            $t->string('numero_chasis', 100)->nullable();
            $t->string('vin', 100)->nullable();
            $t->string('estado', 50)->default('activo');
            $t->index('estado', 'tractivos_estado_index');
        });

        Schema::table('arrastres', function (Blueprint $t) {
            $t->dropForeign(['id_tipo_estado']);
            $t->dropForeign(['id_marca']);
            $t->dropForeign(['id_modelo']);
            $t->dropColumn(['id_tipo_estado', 'id_marca', 'id_modelo']);
            $t->string('estado', 50)->default('activo');
            $t->index('estado', 'tractivos_estado_index');
        });

        Schema::table('tipos_tractivos', function (Blueprint $t) {
            $t->dropForeign(['id_lubricante_hidraulico']);
            $t->dropColumn(['id_lubricante_hidraulico', 'cap_hidraulico']);
        });

        Schema::table('vehiculos_documentacion', function (Blueprint $t) {
            $t->dropColumn(['nro_chasis', 'vin']);
        });

        Schema::table('neumaticos', function (Blueprint $t) {
            $t->dropForeign(['id_marca']);
            $t->dropForeign(['id_modelo']);
            $t->dropColumn(['id_marca', 'id_modelo']);
            $t->string('marca', 100)->nullable();
            $t->string('modelo', 100)->nullable();
        });

        Schema::table('baterias', function (Blueprint $t) {
            $t->dropForeign(['id_marca']);
            $t->dropForeign(['id_modelo']);
            $t->dropColumn(['id_marca', 'id_modelo']);
            $t->string('marca', 100)->nullable();
            $t->string('modelo', 100)->nullable();
        });
    }
};
