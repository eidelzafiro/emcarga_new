<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina las tablas catálogo legacy que no se usan en la nueva versión.
 * Se conserva referencia en docs/tablas-eliminadas.md para revisiones futuras.
 */
return new class extends Migration
{
    private array $tablas = [
        'plantilla',
        'tipos_plantillas',
        'tipos_calificadores',
        'tipos_articulos_bolsa',
        'tipos_entidad',
        'tipos_especialidad',
        'tipos_tallas',
        'tipos_causas_baja',
        'tipos_causas_laborales',
        'tipos_causas_movimiento',
        'tipos_jefe_grupo',
    ];

    public function up(): void
    {
        if (Schema::hasColumn('historial_movimientos', 'id_plantilla')) {
            Schema::table('historial_movimientos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('id_plantilla');
            });
        }

        $this->dropDependencias();

        foreach ($this->tablas as $tabla) {
            Schema::dropIfExists($tabla);
        }

        DB::table('catalogo_items')->whereIn('tipo', $this->tablas)->delete();
        DB::table('catalogo_tipos')->whereIn('tipo', $this->tablas)->delete();
    }

    private function dropDependencias(): void
    {
        // Las tablas tipos_causas_baja y tipos_causas_movimiento referencian
        // a tipos_causas_laborales; hay que soltar esas FKs antes de dropear.
        foreach (['tipos_causas_baja', 'tipos_causas_movimiento'] as $tabla) {
            if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'id_tipo_causa_laboral')) {
                continue;
            }

            try {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropForeign(['id_tipo_causa_laboral']);
                });
            } catch (\Throwable) {
                // La FK pudo no existir; se ignora.
            }
        }
    }

    public function down(): void
    {
        //
    }
};
