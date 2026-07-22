<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Áreas del organigrama
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->foreignId('id_area_padre')->nullable()->constrained('areas');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Entidades (unidades organizativas)
        Schema::create('entidades', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->foreignId('id_area')->constrained('areas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Cargos
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->text('funciones')->nullable();
            $table->text('medios_requeridos')->nullable();
            $table->text('competencias')->nullable();
            $table->boolean('es_chofer')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Bolsa de empleados (personal)
        Schema::create('bolsa', function (Blueprint $table) {
            $table->id();
            $table->string('ci', 20)->unique();
            $table->string('nombre', 255);
            $table->string('apellidos', 255);
            $table->string('sexo', 1)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('direccion', 500)->nullable();
            $table->string('telefono', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->foreignId('id_cargo')->constrained('cargos')->nullable();
            $table->foreignId('id_entidad')->constrained('entidades')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('ci');
            $table->index('id_cargo');
            $table->index('id_entidad');
        });

        // Turnos de trabajo
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->time('hora_entrada');
            $table->time('hora_salida');
            $table->integer('dias_descanso')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Movimientos de personal (altas, bajas, traslados)
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bolsa')->constrained('bolsa');
            $table->string('tipo_movimiento', 50); // alta, baja, traslado
            $table->date('fecha_movimiento');
            $table->foreignId('id_entidad_origen')->constrained('entidades')->nullable();
            $table->foreignId('id_entidad_destino')->constrained('entidades')->nullable();
            $table->foreignId('id_cargo')->constrained('cargos')->nullable();
            $table->foreignId('id_turno')->constrained('turnos')->nullable();
            $table->decimal('salario', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('id_bolsa');
            $table->index('tipo_movimiento');
            $table->index('fecha_movimiento');
        });

        // Incidencias laborales
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bolsa')->constrained('bolsa');
            $table->string('tipo_incidencia', 100);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('documentos')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_bolsa');
            $table->index('fecha_inicio');
        });

        // Penalizaciones
        Schema::create('penalizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bolsa')->constrained('bolsa');
            $table->string('tipo_penalizacion', 100);
            $table->date('fecha');
            $table->decimal('monto', 12, 2)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_bolsa');
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalizaciones');
        Schema::dropIfExists('incidencias');
        Schema::dropIfExists('movimientos');
        Schema::dropIfExists('turnos');
        Schema::dropIfExists('bolsa');
        Schema::dropIfExists('cargos');
        Schema::dropIfExists('entidades');
        Schema::dropIfExists('areas');
    }
};
