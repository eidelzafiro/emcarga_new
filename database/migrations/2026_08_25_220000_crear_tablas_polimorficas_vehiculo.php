<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase C: extrae las columnas de amortización, planes y documentación
     * de la tabla tractivos hacia tablas polimórficas independientes
     * (vehiculos_amortizacion, vehiculos_planes, vehiculos_documentacion)
     * para que, tras el split físico de arrastres (Fase D), tanto tractores
     * como arrastres compartan estas fichas vía (vehiculo_type, vehiculo_id).
     */
    public function up(): void
    {
        Schema::create('vehiculos_amortizacion', function (Blueprint $table) {
            $table->id();
            $table->string('vehiculo_type', 60);
            $table->unsignedBigInteger('vehiculo_id');
            $table->decimal('amortmn', 12, 2)->nullable();
            $table->decimal('amortme', 12, 2)->nullable();
            $table->decimal('vchapa', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['vehiculo_type', 'vehiculo_id']);
        });

        Schema::create('vehiculos_planes', function (Blueprint $table) {
            $table->id();
            $table->string('vehiculo_type', 60);
            $table->unsignedBigInteger('vehiculo_id');
            $table->decimal('plan_comb', 12, 2)->nullable();
            $table->decimal('plan_tn', 12, 2)->nullable();
            $table->decimal('plan_viajes', 12, 2)->nullable();
            $table->decimal('plan_gastos', 12, 2)->nullable();
            $table->decimal('plan_cdt', 12, 2)->nullable();
            $table->decimal('plan_diario', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['vehiculo_type', 'vehiculo_id']);
        });

        Schema::create('vehiculos_documentacion', function (Blueprint $table) {
            $table->id();
            $table->string('vehiculo_type', 60);
            $table->unsignedBigInteger('vehiculo_id');
            $table->string('ficav', 50)->nullable();
            $table->date('femision_ficav')->nullable();
            $table->date('fvence_ficav')->nullable();
            $table->string('lot', 50)->nullable();
            $table->date('femision_lot')->nullable();
            $table->date('fvence_lot')->nullable();
            $table->string('circulacion', 50)->nullable();
            $table->date('femision_circ')->nullable();
            $table->date('fvence_circ')->nullable();
            $table->date('f_reconstruccion')->nullable();
            $table->timestamps();

            $table->index(['vehiculo_type', 'vehiculo_id']);
        });

        $ahora = now();

        // Poblar desde tractivos (solo existen tractores en esta etapa).
        DB::insert(
            "INSERT INTO vehiculos_amortizacion (vehiculo_type, vehiculo_id, amortmn, amortme, vchapa, created_at, updated_at)
             SELECT 'tractivo', id, amortmn, amortme, vchapa, ?, ? FROM tractivos",
            [$ahora, $ahora]
        );

        DB::insert(
            "INSERT INTO vehiculos_planes (vehiculo_type, vehiculo_id, plan_comb, plan_tn, plan_viajes, plan_gastos, plan_cdt, plan_diario, created_at, updated_at)
             SELECT 'tractivo', id, plan_comb, plan_tn, plan_viajes, plan_gastos, plan_cdt, plan_diario, ?, ? FROM tractivos",
            [$ahora, $ahora]
        );

        DB::insert(
            "INSERT INTO vehiculos_documentacion (vehiculo_type, vehiculo_id, ficav, femision_ficav, fvence_ficav, lot, femision_lot, fvence_lot, circulacion, femision_circ, fvence_circ, f_reconstruccion, created_at, updated_at)
             SELECT 'tractivo', id, ficav, femision_ficav, fvence_ficav, lot, femision_lot, fvence_lot, circulacion, femision_circ, fvence_circ, f_reconstruccion, ?, ? FROM tractivos",
            [$ahora, $ahora]
        );

        // Soltar las columnas de tractivos (ahora viven en las tablas polimórficas).
        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropColumn([
                'amortmn', 'amortme', 'vchapa',
                'plan_comb', 'plan_tn', 'plan_viajes', 'plan_gastos', 'plan_cdt', 'plan_diario',
                'ficav', 'femision_ficav', 'fvence_ficav',
                'lot', 'femision_lot', 'fvence_lot',
                'circulacion', 'femision_circ', 'fvence_circ',
                'f_reconstruccion',
            ]);
        });
    }

    public function down(): void
    {
        // Restaurar columnas en tractivos y recuperar los datos de las tablas polimórficas.
        Schema::table('tractivos', function (Blueprint $table) {
            $table->decimal('amortmn', 12, 2)->nullable();
            $table->decimal('amortme', 12, 2)->nullable();
            $table->decimal('vchapa', 12, 2)->nullable();
            $table->decimal('plan_comb', 12, 2)->nullable();
            $table->decimal('plan_tn', 12, 2)->nullable();
            $table->decimal('plan_viajes', 12, 2)->nullable();
            $table->decimal('plan_gastos', 12, 2)->nullable();
            $table->decimal('plan_cdt', 12, 2)->nullable();
            $table->decimal('plan_diario', 12, 2)->nullable();
            $table->string('ficav', 50)->nullable();
            $table->date('femision_ficav')->nullable();
            $table->date('fvence_ficav')->nullable();
            $table->string('lot', 50)->nullable();
            $table->date('femision_lot')->nullable();
            $table->date('fvence_lot')->nullable();
            $table->string('circulacion', 50)->nullable();
            $table->date('femision_circ')->nullable();
            $table->date('fvence_circ')->nullable();
            $table->date('f_reconstruccion')->nullable();
        });

        DB::update(
            "UPDATE tractivos t
             JOIN vehiculos_amortizacion a ON a.vehiculo_type = 'tractivo' AND a.vehiculo_id = t.id
             SET t.amortmn = a.amortmn, t.amortme = a.amortme, t.vchapa = a.vchapa"
        );
        DB::update(
            "UPDATE tractivos t
             JOIN vehiculos_planes p ON p.vehiculo_type = 'tractivo' AND p.vehiculo_id = t.id
             SET t.plan_comb = p.plan_comb, t.plan_tn = p.plan_tn, t.plan_viajes = p.plan_viajes,
                 t.plan_gastos = p.plan_gastos, t.plan_cdt = p.plan_cdt, t.plan_diario = p.plan_diario"
        );
        DB::update(
            "UPDATE tractivos t
             JOIN vehiculos_documentacion d ON d.vehiculo_type = 'tractivo' AND d.vehiculo_id = t.id
             SET t.ficav = d.ficav, t.femision_ficav = d.femision_ficav, t.fvence_ficav = d.fvence_ficav,
                 t.lot = d.lot, t.femision_lot = d.femision_lot, t.fvence_lot = d.fvence_lot,
                 t.circulacion = d.circulacion, t.femision_circ = d.femision_circ, t.fvence_circ = d.fvence_circ,
                 t.f_reconstruccion = d.f_reconstruccion"
        );

        Schema::dropIfExists('vehiculos_documentacion');
        Schema::dropIfExists('vehiculos_planes');
        Schema::dropIfExists('vehiculos_amortizacion');
    }
};
