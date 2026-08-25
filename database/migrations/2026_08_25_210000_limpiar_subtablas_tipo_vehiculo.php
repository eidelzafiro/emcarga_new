<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase B: limpieza de las columnas compartidas en las subtablas de
     * negocio (tipos_tractivos / tipos_arrastres). Esos atributos ahora
     * viven en la tabla unificada tipo_vehiculos (marca, modelo,
     * tipo_equipo, tipo_mantenimiento), poblada en la migración 2026_08_25_200000.
     *
     * Se conservan en las subtablas: su ficha técnica propia (neumáticos,
     * distancias, ejes, medidas...), el año (fabricacion) y, en
     * tipos_tractivos, id_tipo_combustible.
     */
    public function up(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            $table->dropForeign('fk_tipos_tractivos_id_marca_catalogo');
            $table->dropForeign('fk_tipos_tractivos_id_modelo_catalogo');
            $table->dropForeign('tipos_tractivos_id_tipo_equipo_foreign');
            $table->dropForeign('tipos_tractivos_id_tipo_mantenimiento_foreign');
            $table->dropColumn(['id_marca', 'id_modelo', 'id_tipo_equipo', 'id_tipo_mantenimiento']);
        });

        Schema::table('tipos_arrastres', function (Blueprint $table) {
            $table->dropForeign('fk_tipos_arrastres_id_marca_catalogo');
            $table->dropForeign('fk_tipos_arrastres_id_modelo_catalogo');
            $table->dropForeign('tipos_arrastres_id_tipo_equipo_foreign');
            $table->dropForeign('tipos_arrastres_id_tipo_mantenimiento_foreign');
            $table->dropColumn(['id_marca', 'id_modelo', 'id_tipo_equipo', 'id_tipo_mantenimiento']);
        });
    }

    public function down(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_marca')->nullable();
            $table->unsignedBigInteger('id_modelo')->nullable();
            $table->unsignedBigInteger('id_tipo_equipo')->nullable();
            $table->unsignedBigInteger('id_tipo_mantenimiento')->nullable();

            $table->foreign('id_marca')->references('id')->on('catalogo_items');
            $table->foreign('id_modelo')->references('id')->on('catalogo_items');
            $table->foreign('id_tipo_equipo')->references('id')->on('tipos_equipos')->onDelete('set null');
            $table->foreign('id_tipo_mantenimiento')->references('id')->on('tipos_mantenimiento')->onDelete('set null');
        });

        Schema::table('tipos_arrastres', function (Blueprint $table) {
            $table->unsignedBigInteger('id_marca')->nullable();
            $table->unsignedBigInteger('id_modelo')->nullable();
            $table->unsignedBigInteger('id_tipo_equipo')->nullable();
            $table->unsignedBigInteger('id_tipo_mantenimiento')->nullable();

            $table->foreign('id_marca')->references('id')->on('catalogo_items');
            $table->foreign('id_modelo')->references('id')->on('catalogo_items');
            $table->foreign('id_tipo_equipo')->references('id')->on('tipos_equipos')->onDelete('set null');
            $table->foreign('id_tipo_mantenimiento')->references('id')->on('tipos_mantenimiento')->onDelete('set null');
        });
    }
};
