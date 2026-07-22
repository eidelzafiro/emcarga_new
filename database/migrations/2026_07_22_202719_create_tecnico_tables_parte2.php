<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // Catálogos
        // ============================================================

        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('tipo', 50)->nullable()->comment('motor, caja, diferencial, neumatico, bateria, tractor');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('modelos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->foreignId('id_marca')->constrained('marcas')->cascadeOnDelete();
            $table->string('tipo', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('estados_componentes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('tipo', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('paises', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_lubricantes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_causas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('tipo', 50)->nullable()->comment('aceite, rotura, baja');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('medidas_neumaticos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('medida', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_combustibles', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('destinos_agregados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('consecutivos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);
            $table->integer('ultimo')->default(0);
            $table->string('formato', 50)->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Tipos de tractivos (configuración detallada)
        // ============================================================
        Schema::create('tipos_tractivos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->foreignId('id_marca')->nullable()->constrained('marcas');
            $table->foreignId('id_modelo')->nullable()->constrained('modelos');
            $table->foreignId('id_pais')->nullable()->constrained('paises');
            $table->integer('fabricacion')->nullable();
            $table->string('tipo_equipo', 50)->nullable();
            $table->integer('bat_cant')->nullable();
            $table->decimal('bat_amp', 8, 2)->nullable();
            $table->integer('dif_cant')->nullable();
            $table->string('dif_relacion', 50)->nullable();
            $table->decimal('dif_ancho', 8, 2)->nullable();
            $table->foreignId('id_medida_del')->nullable()->constrained('medidas_neumaticos');
            $table->foreignId('id_medida_tra')->nullable()->constrained('medidas_neumaticos');
            $table->foreignId('id_medida_res')->nullable()->constrained('medidas_neumaticos');
            $table->integer('neum_del_cant')->nullable();
            $table->integer('neum_tras_cant')->nullable();
            $table->integer('neum_resp_cant')->nullable();
            $table->string('neum_tractivos', 50)->nullable();
            $table->integer('ejes_cant')->nullable();
            $table->string('eject_trac', 50)->nullable();
            $table->foreignId('id_tipo_combustible')->nullable()->constrained('tipos_combustibles');
            $table->foreignId('id_lubricante_motor')->nullable()->constrained('tipos_lubricantes');
            $table->foreignId('id_lubricante_cubo')->nullable()->constrained('tipos_lubricantes');
            $table->string('lub_norma', 100)->nullable();
            $table->string('lub_caja', 100)->nullable();
            $table->decimal('dist_eje_inter', 8, 2)->nullable();
            $table->decimal('dist_eje_tras', 8, 2)->nullable();
            $table->decimal('cama_largo', 8, 2)->nullable();
            $table->decimal('cama_ancho', 8, 2)->nullable();
            $table->decimal('cama_altura', 8, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ============================================================
        // Motores — movimientos
        // ============================================================
        Schema::create('motores_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_motor')->constrained('motores')->cascadeOnDelete();
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->date('fecha_movimiento');
            $table->string('tipo', 50)->comment('instalacion, retiro, reparacion');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Baterías — movimientos
        // ============================================================
        Schema::create('baterias_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_bateria')->constrained('baterias')->cascadeOnDelete();
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->date('fecha_movimiento');
            $table->string('tipo', 50);
            $table->date('fecha_retiro')->nullable();
            $table->integer('tiempo_trabajo')->nullable()->comment('días');
            $table->text('observaciones')->nullable();
            $table->foreignId('id_destino')->nullable()->constrained('destinos_agregados');
            $table->timestamps();
        });

        // ============================================================
        // Neumáticos — movimientos y roturas
        // ============================================================
        Schema::create('neumaticos_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_neumatico')->constrained('neumaticos')->cascadeOnDelete();
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->date('fecha_montaje');
            $table->date('fecha_retiro')->nullable();
            $table->decimal('km_instalado', 12, 2)->nullable();
            $table->decimal('km_retirado', 12, 2)->nullable();
            $table->string('posicion', 50)->nullable();
            $table->foreignId('id_destino')->nullable()->constrained('destinos_agregados');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('neumaticos_roturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_neumatico')->constrained('neumaticos')->cascadeOnDelete();
            $table->foreignId('id_tipo_causa')->constrained('tipos_causas');
            $table->date('fecha');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // ============================================================
        // Lubricantes
        // ============================================================
        Schema::create('consumo_lubricantes', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->unique();
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_tipo_aceite')->nullable()->constrained('tipos_lubricantes');
            $table->foreignId('id_causa')->nullable()->constrained('tipos_causas');
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad', 20)->default('litros');
            $table->decimal('importe_mn', 12, 2)->nullable();
            $table->decimal('importe_me', 12, 2)->nullable();
            $table->date('fecha');
            $table->timestamps();
        });

        // ============================================================
        // Otros Agregados
        // ============================================================
        Schema::create('otros_agregados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);
            $table->string('numero_serie', 100)->nullable();
            $table->foreignId('id_marca')->nullable()->constrained('marcas');
            $table->foreignId('id_modelo')->nullable()->constrained('modelos');
            $table->foreignId('id_pais')->nullable()->constrained('paises');
            $table->foreignId('id_estado')->nullable()->constrained('estados_componentes');
            $table->foreignId('id_lubricante')->nullable()->constrained('tipos_lubricantes');
            $table->integer('nro_cilindros')->nullable();
            $table->integer('nro_tiempos')->nullable();
            $table->decimal('caballaje', 8, 2)->nullable();
            $table->decimal('cantidad_lubricante', 8, 2)->nullable();
            $table->date('fecha_baja')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ============================================================
        // Energía (medidores y lecturas eléctricas)
        // ============================================================
        Schema::create('medidores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('ruta_folio', 100)->nullable();
            $table->string('metro', 100)->nullable();
            $table->boolean('prepago')->default(false);
            $table->string('tipo', 50)->nullable();
            $table->decimal('lectura_actual', 12, 2)->default(0);
            $table->decimal('factor', 8, 2)->nullable();
            $table->json('lecturas_mensuales')->nullable();
            $table->foreignId('id_unidad')->nullable()->constrained();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('lecturas_medidores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_medidor')->constrained('medidores')->cascadeOnDelete();
            $table->date('fecha_lectura');
            $table->decimal('lectura_inicial', 12, 2);
            $table->decimal('lectura_final', 12, 2);
            $table->decimal('consumo', 12, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('equipos_electricos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->string('tipo', 50)->nullable();
            $table->decimal('potencia', 8, 2)->nullable();
            $table->string('unidad', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos_electricos');
        Schema::dropIfExists('lecturas_medidores');
        Schema::dropIfExists('medidores');
        Schema::dropIfExists('otros_agregados');
        Schema::dropIfExists('lubricantes');
        Schema::dropIfExists('neumaticos_roturas');
        Schema::dropIfExists('neumaticos_movimientos');
        Schema::dropIfExists('baterias_movimientos');
        Schema::dropIfExists('motores_movimientos');
        Schema::dropIfExists('tipos_tractivos');
        Schema::dropIfExists('consecutivos');
        Schema::dropIfExists('destinos_agregados');
        Schema::dropIfExists('tipos_combustibles');
        Schema::dropIfExists('medidas_neumaticos');
        Schema::dropIfExists('tipos_causas');
        Schema::dropIfExists('tipos_lubricantes');
        Schema::dropIfExists('paises');
        Schema::dropIfExists('estados_componentes');
        Schema::dropIfExists('modelos');
        Schema::dropIfExists('marcas');
    }
};
