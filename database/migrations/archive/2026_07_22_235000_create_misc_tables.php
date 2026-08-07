<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // Catálogos varios
        // ============================================================
        Schema::create('clientes_mm', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_aceites', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_entidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_organismo')->nullable()->constrained('organismos');
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('causas_gps', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('causas_multas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('elementos_gasto', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->string('subelemento', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ============================================================
        // Empleados (registro maestro)
        // ============================================================
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->string('expediente', 50)->nullable()->unique();
            $table->foreignId('id_area')->nullable()->constrained('areas');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ============================================================
        // Choferes
        // ============================================================
        Schema::create('choferes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->string('ci', 20)->nullable()->unique();
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_empleado')->nullable()->constrained('empleados');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ============================================================
        // Devoluciones de cartas porte (ajustes de flete)
        // ============================================================
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carta_porte')->constrained('cartas_porte');
            $table->foreignId('id_cliente')->nullable()->constrained('clientes');
            $table->foreignId('id_cliente_mm')->nullable()->constrained('clientes_mm');
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_empleado')->nullable()->constrained('empleados');
            $table->date('fecha');
            $table->decimal('aumento_flete_mn', 12, 2)->default(0);
            $table->decimal('aumento_flete_me', 12, 2)->default(0);
            $table->decimal('aumento_demora', 12, 2)->default(0);
            $table->decimal('aumento_salario', 12, 2)->default(0);
            $table->decimal('aumento_alquiler', 12, 2)->default(0);
            $table->decimal('aumento_izaje', 12, 2)->default(0);
            $table->decimal('disminucion_flete_mn', 12, 2)->default(0);
            $table->decimal('disminucion_flete_me', 12, 2)->default(0);
            $table->decimal('disminucion_demora', 12, 2)->default(0);
            $table->decimal('disminucion_salario', 12, 2)->default(0);
            $table->decimal('disminucion_alquiler', 12, 2)->default(0);
            $table->decimal('disminucion_izaje', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Descuentos de empleados
        // ============================================================
        Schema::create('descuentos_empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_empleado')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->decimal('tiempo', 8, 2)->comment('Horas/minutos descontados');
            $table->text('motivo')->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Importes GPS y Multas
        // ============================================================
        Schema::create('importes_gps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_chofer')->constrained('choferes');
            $table->foreignId('id_causa_gps')->constrained('causas_gps');
            $table->date('fecha');
            $table->decimal('importe', 10, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('importes_multas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_chofer')->constrained('choferes');
            $table->foreignId('id_causa_multa')->constrained('causas_multas');
            $table->date('fecha');
            $table->decimal('importe', 10, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Vacaciones de choferes
        // ============================================================
        Schema::create('vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_chofer')->constrained('choferes')->cascadeOnDelete();
            $table->date('fecha');
            $table->integer('dias')->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Estadísticas de explotación (cp_estadistica)
        // ============================================================
        Schema::create('estadisticas_explotacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_hoja_ruta')->constrained('hojas_ruta')->cascadeOnDelete();
            $table->date('fecha_indicadores');
            $table->integer('viajes')->default(0);
            $table->decimal('kms_carga', 12, 2)->default(0);
            $table->decimal('kms_vacio', 12, 2)->default(0);
            $table->decimal('kms_total', 12, 2)->default(0);
            $table->decimal('toneladas_posibles', 12, 2)->default(0);
            $table->decimal('toneladas_reales', 12, 2)->default(0);
            $table->decimal('trafico_posible', 12, 2)->default(0);
            $table->decimal('trafico_producido', 12, 2)->default(0);
            $table->timestamps();
        });

        // ============================================================
        // Registro de órdenes de taller (OT)
        // ============================================================
        Schema::create('registro_ordenes_taller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->date('fecha_salida_taller');
            $table->integer('tiempo_minutos')->default(0)->comment('Minutos en taller');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Detalle de prefacturas
        // ============================================================
        Schema::create('detalle_prefacturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_prefactura')->constrained('prefacturas')->cascadeOnDelete();
            $table->foreignId('id_moneda')->nullable()->constrained('monedas');
            $table->foreignId('id_origen')->nullable()->constrained('lugares');
            $table->foreignId('id_destino')->nullable()->constrained('lugares');
            $table->foreignId('id_tipo_carga')->nullable()->constrained('tipos_cargas');
            $table->decimal('importe', 12, 2)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_prefacturas');
        Schema::dropIfExists('registro_ordenes_taller');
        Schema::dropIfExists('estadisticas_explotacion');
        Schema::dropIfExists('vacaciones');
        Schema::dropIfExists('importes_multas');
        Schema::dropIfExists('importes_gps');
        Schema::dropIfExists('descuentos_empleados');
        Schema::dropIfExists('devoluciones');
        Schema::dropIfExists('empleados');
        Schema::dropIfExists('choferes');
        Schema::dropIfExists('elementos_gasto');
        Schema::dropIfExists('causas_multas');
        Schema::dropIfExists('causas_gps');
        Schema::dropIfExists('tipos_entidad');
        Schema::dropIfExists('tipos_aceites');
        Schema::dropIfExists('clientes_mm');
    }
};
