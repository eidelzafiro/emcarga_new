<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina tablas sin uso confirmadas por la auditoría DBA (2026-09-13) y
 * autorizadas por EIDEL.
 *
 * NO se elimina `reportes_legacy`: está en uso por ReporteCatalogoService,
 * ReportesDispatcher y ReportesController.
 *
 * Antes de dropear `perfiles_rh` se retira la FK/columna `alertas.id_perfil`.
 * El rollback completo se hace desde la salva previa
 * (database/backups/emcarga_new_2026-09-13_15-36-29.sql).
 */
return new class extends Migration
{
    /** Tablas autorizadas (vacías o sin uso, sin dependencias vivas). */
    private const TABLAS = [
        'amortizaciones', 'costos_taller', 'competencias_cargo', 'consumo_piezas',
        'descuentos_empleados', 'detalle_movimientos_inventario', 'detalle_prefacturas',
        'detalle_vales_inventario', 'equipos_electricos', 'equipos_garaje',
        'funciones_cargo', 'gastos_taller', 'giros', 'importes_gps', 'importes_multas',
        'lineas_bateria', 'lineas_diferencial', 'lineas_lubricante', 'lineas_neumatico',
        'lineas_otro_agregado', 'locales_electricos', 'mantenimiento_ciclos',
        'motivos_espera', 'movimientos_tarjetas', 'pagos_adicionales_cargo', 'piezas',
        'planes', 'planes_mantenimiento', 'vacaciones', 'clientes_seleccion',
        'conceptos_costos', 'movimientos_inventario', 'perfiles_rh', 'tarjetero',
    ];

    public function up(): void
    {
        // `perfiles_rh` es referenciada por alertas.id_perfil: se retira primero.
        if (Schema::hasTable('alertas') && Schema::hasColumn('alertas', 'id_perfil')) {
            Schema::table('alertas', function (Blueprint $table) {
                $table->dropForeign(['id_perfil']);
                $table->dropColumn('id_perfil');
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (self::TABLAS as $tabla) {
            Schema::dropIfExists($tabla);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Solo se restaura la columna de alertas; las tablas se recuperan de la salva.
        if (Schema::hasTable('alertas') && ! Schema::hasColumn('alertas', 'id_perfil')) {
            Schema::table('alertas', function (Blueprint $table) {
                $table->unsignedBigInteger('id_perfil')->nullable()->after('id_user');
            });
        }
    }
};
