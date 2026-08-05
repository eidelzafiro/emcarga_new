<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha técnica de tipos de arrastre (tec_tipoarrastres) con paridad total
 * sobre el formulario legacy. Replica el esquema técnico de tipos_tractivos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_arrastres', function (Blueprint $table) {
            $table->foreignId('id_marca')->nullable()->after('capacidad_toneladas')->constrained('marcas')->nullOnDelete();
            $table->foreignId('id_modelo')->nullable()->after('id_marca')->constrained('modelos')->nullOnDelete();
            $table->foreignId('id_pais')->nullable()->after('id_modelo')->constrained('paises')->nullOnDelete();
            $table->foreignId('id_tipo_equipo')->nullable()->after('id_pais')->constrained('tipos_equipos')->nullOnDelete();
            $table->integer('fabricacion')->nullable()->after('id_tipo_equipo');
            $table->integer('frecuencia')->nullable()->after('fabricacion');
            $table->foreignId('id_medida_del')->nullable()->after('frecuencia')->constrained('medidas_neumaticos')->nullOnDelete();
            $table->foreignId('id_medida_tra')->nullable()->after('id_medida_del')->constrained('medidas_neumaticos')->nullOnDelete();
            $table->foreignId('id_medida_res')->nullable()->after('id_medida_tra')->constrained('medidas_neumaticos')->nullOnDelete();
            $table->integer('neum_del_cant')->nullable()->after('id_medida_res');
            $table->integer('neum_tras_cant')->nullable()->after('neum_del_cant');
            $table->integer('neum_resp_cant')->nullable()->after('neum_tras_cant');
            $table->foreignId('id_tipo_suspension')->nullable()->after('neum_resp_cant')->constrained('tipos_suspension')->nullOnDelete();
            $table->integer('ejes_cant')->nullable()->after('id_tipo_suspension');
            $table->string('eject_trac', 50)->nullable()->after('ejes_cant');
            $table->decimal('dist_frente', 8, 2)->nullable()->after('eject_trac');
            $table->decimal('dist_trasera', 8, 2)->nullable()->after('dist_frente');
            $table->decimal('largo_garganta', 8, 2)->nullable()->after('dist_trasera');
            $table->decimal('altura_piso', 8, 2)->nullable()->after('largo_garganta');
            $table->decimal('altura_total', 8, 2)->nullable()->after('altura_piso');
            $table->decimal('largo_total', 8, 2)->nullable()->after('altura_total');
            $table->decimal('ancho_total', 8, 2)->nullable()->after('largo_total');
            $table->foreignId('id_tipo_combustible')->nullable()->after('ancho_total')->constrained('tipos_combustibles')->nullOnDelete();
            $table->foreignId('id_lubricante')->nullable()->after('id_tipo_combustible')->constrained('lubricantes')->nullOnDelete();
            $table->foreignId('id_lub_cubo')->nullable()->after('id_lubricante')->constrained('lubricantes')->nullOnDelete();
            $table->foreignId('id_tipo_mantenimiento')->nullable()->after('id_lub_cubo')->constrained('tipos_mantenimiento')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tipos_arrastres', function (Blueprint $table) {
            $table->dropForeign([
                'id_marca', 'id_modelo', 'id_pais', 'id_tipo_equipo',
                'id_medida_del', 'id_medida_tra', 'id_medida_res',
                'id_tipo_suspension', 'id_tipo_combustible', 'id_lubricante',
                'id_lub_cubo', 'id_tipo_mantenimiento',
            ]);

            $table->dropColumn([
                'id_marca', 'id_modelo', 'id_pais', 'id_tipo_equipo',
                'fabricacion', 'frecuencia', 'id_medida_del', 'id_medida_tra', 'id_medida_res',
                'neum_del_cant', 'neum_tras_cant', 'neum_resp_cant',
                'id_tipo_suspension', 'ejes_cant', 'eject_trac',
                'dist_frente', 'dist_trasera', 'largo_garganta', 'altura_piso',
                'altura_total', 'largo_total', 'ancho_total',
                'id_tipo_combustible', 'id_lubricante', 'id_lub_cubo', 'id_tipo_mantenimiento',
            ]);
        });
    }
};
