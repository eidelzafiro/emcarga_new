<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split físico de arrastres (Fase D).
     *
     * - hojas_ruta ya tiene id_arrastre (apuntaba a tractivos): se repunta la FK a arrastres.
     * - neumaticos y ordenes_taller: se añade id_arrastre (FK -> arrastres) y se popula
     *   para las filas que hoy referencian un arrastre vía id_tractivo.
     * - Las tablas que solo tienen id_tractivo/tractivo_id se repuntan al TRACTOR
     *   asociado mediante el pivote arrastre_tractivo (id_arrastres -> id_tractivo),
     *   dejando NULL si no hay asociación. Así resuelven el arrastre por la relación.
     * Se conserva el espejo en tractivos (id_grupo=8) para no romper el código legacy.
     */
    public function up(): void
    {
        $arrastreIds = DB::table('arrastres')->pluck('id')->all();
        if (empty($arrastreIds)) {
            return;
        }
        $inLista = implode(',', $arrastreIds);
        $pivote = 'arrastre_tractivo';

        // 1) Schema idempotente: hojas_ruta repunta FK id_arrastre tractivos -> arrastres
        if (Schema::hasColumn('hojas_ruta', 'id_arrastre')) {
            $fks = DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hojas_ruta'
                AND COLUMN_NAME = 'id_arrastre' AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $refTabla = !empty($fks) ? $fks[0]->REFERENCED_TABLE_NAME ?? null : null;
            $refTabla = $refTabla ?? (DB::select("
                SELECT REFERENCED_TABLE_NAME AS t FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hojas_ruta'
                AND COLUMN_NAME = 'id_arrastre' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1
            ")[0]->t ?? null);
            if ($refTabla !== 'arrastres') {
                Schema::table('hojas_ruta', function (Blueprint $table) {
                    $table->dropForeign('hojas_ruta_id_arrastre_foreign');
                    $table->foreign('id_arrastre')->references('id')->on('arrastres');
                });
            }
        }

        // 2) Schema idempotente: neumaticos y ordenes_taller ganan id_arrastre -> arrastres
        if (!Schema::hasColumn('neumaticos', 'id_arrastre')) {
            Schema::table('neumaticos', function (Blueprint $table) {
                $table->unsignedBigInteger('id_arrastre')->nullable()->after('id_tractivo');
                $table->foreign('id_arrastre')->references('id')->on('arrastres');
            });
        }
        if (!Schema::hasColumn('ordenes_taller', 'id_arrastre')) {
            Schema::table('ordenes_taller', function (Blueprint $table) {
                $table->unsignedBigInteger('id_arrastre')->nullable()->after('id_tractivo');
                $table->foreign('id_arrastre')->references('id')->on('arrastres');
            });
        }

        // 3) Datos: poblar id_arrastre en neumaticos/ordenes_taller antes de repuntar id_tractivo
        DB::statement("UPDATE neumaticos SET id_arrastre = id_tractivo WHERE id_tractivo IN ({$inLista}) AND id_arrastre IS NULL");
        DB::statement("UPDATE ordenes_taller SET id_arrastre = id_tractivo WHERE id_tractivo IN ({$inLista}) AND id_arrastre IS NULL");

        // 4) Datos: repuntar id_tractivo/tractivo_id de todas las tablas al tractor vía pivote
        $tablas = [
            'amortizaciones', 'baterias', 'baterias_movimientos', 'cajas', 'choferes',
            'combustibles_lubricantes', 'consumo_lubricantes', 'consumo_piezas',
            'control_lubricantes', 'costos_taller', 'devoluciones', 'dietas',
            'diferenciales', 'giros', 'historial_tractivos', 'motores',
            'motores_movimientos', 'movimientos_rrhh', 'neumaticos',
            'neumaticos_movimientos', 'ordenes_taller', 'otros_gastos',
            'pizarra_tractivos', 'registro_ordenes_taller', 'reportes_costos', 'vales',
        ];
        foreach ($tablas as $tabla) {
            // hojas_ruta ya tiene id_tractivo = tractor; saltar su repunto de datos
            if ($tabla === 'hojas_ruta') {
                continue;
            }
            if (!Schema::hasColumn($tabla, 'id_tractivo')) {
                continue;
            }
            DB::statement("
                UPDATE {$tabla} t
                JOIN {$pivote} a ON a.id_arrastre = t.id_tractivo
                SET t.id_tractivo = a.id_tractivo
                WHERE t.id_tractivo IN ({$inLista})
            ");
        }

        // pizarra usa tractivo_id
        if (Schema::hasColumn('pizarra', 'tractivo_id')) {
            DB::statement("
                UPDATE pizarra t
                JOIN {$pivote} a ON a.id_arrastre = t.tractivo_id
                SET t.tractivo_id = a.id_tractivo
                WHERE t.tractivo_id IN ({$inLista})
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hojas_ruta', 'id_arrastre')) {
            $fks = DB::select("
                SELECT REFERENCED_TABLE_NAME AS t FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hojas_ruta'
                AND COLUMN_NAME = 'id_arrastre' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1
            ");
            $refTabla = $fks[0]->t ?? null;
            if ($refTabla === 'arrastres') {
                Schema::table('hojas_ruta', function (Blueprint $table) {
                    $table->dropForeign(['id_arrastre']);
                    $table->foreign('id_arrastre')->references('id')->on('tractivos');
                });
            }
        }
        if (Schema::hasColumn('neumaticos', 'id_arrastre')) {
            Schema::table('neumaticos', function (Blueprint $table) {
                $table->dropForeign(['id_arrastre']);
                $table->dropColumn('id_arrastre');
            });
        }
        if (Schema::hasColumn('ordenes_taller', 'id_arrastre')) {
            Schema::table('ordenes_taller', function (Blueprint $table) {
                $table->dropForeign(['id_arrastre']);
                $table->dropColumn('id_arrastre');
            });
        }
    }
};
