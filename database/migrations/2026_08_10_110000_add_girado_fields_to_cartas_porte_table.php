<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->unsignedBigInteger('id_hoja_ruta')->nullable()->change();

            $table->unsignedBigInteger('id_solicitud')->nullable()->after('id_hoja_ruta');
            $table->unsignedBigInteger('id_tractivo')->nullable()->after('id_solicitud');
            $table->unsignedBigInteger('id_arrastre')->nullable()->after('id_tractivo');

            $table->unsignedBigInteger('id_producto')->nullable()->after('id_cliente');
            $table->unsignedBigInteger('id_producto2')->nullable()->after('id_producto');
            $table->unsignedBigInteger('id_tipo_carga')->nullable()->after('id_producto2');
            $table->unsignedBigInteger('id_tipo_carga2')->nullable()->after('id_tipo_carga');
            $table->unsignedBigInteger('id_chofer')->nullable()->after('id_tipo_carga2');
            $table->unsignedBigInteger('id_chofer2')->nullable()->after('id_chofer');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->unsignedBigInteger('id_user_recepcion')->nullable();
            $table->unsignedBigInteger('id_buque')->nullable();
            $table->unsignedBigInteger('id_turno')->nullable();
            $table->unsignedBigInteger('id_moneda')->nullable();

            $table->date('fecha_parte')->nullable();
            $table->decimal('peso1', 12, 2)->nullable()->after('toneladas');
            $table->decimal('peso2', 12, 2)->nullable()->after('peso1');
            $table->unsignedInteger('distancia')->nullable()->after('peso2');
            $table->string('conduce', 100)->nullable();
            $table->tinyInteger('cancelada')->default(0);
            $table->tinyInteger('imprimir')->default(0);
            $table->decimal('ingreso_mt', 12, 2)->nullable();
            $table->decimal('flete_mt', 12, 2)->nullable();

            $table->index('id_solicitud', 'cartas_porte_id_solicitud_index');
            $table->index('id_tractivo', 'cartas_porte_id_tractivo_index');
            $table->index('id_arrastre', 'cartas_porte_id_arrastre_index');
            $table->index('id_chofer', 'cartas_porte_id_chofer_index');
            $table->index('id_producto', 'cartas_porte_id_producto_index');
            $table->index('id_tipo_carga', 'cartas_porte_id_tipo_carga_index');
            $table->index('id_user', 'cartas_porte_id_user_index');
        });
    }

    public function down(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->dropIndex('cartas_porte_id_user_index');
            $table->dropIndex('cartas_porte_id_tipo_carga_index');
            $table->dropIndex('cartas_porte_id_producto_index');
            $table->dropIndex('cartas_porte_id_chofer_index');
            $table->dropIndex('cartas_porte_id_arrastre_index');
            $table->dropIndex('cartas_porte_id_tractivo_index');
            $table->dropIndex('cartas_porte_id_solicitud_index');
            $table->dropColumn([
                'id_solicitud', 'id_tractivo', 'id_arrastre', 'id_chofer', 'id_chofer2',
                'id_tipo_carga', 'id_tipo_carga2', 'id_producto', 'id_producto2',
                'id_user', 'id_user_recepcion', 'id_buque', 'id_turno', 'id_moneda',
                'fecha_parte', 'peso1', 'peso2', 'distancia', 'conduce',
                'cancelada', 'imprimir', 'ingreso_mt', 'flete_mt',
            ]);
        });
    }
};