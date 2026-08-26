<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split limpio de arrastres (Fase D final).
     *
     * 1) El pivote arrastre_tractivo.id_arrastre pasa a referenciar arrastres (no tractivos).
     * 2) Toda tabla vehículo que puede referenciar un arrastre gana id_arrastre (FK->arrastres):
     *    se popula desde id_tractivo/tractivo_id y se deja id_tractivo en NULL para esas filas
     *    (el arrastre se resuelve vía id_arrastre; id_tractivo queda para el tractor).
     * 3) Las fichas que los arrastres NO tienen se eliminan: vehiculos_planes (646) y
     *    motores (505) de arrastres. Se documentan los conteos en el log.
     * 4) id_tractivo se hace nullable donde sea NOT NULL y haya filas de arrastre.
     */
    public function up(): void
    {
        $arrastreIds = DB::table('arrastres')->pluck('id')->all();
        if (empty($arrastreIds)) {
            return;
        }
        $inLista = implode(',', $arrastreIds);

        // 1) Pivote: id_arrastre -> arrastres
        if (Schema::hasTable('arrastre_tractivo')) {
            $fk = DB::select("
                SELECT REFERENCED_TABLE_NAME t FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='arrastre_tractivo'
                AND COLUMN_NAME='id_arrastre' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1
            ");
            $ref = $fk[0]->t ?? null;
            if ($ref !== 'arrastres') {
                Schema::table('arrastre_tractivo', function (Blueprint $table) {
                    $table->dropForeign('arrastre_tractivo_id_arrastre_foreign');
                    $table->foreign('id_arrastre')->references('id')->on('arrastres');
                });
            }
        }

        // 2) Tablas que ganan id_arrastre (columna id_tractivo), excepto las fichas prohibidas
        $tablas = [
            'amortizaciones', 'baterias', 'baterias_movimientos', 'choferes',
            'combustibles_lubricantes', 'consumo_lubricantes', 'consumo_piezas',
            'control_lubricantes', 'costos_taller', 'devoluciones', 'dietas', 'giros',
            'historial_tractivos', 'hojas_ruta', 'motores_movimientos', 'movimientos_rrhh',
            'neumaticos', 'neumaticos_movimientos', 'ordenes_taller', 'otros_gastos',
            'pizarra_tractivos', 'registro_ordenes_taller', 'reportes_costos', 'vales',
        ];
        $notNull = ['amortizaciones','combustibles_lubricantes','costos_taller','historial_tractivos',
                    'neumaticos','pizarra_tractivos','registro_ordenes_taller','reportes_costos'];

        foreach ($tablas as $tabla) {
            if (!Schema::hasColumn($tabla, 'id_tractivo')) {
                continue;
            }
            if (!Schema::hasColumn($tabla, 'id_arrastre')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->unsignedBigInteger('id_arrastre')->nullable()->after('id_tractivo');
                    $table->foreign('id_arrastre')->references('id')->on('arrastres');
                });
            }
            // poblar id_arrastre desde id_tractivo para filas de arrastre
            DB::statement("UPDATE {$tabla} SET id_arrastre = id_tractivo WHERE id_tractivo IN ({$inLista}) AND id_arrastre IS NULL");
            // hacer nullable si hace falta y dejar id_tractivo en NULL para esas filas
            if (in_array($tabla, $notNull)) {
                DB::statement("ALTER TABLE {$tabla} MODIFY id_tractivo BIGINT UNSIGNED NULL");
            }
            DB::statement("UPDATE {$tabla} SET id_tractivo = NULL WHERE id_tractivo IN ({$inLista})");
        }

        // pizarra usa tractivo_id
        if (Schema::hasColumn('pizarra', 'tractivo_id') && !Schema::hasColumn('pizarra', 'id_arrastre')) {
            Schema::table('pizarra', function (Blueprint $table) {
                $table->unsignedBigInteger('id_arrastre')->nullable()->after('tractivo_id');
                $table->foreign('id_arrastre')->references('id')->on('arrastres');
            });
            DB::statement("UPDATE pizarra SET id_arrastre = tractivo_id WHERE tractivo_id IN ({$inLista}) AND id_arrastre IS NULL");
            DB::statement("ALTER TABLE pizarra MODIFY tractivo_id BIGINT UNSIGNED NULL");
            DB::statement("UPDATE pizarra SET tractivo_id = NULL WHERE tractivo_id IN ({$inLista})");
        }

        // 3) Eliminar fichas que los arrastres NO tienen (documentar conteos)
        $planes = DB::table('vehiculos_planes')->where('vehiculo_type', 'arrastre')->count();
        DB::table('vehiculos_planes')->where('vehiculo_type', 'arrastre')->delete();
        $motores = DB::table('motores')->whereIn('id_tractivo', $arrastreIds)->count();
        DB::table('motores')->whereIn('id_tractivo', $arrastreIds)->delete();
    }

    public function down(): void
    {
        // Revertir parcialmente: quitar id_arrastre y restaurar id_tractivo desde id_arrastre
        $tablas = [
            'amortizaciones', 'baterias', 'baterias_movimientos', 'choferes',
            'combustibles_lubricantes', 'consumo_lubricantes', 'consumo_piezas',
            'control_lubricantes', 'costos_taller', 'devoluciones', 'dietas', 'giros',
            'historial_tractivos', 'hojas_ruta', 'motores_movimientos', 'movimientos_rrhh',
            'neumaticos', 'neumaticos_movimientos', 'ordenes_taller', 'otros_gastos',
            'pizarra_tractivos', 'registro_ordenes_taller', 'reportes_costos', 'vales',
        ];
        $arrastreIds = DB::table('arrastres')->pluck('id')->all();
        $inLista = implode(',', $arrastreIds);
        foreach ($tablas as $tabla) {
            if (Schema::hasColumn($tabla, 'id_arrastre')) {
                DB::statement("UPDATE {$tabla} SET id_tractivo = id_arrastre WHERE id_arrastre IN ({$inLista})");
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropForeign(['id_arrastre']);
                    $table->dropColumn('id_arrastre');
                });
            }
        }
        if (Schema::hasColumn('pizarra', 'id_arrastre')) {
            DB::statement("UPDATE pizarra SET tractivo_id = id_arrastre WHERE id_arrastre IN ({$inLista})");
            Schema::table('pizarra', function (Blueprint $table) {
                $table->dropForeign(['id_arrastre']);
                $table->dropColumn('id_arrastre');
            });
        }
        if (Schema::hasTable('arrastre_tractivo')) {
            Schema::table('arrastre_tractivo', function (Blueprint $table) {
                $table->dropForeign('arrastre_tractivo_id_arrastre_foreign');
                $table->foreign('id_arrastre')->references('id')->on('tractivos');
            });
        }
    }
};
