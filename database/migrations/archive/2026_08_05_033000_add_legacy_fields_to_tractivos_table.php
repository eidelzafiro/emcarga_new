<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cabecera completa del tractivo legacy (tec_tractivos) con paridad total
 * sobre el formulario legacy. Se añaden FKs reales a los componentes
 * (motores/cajas/diferenciales) y catálogos (grupo, servicio, colores,
 * estado, lubricante hidráulico) y todos los campos de planes, combustible,
 * vencimientos, numeración y reconstrucción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            // FKs a componentes (decisión usuario 2026-08-05: FKs reales)
            $table->foreignId('id_motor')->nullable()->after('id_tipo_vehiculo')->constrained('motores')->nullOnDelete();
            $table->foreignId('id_caja')->nullable()->after('id_motor')->constrained('cajas')->nullOnDelete();
            $table->foreignId('id_diferencial')->nullable()->after('id_caja')->constrained('diferenciales')->nullOnDelete();

            // Catálogos
            $table->foreignId('id_grupo')->nullable()->after('id_diferencial')->constrained('grupos')->nullOnDelete();
            $table->foreignId('id_tipo_servicio')->nullable()->after('id_grupo')->constrained('tipos_servicios')->nullOnDelete();
            $table->foreignId('id_color_primario')->nullable()->after('id_tipo_servicio')->constrained('colores')->nullOnDelete();
            $table->foreignId('id_color_secundario')->nullable()->after('id_color_primario')->constrained('colores')->nullOnDelete();
            $table->foreignId('id_tipo_estado')->nullable()->after('id_color_secundario')->constrained('estados_componentes')->nullOnDelete();
            $table->foreignId('id_lubricante_hidraulico')->nullable()->after('id_tipo_estado')->constrained('lubricantes')->nullOnDelete();

            // Identificación / numeración
            $table->string('vin', 100)->nullable()->after('color');
            $table->string('nro_carroceria', 100)->nullable()->after('vin');
            $table->string('nro_registro', 100)->nullable()->after('nro_carroceria');
            $table->string('nro_resolucion', 100)->nullable()->after('nro_registro');

            // Físicos
            $table->decimal('tara', 10, 2)->nullable()->after('nro_resolucion');
            $table->decimal('cap_deposito', 10, 2)->nullable()->after('tara');
            $table->decimal('cap_hidraulico', 10, 2)->nullable()->after('cap_deposito');
            $table->string('cta_combustible', 100)->nullable()->after('cap_hidraulico');

            // Consumos / índices
            $table->decimal('indice_consumo', 12, 2)->nullable()->after('cta_combustible');
            $table->decimal('indice_aceite', 12, 2)->nullable()->after('indice_consumo');

            // Kilometrajes operativos
            $table->decimal('kms_disp', 12, 2)->nullable()->after('kilometraje_actual');
            $table->integer('kms_plan_mtto')->nullable()->after('kms_disp');

            // Planes
            $table->decimal('plan_comb', 12, 2)->nullable()->after('kms_plan_mtto');
            $table->decimal('plan_tn', 12, 2)->nullable()->after('plan_comb');
            $table->decimal('plan_viajes', 12, 2)->nullable()->after('plan_tn');
            $table->decimal('plan_gastos', 12, 2)->nullable()->after('plan_viajes');
            $table->decimal('plan_cdt', 12, 2)->nullable()->after('plan_gastos');
            $table->decimal('plan_diario', 12, 2)->nullable()->after('plan_cdt');

            // Vencimientos / documentos
            $table->string('ficav', 100)->nullable()->after('plan_diario');
            $table->date('femision_ficav')->nullable()->after('ficav');
            $table->date('fvence_ficav')->nullable()->after('femision_ficav');
            $table->string('lot', 100)->nullable()->after('fvence_ficav');
            $table->date('femision_lot')->nullable()->after('lot');
            $table->date('fvence_lot')->nullable()->after('femision_lot');
            $table->string('circulacion', 100)->nullable()->after('fvence_lot');
            $table->date('femision_circ')->nullable()->after('circulacion');
            $table->date('fvence_circ')->nullable()->after('femision_circ');

            // Misceláneos
            $table->date('f_reconstruccion')->nullable()->after('fvence_circ');
            $table->string('gps', 100)->nullable()->after('fecha_baja');

            $table->index('id_grupo');
            $table->index('id_tipo_servicio');
            $table->index('id_tipo_estado');
        });
    }

    public function down(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropForeign(['id_motor']);
            $table->dropForeign(['id_caja']);
            $table->dropForeign(['id_diferencial']);
            $table->dropForeign(['id_grupo']);
            $table->dropForeign(['id_tipo_servicio']);
            $table->dropForeign(['id_color_primario']);
            $table->dropForeign(['id_color_secundario']);
            $table->dropForeign(['id_tipo_estado']);
            $table->dropForeign(['id_lubricante_hidraulico']);

            $table->dropColumn([
                'id_motor', 'id_caja', 'id_diferencial', 'id_grupo',
                'id_tipo_servicio', 'id_color_primario', 'id_color_secundario',
                'id_tipo_estado', 'id_lubricante_hidraulico',
                'vin', 'nro_carroceria', 'nro_registro', 'nro_resolucion',
                'tara', 'cap_deposito', 'cap_hidraulico', 'cta_combustible',
                'indice_consumo', 'indice_aceite', 'kms_disp', 'kms_plan_mtto',
                'plan_comb', 'plan_tn', 'plan_viajes', 'plan_gastos', 'plan_cdt', 'plan_diario',
                'ficav', 'femision_ficav', 'fvence_ficav', 'lot', 'femision_lot', 'fvence_lot',
                'circulacion', 'femision_circ', 'fvence_circ', 'f_reconstruccion', 'gps',
            ]);
        });
    }
};
