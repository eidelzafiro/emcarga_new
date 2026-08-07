<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // Centros de costo
        // ============================================================
        Schema::create('centros_costos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ============================================================
        // Catálogos RRHH complementarios
        // ============================================================
        Schema::create('tipos_articulos_bolsa', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('competencias_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cargo')->constrained('cargos')->cascadeOnDelete();
            $table->string('competencia', 255);
            $table->string('nivel', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('funciones_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cargo')->constrained('cargos')->cascadeOnDelete();
            $table->string('funcion', 255);
            $table->text('descripcion')->nullable();
            $table->integer('orden')->nullable();
            $table->timestamps();
        });

        Schema::create('tipos_jefe_grupo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('pagos_adicionales_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cargo')->constrained('cargos')->cascadeOnDelete();
            $table->foreignId('id_tipo_pago_adicional')->constrained('tipos_pagos_adicionales')->cascadeOnDelete();
            $table->decimal('monto', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['id_cargo', 'id_tipo_pago_adicional'], 'cargo_pago_adicional_unique');
        });

        Schema::create('tipos_ramas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_sistemas_cuc', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_sistemas_cuc');
        Schema::dropIfExists('tipos_ramas');
        Schema::dropIfExists('pagos_adicionales_cargo');
        Schema::dropIfExists('tipos_jefe_grupo');
        Schema::dropIfExists('funciones_cargo');
        Schema::dropIfExists('competencias_cargo');
        Schema::dropIfExists('tipos_articulos_bolsa');
        Schema::dropIfExists('centros_costos');
    }
};
