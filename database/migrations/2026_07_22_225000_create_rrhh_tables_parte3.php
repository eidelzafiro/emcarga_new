<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provincias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->timestamps();
        });

        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->foreignId('id_provincia')->constrained('provincias');
            $table->timestamps();

            $table->index('id_provincia');
        });

        Schema::create('osdes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->nullable();
            $table->string('nombre', 200);
            $table->string('siglas', 100)->nullable();
            $table->foreignId('id_organismo')->nullable()->constrained('organismos');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('meses', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 2)->nullable();
            $table->integer('dias')->nullable();
            $table->decimal('dias_laborables', 6, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('fondos_tiempo', function (Blueprint $table) {
            $table->id();
            $table->decimal('fondo_tiempo', 8, 4);
            $table->timestamps();
        });

        Schema::create('firmas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('confecciona_nombre', 150)->nullable();
            $table->string('confecciona_cargo', 150)->nullable();
            $table->string('revisa_nombre', 150)->nullable();
            $table->string('revisa_cargo', 150)->nullable();
            $table->string('aprueba_nombre', 150)->nullable();
            $table->string('aprueba_cargo', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('medios_proteccion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->foreignId('id_tipo_medio_proteccion')->nullable()->constrained('tipos_medios_proteccion');
            $table->integer('duracion')->nullable();
            $table->string('tipo_duracion', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_medios_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_medio_proteccion')->constrained('medios_proteccion');
            $table->foreignId('id_cargo')->constrained('cargos');
            $table->timestamps();
        });

        Schema::create('salarios', function (Blueprint $table) {
            $table->id();
            $table->integer('mes');
            $table->integer('ano');
            $table->foreignId('id_bolsa')->constrained('bolsa');
            $table->foreignId('id_movimiento')->nullable()->constrained('movimientos');
            $table->string('numero_nomina', 15)->nullable();
            $table->foreignId('id_area')->nullable()->constrained('areas');
            $table->foreignId('id_sexo')->nullable()->constrained('tipos_sexo');
            $table->foreignId('id_categoria_cargo')->nullable()->constrained('categorias_cargo');
            $table->foreignId('id_cargo')->nullable()->constrained('cargos');
            $table->foreignId('id_tipo_sistema_pago')->nullable()->constrained('tipos_sistemas_pago');
            $table->foreignId('id_grupo_escala')->nullable()->constrained('grupos_escala');
            $table->foreignId('id_nivel_educacion')->nullable()->constrained('tipos_nivel_educacion');
            $table->foreignId('id_integracion_politica')->nullable()->constrained('tipos_integracion_politica');
            $table->foreignId('id_color_piel')->nullable()->constrained('tipos_color_piel');
            $table->decimal('salario_base', 12, 2)->default(0);
            $table->decimal('plus_base', 12, 2)->default(0);
            $table->decimal('tarifa', 12, 6)->default(0);
            $table->decimal('plus', 12, 6)->default(0);
            $table->decimal('cla', 12, 6)->default(0);
            $table->decimal('t_regular', 12, 2)->default(0);
            $table->decimal('t_irregular', 12, 2)->default(0);
            $table->decimal('t_garantia', 12, 2)->default(0);
            $table->decimal('t_doblaje', 12, 2)->default(0);
            $table->decimal('t_nocturna_1', 12, 2)->default(0);
            $table->decimal('t_nocturna_2', 12, 2)->default(0);
            $table->decimal('t_feriados', 12, 2)->default(0);
            $table->decimal('t_extra', 12, 2)->default(0);
            $table->decimal('t_total', 12, 2)->default(0);
            $table->decimal('imp_regular', 12, 2)->default(0);
            $table->decimal('imp_plus', 12, 2)->default(0);
            $table->decimal('imp_adicional', 12, 2)->default(0);
            $table->decimal('imp_cla', 12, 2)->default(0);
            $table->decimal('imp_gps', 12, 2)->default(0);
            $table->decimal('imp_irregular', 12, 2)->default(0);
            $table->decimal('imp_nocturna_1', 12, 2)->default(0);
            $table->decimal('imp_nocturna_2', 12, 2)->default(0);
            $table->decimal('imp_feriados', 12, 2)->default(0);
            $table->decimal('imp_maestrias', 12, 2)->default(0);
            $table->decimal('imp_g_electro', 12, 2)->default(0);
            $table->decimal('imp_garantia', 12, 2)->default(0);
            $table->decimal('imp_doblaje', 12, 2)->default(0);
            $table->decimal('imp_h_extra', 12, 2)->default(0);
            $table->decimal('imp_reservas_alm', 12, 2)->default(0);
            $table->decimal('imp_otros', 12, 2)->default(0);
            $table->decimal('imp_ir_resultado', 12, 2)->default(0);
            $table->decimal('pen_resultado', 12, 2)->default(0);
            $table->decimal('pen_importe', 12, 2)->default(0);
            $table->decimal('imp_resultado', 12, 2)->default(0);
            $table->decimal('imp_salario_final', 12, 2)->default(0);
            $table->decimal('cpl', 12, 2)->default(0);
            $table->decimal('ri', 12, 2)->default(0);
            $table->decimal('cotizacion', 12, 2)->default(0);
            $table->decimal('salario_cotizacion', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('borrador');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mes', 'ano']);
            $table->index('id_bolsa');
        });

        Schema::create('salarios_administrativos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('id_movimiento')->nullable()->constrained('movimientos');
            $table->decimal('feriados', 12, 2)->default(0);
            $table->decimal('irregular', 12, 2)->default(0);
            $table->decimal('cpl', 12, 2)->default(0);
            $table->decimal('alimentos_extra', 12, 2)->default(0);
            $table->decimal('dias_taller', 12, 2)->default(0);
            $table->decimal('h_extra', 12, 2)->default(0);
            $table->decimal('imp_h_extra', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('borrador');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salarios_administrativos');
        Schema::dropIfExists('salarios');
        Schema::dropIfExists('tipos_medios_cargo');
        Schema::dropIfExists('medios_proteccion');
        Schema::dropIfExists('firmas');
        Schema::dropIfExists('fondos_tiempo');
        Schema::dropIfExists('meses');
        Schema::dropIfExists('osdes');
        Schema::dropIfExists('municipios');
        Schema::dropIfExists('provincias');
    }
};
