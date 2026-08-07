<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_catalogo_lugares', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 100);
            $table->string('abreviatura', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_modelo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->foreignId('id_tipo_modelo')->nullable()->constrained('tipos_modelo');
            $table->string('modelo', 50);
            $table->decimal('ancho', 10, 2)->nullable();
            $table->decimal('alto', 10, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('configuraciones_modelo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30);
            $table->foreignId('id_tipo_modelo')->nullable()->constrained('tipos_modelo');
            $table->integer('set_x')->nullable();
            $table->integer('set_y')->nullable();
            $table->integer('letra')->nullable();
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('tipos_estados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->string('imagen', 100)->nullable();
            $table->string('siglas', 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_cargas_reporte', function (Blueprint $table) {
            $table->id();
            $table->decimal('km1', 8, 2)->nullable();
            $table->decimal('km2', 8, 2)->nullable();
            $table->decimal('km3', 8, 2)->nullable();
            $table->decimal('km4', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('clientes_seleccion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('turnos_comerciales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('movil_web', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->nullable();
            $table->string('hoja_ruta', 255)->nullable();
            $table->decimal('km', 10, 2)->nullable();
            $table->decimal('combustible', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->text('mensaje');
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->foreignId('id_user')->constrained('users');
            $table->foreignId('id_perfil')->nullable()->constrained('perfiles_rh');
            $table->boolean('vencida')->default(false);
            $table->timestamps();
        });

        Schema::create('indicadores', function (Blueprint $table) {
            $table->foreignId('id_carta_porte')->constrained('cartas_porte');
            $table->decimal('tn_pos_3', 10, 2)->default(0);
            $table->decimal('tn_real_3', 10, 2)->default(0);
            $table->decimal('km_carga_3', 10, 2)->default(0);
            $table->decimal('km_vacio_3', 10, 2)->default(0);
            $table->decimal('kms_total_3', 10, 2)->default(0);
            $table->decimal('traf_real_3', 10, 2)->default(0);
            $table->decimal('traf_pos_3', 10, 2)->default(0);
            $table->decimal('tn_pos_4', 10, 2)->default(0);
            $table->decimal('tn_real_4', 10, 2)->default(0);
            $table->decimal('km_carga_4', 10, 2)->default(0);
            $table->decimal('km_vacio_4', 10, 2)->default(0);
            $table->decimal('kms_total_4', 10, 2)->default(0);
            $table->decimal('traf_real_4', 10, 2)->default(0);
            $table->decimal('traf_pos_4', 10, 2)->default(0);
            $table->decimal('tn_pos_5', 10, 2)->default(0);
            $table->decimal('tn_real_5', 10, 2)->default(0);
            $table->decimal('km_carga_5', 10, 2)->default(0);
            $table->decimal('km_vacio_5', 10, 2)->default(0);
            $table->decimal('kms_total_5', 10, 2)->default(0);
            $table->decimal('traf_real_5', 10, 2)->default(0);
            $table->decimal('traf_pos_5', 10, 2)->default(0);
            $table->decimal('tn_pos_6', 10, 2)->default(0);
            $table->decimal('tn_real_6', 10, 2)->default(0);
            $table->decimal('km_carga_6', 10, 2)->default(0);
            $table->decimal('km_vacio_6', 10, 2)->default(0);
            $table->decimal('kms_total_6', 10, 2)->default(0);
            $table->decimal('traf_real_6', 10, 2)->default(0);
            $table->decimal('traf_pos_6', 10, 2)->default(0);
            $table->decimal('tn_pos_7', 10, 2)->default(0);
            $table->decimal('tn_real_7', 10, 2)->default(0);
            $table->decimal('km_carga_7', 10, 2)->default(0);
            $table->decimal('km_vacio_7', 10, 2)->default(0);
            $table->decimal('kms_total_7', 10, 2)->default(0);
            $table->decimal('traf_real_7', 10, 2)->default(0);
            $table->decimal('traf_pos_7', 10, 2)->default(0);
            $table->timestamps();

            $table->primary('id_carta_porte');
        });

        Schema::create('demandas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_demanda');
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->foreignId('id_producto')->constrained('productos');
            $table->foreignId('id_origen')->constrained('lugares');
            $table->foreignId('id_destino')->constrained('lugares');
            $table->foreignId('id_embalaje')->constrained('embalajes');
            $table->integer('viajes')->default(0);
            $table->decimal('kms_totales', 10, 2)->default(0);
            $table->decimal('kms_carga', 10, 2)->default(0);
            $table->decimal('tiempo_demanda', 10, 2)->default(0);
            $table->decimal('tiempo_aceptacion', 10, 2)->default(0);
            $table->longText('datos_mensuales')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('activa');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_cliente');
            $table->index('fecha_demanda');
        });

        Schema::create('pizarra_tractivos', function (Blueprint $table) {
            $table->id();
            $table->integer('mes');
            $table->integer('ano');
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->longText('dias')->nullable();
            $table->timestamps();

            $table->unique(['mes', 'ano', 'id_tractivo']);
        });

        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_carga')->constrained('tipos_cargas');
            $table->decimal('kms', 10, 2)->nullable();
            $table->decimal('tarifa_mt', 12, 2)->nullable();
            $table->string('version', 20)->default('normal');
            $table->timestamps();

            $table->index('id_tipo_carga');
        });

        Schema::create('tarifas_config_carga', function (Blueprint $table) {
            $table->id();
            $table->decimal('demora_1', 10, 2)->default(0);
            $table->decimal('demora_2', 10, 2)->default(0);
            $table->decimal('kms_vacio_1', 10, 2)->default(0);
            $table->decimal('kms_vacio_2', 10, 2)->default(0);
            $table->decimal('tarifa_horaria_1', 10, 2)->default(0);
            $table->decimal('tarifa_horaria_2', 10, 2)->default(0);
            $table->decimal('kms_adicionales_1', 10, 2)->default(0);
            $table->decimal('kms_adicionales_2', 10, 2)->default(0);
            $table->decimal('almacenaje', 10, 2)->default(0);
            $table->decimal('recargo_1', 10, 2)->default(0);
            $table->decimal('recargo_2', 10, 2)->default(0);
            $table->decimal('recargo_3_1', 10, 2)->default(0);
            $table->decimal('recargo_3_2', 10, 2)->default(0);
            $table->decimal('recargo_3_3', 10, 2)->default(0);
            $table->decimal('recargo_4', 10, 2)->default(0);
            $table->decimal('recargo_5', 10, 2)->default(0);
            $table->integer('hora_1')->default(0);
            $table->integer('hora_2')->default(0);
            $table->integer('hora_3')->default(0);
            $table->string('version', 20)->default('carga');
            $table->timestamps();
        });

        Schema::create('tarifas_config_contenedor', function (Blueprint $table) {
            $table->id();
            $table->decimal('demora_1', 10, 2)->default(0);
            $table->decimal('demora_2', 10, 2)->default(0);
            $table->decimal('kms_vacio_1', 10, 2)->default(0);
            $table->decimal('kms_vacio_2', 10, 2)->default(0);
            $table->decimal('tarifa_horaria_1', 10, 2)->default(0);
            $table->decimal('izaje_1', 10, 2)->default(0);
            $table->decimal('izaje_2', 10, 2)->default(0);
            $table->decimal('valor_izaje_mt', 10, 2)->default(0);
            $table->decimal('valor_izaje_me', 10, 2)->default(0);
            $table->decimal('valor_almacenaje', 10, 2)->default(0);
            $table->integer('plazo_libre_exp')->default(0);
            $table->string('version', 20)->default('contenedor');
            $table->timestamps();
        });

        Schema::create('otros_ingresos_pre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carta_porte')->constrained('cartas_porte');
            $table->foreignId('id_tipo_ingreso')->constrained('tipo_ingresos');
            $table->integer('cantidad')->default(0);
            $table->decimal('importe_mn', 12, 2)->default(0);
            $table->timestamps();

            $table->index('id_carta_porte');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otros_ingresos_pre');
        Schema::dropIfExists('tarifas_config_contenedor');
        Schema::dropIfExists('tarifas_config_carga');
        Schema::dropIfExists('tarifas');
        Schema::dropIfExists('pizarra_tractivos');
        Schema::dropIfExists('demandas');
        Schema::dropIfExists('indicadores');
        Schema::dropIfExists('alertas');
        Schema::dropIfExists('movil_web');
        Schema::dropIfExists('turnos_comerciales');
        Schema::dropIfExists('clientes_seleccion');
        Schema::dropIfExists('tipos_cargas_reporte');
        Schema::dropIfExists('tipos_estados');
        Schema::dropIfExists('configuraciones_modelo');
        Schema::dropIfExists('tipos_modelo');
        Schema::dropIfExists('tipos_catalogo_lugares');
    }
};
