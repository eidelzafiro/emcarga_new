<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía `ordenes_taller` con paridad del legacy CI3 `tec_ordentaller`
 * (cabecera completa de la OT: horas, clasificación, motivo de entrada,
 * plan de mantenimiento, prueba de motor, paralización, taller exterior).
 *
 * La tabla ya tiene 59 filas (ETL previo) y NO se dropea: se añaden columnas
 * con ALTER para no perder datos. `estado` se deriva de fecha_salida_real/cancelada
 * (no es una columna de estado explícita en el legacy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_taller', function (Blueprint $table) {
            $table->string('hora_ingreso', 20)->nullable()->after('fecha_ingreso');
            $table->date('fecha_salida')->nullable()->after('hora_ingreso');
            $table->string('hora_salida', 20)->nullable()->after('fecha_salida');
            $table->decimal('ottiempo', 10, 2)->default(0)->after('hora_salida');
            $table->foreignId('id_user')->nullable()->after('ottiempo')->constrained('users');
            $table->foreignId('id_motivo_entrada')->nullable()->after('id_user')->constrained('motivos_entrada_taller');
            $table->foreignId('id_clasificacion')->nullable()->after('id_motivo_entrada')->constrained('clasificaciones_ordenes_taller');
            $table->decimal('cant_clasificacion', 20, 2)->nullable()->after('id_clasificacion');
            $table->foreignId('id_reporte')->nullable()->after('cant_clasificacion')->constrained('bolsa');
            $table->foreignId('id_confeccionado')->nullable()->after('id_reporte')->constrained('bolsa');
            $table->foreignId('id_operario')->nullable()->after('id_confeccionado')->constrained('bolsa');
            $table->text('notas')->nullable()->after('id_operario');
            $table->boolean('cancelada')->default(false)->after('notas');
            $table->string('tipo_mtto', 255)->nullable()->after('cancelada');
            $table->integer('km_mtto')->nullable()->after('tipo_mtto');
            $table->integer('planificacion')->nullable()->after('km_mtto');
            $table->integer('km_mtto_prox')->nullable()->after('planificacion');
            $table->string('ot_paralizado', 255)->nullable()->after('km_mtto_prox');
            $table->string('ot_rotura_en_linea', 255)->nullable()->after('ot_paralizado');
            $table->string('ot_largo_plazo', 255)->nullable()->after('ot_rotura_en_linea');
            $table->decimal('comb_taller', 10, 2)->default(0)->after('ot_largo_plazo');
            $table->foreignId('id_motor')->nullable()->after('comb_taller')->constrained('motores');
            $table->foreignId('id_taller')->nullable()->after('id_motor')->constrained('talleres');
            $table->foreignId('id_unidad')->nullable()->after('id_taller')->constrained('entidades');
            $table->foreignId('id_entidad')->nullable()->after('id_unidad')->constrained('entidades');
            // Prueba de motor (compresión de cilindros, consumos, presiones, temperaturas)
            $table->string('pl_cons_comb', 255)->nullable()->after('id_entidad');
            $table->string('pl_cons_aceite', 255)->nullable()->after('pl_cons_comb');
            $table->string('pl_cil1', 255)->nullable()->after('pl_cons_aceite');
            $table->string('pl_cil2', 255)->nullable()->after('pl_cil1');
            $table->string('pl_cil3', 255)->nullable()->after('pl_cil2');
            $table->string('pl_cil4', 255)->nullable()->after('pl_cil3');
            $table->string('pl_cil5', 255)->nullable()->after('pl_cil4');
            $table->string('pl_cil6', 255)->nullable()->after('pl_cil5');
            $table->string('pl_cil7', 255)->nullable()->after('pl_cil6');
            $table->string('pl_cil8', 255)->nullable()->after('pl_cil7');
            $table->string('pl_presion_aceite_baja', 255)->nullable()->after('pl_cil8');
            $table->string('pl_presion_aceite_alta', 255)->nullable()->after('pl_presion_aceite_baja');
            $table->integer('pl_temp_agua')->nullable()->after('pl_presion_aceite_alta');
            $table->integer('pl_temp_aceite')->nullable()->after('pl_temp_agua');
            $table->string('pl_observacion', 255)->nullable()->after('pl_temp_aceite');

            $table->index(['fecha_ingreso', 'id_tractivo']);
            $table->index('cancelada');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_taller', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pl_observacion'); // noop guard
        });
        // Drop de columnas y FKs añadidas
        Schema::table('ordenes_taller', function (Blueprint $table) {
            $table->dropColumn([
                'pl_observacion', 'pl_temp_aceite', 'pl_temp_agua', 'pl_presion_aceite_alta',
                'pl_presion_aceite_baja', 'pl_cil8', 'pl_cil7', 'pl_cil6', 'pl_cil5', 'pl_cil4',
                'pl_cil3', 'pl_cil2', 'pl_cil1', 'pl_cons_aceite', 'pl_cons_comb',
            ]);
            $table->dropConstrainedForeignId('id_entidad');
            $table->dropConstrainedForeignId('id_unidad');
            $table->dropConstrainedForeignId('id_taller');
            $table->dropConstrainedForeignId('id_motor');
            $table->dropColumn('comb_taller');
            $table->dropColumn('ot_largo_plazo');
            $table->dropColumn('ot_rotura_en_linea');
            $table->dropColumn('ot_paralizado');
            $table->dropColumn('km_mtto_prox');
            $table->dropColumn('planificacion');
            $table->dropColumn('km_mtto');
            $table->dropColumn('tipo_mtto');
            $table->dropColumn('cancelada');
            $table->dropColumn('notas');
            $table->dropConstrainedForeignId('id_operario');
            $table->dropConstrainedForeignId('id_confeccionado');
            $table->dropConstrainedForeignId('id_reporte');
            $table->dropColumn('cant_clasificacion');
            $table->dropConstrainedForeignId('id_clasificacion');
            $table->dropConstrainedForeignId('id_motivo_entrada');
            $table->dropConstrainedForeignId('id_user');
            $table->dropColumn('ottiempo');
            $table->dropColumn('hora_salida');
            $table->dropColumn('fecha_salida');
            $table->dropColumn('hora_ingreso');
        });
    }
};
