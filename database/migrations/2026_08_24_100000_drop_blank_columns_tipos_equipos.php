<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Decisión (2026-08-24) — limpieza de `tipos_equipos`.
     *
     * La tabla migrada (emcarga_new) tiene 14 filas (los 14 tipos de equipo
     * normalizados) y 8 columnas. Dos de ellas están 100% en blanco:
     *
     *   - `codigo`       : NULL en las 14 filas. No es FK (id_tipo_equipo
     *                      referencia tipos_equipos.id) ni se usa en ninguna
     *                      consulta por columna; el comando zafiro:migrar-catalogos
     *                      solo lo lee como propiedad y lo trata como null.
     *   - `imagen_fuente`: NULL en las 14 filas. El catálogo unificado la espeja
     *                      a catalogo_items.extra.imagen_fuente (también null) y
     *                      ni zafiro:migrar-catalogos ni TiposEquiposNormalizer
     *                      referencian esta columna de tipos_equipos.
     *
     * Se conservan `imagen` (ruta relativa, 14 valores), `nombre`, `activo` y
     * los timestamps. Eliminar estas dos columnas muertas simplifica el modelo y
     * evita confusión en futuros re-syncs del catálogo.
     *
     * Idempotente: sólo dropea si la columna existe (seguro de re-ejecutar).
     */
    public function up(): void
    {
        if (Schema::hasColumn('tipos_equipos', 'codigo')) {
            Schema::table('tipos_equipos', function (Blueprint $table) {
                $table->dropUnique('tipos_equipos_codigo_unique');
                $table->dropColumn('codigo');
            });
        }

        if (Schema::hasColumn('tipos_equipos', 'imagen_fuente')) {
            Schema::table('tipos_equipos', function (Blueprint $table) {
                $table->dropColumn('imagen_fuente');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tipos_equipos', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable()->unique()->after('id');
            $table->string('imagen_fuente', 500)->nullable()->after('imagen');
        });
    }
};
