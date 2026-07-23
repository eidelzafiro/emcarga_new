<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_incidencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_penalizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_contratos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_sistemas_pago', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_pagos_adicionales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('plantilla', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->foreignId('id_cargo')->nullable()->constrained('cargos');
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->foreignId('id_bolsa')->nullable()->constrained('bolsa');
            $table->foreignId('id_turno')->nullable()->constrained('turnos');
            $table->foreignId('id_tipo_contrato')->nullable()->constrained('tipos_contratos');
            $table->foreignId('id_tipo_sistema_pago')->nullable()->constrained('tipos_sistemas_pago');
            $table->integer('plazas')->default(1);
            $table->integer('cubiertas')->default(0);
            $table->decimal('salario_base_mn', 12, 2)->nullable();
            $table->decimal('salario_base_mlc', 12, 2)->nullable();
            $table->string('categoria', 50)->nullable();
            $table->boolean('aseo')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('id_cargo');
            $table->index('id_entidad');
        });

        Schema::create('historial_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_movimiento')->nullable()->constrained('movimientos');
            $table->foreignId('id_bolsa')->constrained('bolsa');
            $table->string('tipo', 50);
            $table->date('fecha');
            $table->foreignId('id_plantilla')->nullable()->constrained('plantilla');
            $table->string('numero_nomina', 50)->nullable();
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('id_bolsa');
            $table->index('tipo');
            $table->index('fecha');
        });

        Schema::create('tipos_tasas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('unidad', 50)->nullable();
            $table->decimal('valor', 12, 4)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_tasas');
        Schema::dropIfExists('historial_movimientos');
        Schema::dropIfExists('plantilla');
        Schema::dropIfExists('tipos_pagos_adicionales');
        Schema::dropIfExists('tipos_sistemas_pago');
        Schema::dropIfExists('tipos_contratos');
        Schema::dropIfExists('tipos_penalizaciones');
        Schema::dropIfExists('tipos_incidencias');
    }
};
