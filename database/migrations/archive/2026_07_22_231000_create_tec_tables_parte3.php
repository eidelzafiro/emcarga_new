<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // Catálogos técnicos
        // ============================================================

        Schema::create('motivos_baja_bateria', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('motivos_entrada_taller', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_roturas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('clasificaciones_ordenes_taller', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_sistemas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_suspension', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('locales_electricos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('acciones_hotkeys', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ============================================================
        // Arrastres (remolques/semi-remolques)
        // ============================================================
        Schema::create('arrastres', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('chapa', 50)->nullable();
            $table->foreignId('id_marca')->nullable()->constrained('marcas');
            $table->foreignId('id_tipo_equipo')->nullable()->constrained('tipos_equipos');
            $table->decimal('capacidad', 10, 2)->nullable();
            $table->string('lot', 100)->nullable();
            $table->string('circulacion', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ============================================================
        // Hotkeys (atajos de teclado personalizados)
        // ============================================================
        Schema::create('hotkeys', function (Blueprint $table) {
            $table->id();
            $table->string('combinacion', 100)->comment('Ej: Ctrl+Shift+F');
            $table->foreignId('id_accion')->constrained('acciones_hotkeys');
            $table->foreignId('id_usuario')->nullable()->constrained('users');
            $table->string('tipo', 10)->default('A')->comment('A=accion, R=reporte');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ============================================================
        // Asociaciones tractivos ↔ arrastres (pivote N:M)
        // ============================================================
        Schema::create('arrastre_tractivo', function (Blueprint $table) {
            $table->foreignId('id_tractivo')->constrained('tractivos')->cascadeOnDelete();
            $table->foreignId('id_arrastre')->constrained('arrastres')->cascadeOnDelete();
            $table->primary(['id_tractivo', 'id_arrastre']);
            $table->timestamps();
        });

        // ============================================================
        // Historial mensual de tractivos (snapshots cerrados)
        // ============================================================
        Schema::create('historial_tractivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->foreignId('id_grupo')->nullable()->constrained('grupos');
            $table->foreignId('id_caja')->nullable()->constrained('cajas');
            $table->foreignId('id_motor')->nullable()->constrained('motores');
            $table->foreignId('id_diferencial')->nullable()->constrained('diferenciales');
            $table->foreignId('id_unidad')->nullable()->constrained('unidades');
            $table->date('fecha_cierre');
            $table->decimal('km_historico', 12, 2)->nullable();
            $table->decimal('km_motor', 12, 2)->nullable();
            $table->decimal('km_caja', 12, 2)->nullable();
            $table->decimal('km_diferencial', 12, 2)->nullable();
            $table->decimal('indice', 8, 2)->nullable();
            $table->decimal('indice_acumulado', 8, 2)->nullable();
            $table->decimal('plan_combustible', 12, 2)->nullable();
            $table->string('gps', 100)->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Balance eléctrico (consumo por local/equipo)
        // ============================================================
        Schema::create('balances_electricos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_local')->constrained('locales_electricos');
            $table->foreignId('id_equipo')->constrained('equipos_electricos');
            $table->date('fecha');
            $table->decimal('lectura_inicial', 12, 2)->nullable();
            $table->decimal('lectura_final', 12, 2)->nullable();
            $table->decimal('consumo', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balances_electricos');
        Schema::dropIfExists('historial_tractivos');
        Schema::dropIfExists('arrastre_tractivo');
        Schema::dropIfExists('hotkeys');
        Schema::dropIfExists('arrastres');
        Schema::dropIfExists('acciones_hotkeys');
        Schema::dropIfExists('locales_electricos');
        Schema::dropIfExists('tipos_suspension');
        Schema::dropIfExists('tipos_sistemas');
        Schema::dropIfExists('clasificaciones_ordenes_taller');
        Schema::dropIfExists('tipos_roturas');
        Schema::dropIfExists('motivos_entrada_taller');
        Schema::dropIfExists('motivos_baja_bateria');
    }
};
