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
        // Tipos de vehículos
        Schema::create('tipos_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('descripcion', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Tabla principal de tractivos (flota)
        Schema::create('tractivos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);
            $table->string('placa', 50)->unique();
            $table->foreignId('id_tipo_vehiculo')->constrained('tipos_vehiculos')->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->integer('anno')->nullable();
            $table->string('color', 50)->nullable();
            $table->string('numero_motor', 100)->nullable();
            $table->string('numero_chasis', 100)->nullable();
            $table->string('numero_caja', 100)->nullable();
            $table->decimal('capacidad_toneladas', 8, 2)->nullable();
            $table->decimal('capacidad_m3', 8, 2)->nullable();
            $table->string('estado', 50)->default('activo');
            $table->date('fecha_alta')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->decimal('kilometraje_actual', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('placa');
            $table->index('estado');
            $table->index('id_tipo_vehiculo');
        });

        // Motores
        Schema::create('motores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('numero_serie', 100)->nullable();
            $table->foreignId('id_tractivo')->constrained('tractivos')->nullable();
            $table->string('estado', 50)->default('disponible');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_tractivo');
            $table->index('estado');
        });

        // Cajas de transmisión
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('numero_serie', 100)->nullable();
            $table->foreignId('id_tractivo')->constrained('tractivos')->nullable();
            $table->string('estado', 50)->default('disponible');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_tractivo');
            $table->index('estado');
        });

        // Diferenciales
        Schema::create('diferenciales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('numero_serie', 100)->nullable();
            $table->foreignId('id_tractivo')->constrained('tractivos')->nullable();
            $table->string('estado', 50)->default('disponible');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_tractivo');
            $table->index('estado');
        });

        // Tipos de arrastres
        Schema::create('tipos_arrastres', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('descripcion', 500)->nullable();
            $table->decimal('capacidad_toneladas', 8, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Baterías
        Schema::create('baterias', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->unique();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->foreignId('id_tractivo')->constrained('tractivos')->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->date('fecha_retiro')->nullable();
            $table->string('estado', 50)->default('activa');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_tractivo');
            $table->index('estado');
        });

        // Neumáticos
        Schema::create('neumaticos', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->unique();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('medida', 50)->nullable();
            $table->foreignId('id_tractivo')->constrained('tractivos')->nullable();
            $table->date('fecha_instalacion')->nullable();
            $table->date('fecha_retiro')->nullable();
            $table->decimal('kilometraje', 12, 2)->default(0);
            $table->string('estado', 50)->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_tractivo');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neumaticos');
        Schema::dropIfExists('baterias');
        Schema::dropIfExists('tipos_arrastres');
        Schema::dropIfExists('diferenciales');
        Schema::dropIfExists('cajas');
        Schema::dropIfExists('motores');
        Schema::dropIfExists('tractivos');
        Schema::dropIfExists('tipos_vehiculos');
    }
};
