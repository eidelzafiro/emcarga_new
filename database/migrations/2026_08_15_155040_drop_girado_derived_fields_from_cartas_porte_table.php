<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 4d — Elimina las columnas derivadas de `cartas_porte`.
 *
 * El equipo/choferes se derivan ahora de `hojas_ruta` (id_hoja_ruta) y el
 * cliente/tipos/productos de `solicitudes_servicio` (id_solicitud). Estas 9
 * columnas dejan de persistirse en la carta.
 */
return new class extends Migration
{
    public function up(): void
    {
        // La FK de id_cliente impide dropear la columna; se elimina primero.
        if (Schema::hasColumn('cartas_porte', 'id_cliente')) {
            Schema::table('cartas_porte', function (Blueprint $table) {
                $table->dropForeign(['id_cliente']);
            });
        }

        Schema::table('cartas_porte', function (Blueprint $table) {
            $columnas = [
                'id_tractivo',
                'id_arrastre',
                'id_chofer',
                'id_chofer2',
                'id_cliente',
                'id_tipo_carga',
                'id_tipo_carga2',
                'id_producto',
                'id_producto2',
            ];

            foreach ($columnas as $columna) {
                if (Schema::hasColumn('cartas_porte', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tractivo')->nullable()->after('id_solicitud');
            $table->unsignedBigInteger('id_arrastre')->nullable()->after('id_tractivo');
            $table->unsignedBigInteger('id_cliente')->nullable()->after('id_arrastre');
            $table->unsignedBigInteger('id_producto')->nullable()->after('id_cliente');
            $table->unsignedBigInteger('id_producto2')->nullable()->after('id_producto');
            $table->unsignedBigInteger('id_tipo_carga')->nullable()->after('id_producto2');
            $table->unsignedBigInteger('id_tipo_carga2')->nullable()->after('id_tipo_carga');
            $table->unsignedBigInteger('id_chofer')->nullable()->after('id_tipo_carga2');
            $table->unsignedBigInteger('id_chofer2')->nullable()->after('id_chofer');
        });
    }
};
