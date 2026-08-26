<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fase D (identificación de arrastres): las fichas de vehículo
 * (vehiculos_amortizacion, vehiculos_planes, vehiculos_documentacion) se
 * poblaron con vehiculo_type='tractivo' para todos los tractivos, incluidos
 * los arrastres. Se corrige marcando 'arrastre' para las 646 filas cuyo
 * tractivo pertenece al grupo ARRASTRES (catalogo_items origen_id=8).
 *
 * Es idempotente: tras la primera corrida no quedan filas type='tractivo'
 * para esos ids, por lo que es seguro re-ejecutar.
 */
return new class extends Migration
{
    public function up(): void
    {
        $grupoArrastres = DB::table('catalogo_items')
            ->where('tipo', 'grupos')
            ->where('origen_id', 8)
            ->pluck('id');

        $ids = DB::table('tractivos')
            ->whereIn('id_grupo', $grupoArrastres)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        foreach (['vehiculos_amortizacion', 'vehiculos_planes', 'vehiculos_documentacion'] as $tabla) {
            DB::table($tabla)
                ->where('vehiculo_type', 'tractivo')
                ->whereIn('vehiculo_id', $ids)
                ->update(['vehiculo_type' => 'arrastre']);
        }
    }

    public function down(): void
    {
        $grupoArrastres = DB::table('catalogo_items')
            ->where('tipo', 'grupos')
            ->where('origen_id', 8)
            ->pluck('id');

        $ids = DB::table('tractivos')
            ->whereIn('id_grupo', $grupoArrastres)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        foreach (['vehiculos_amortizacion', 'vehiculos_planes', 'vehiculos_documentacion'] as $tabla) {
            DB::table($tabla)
                ->where('vehiculo_type', 'arrastre')
                ->whereIn('vehiculo_id', $ids)
                ->update(['vehiculo_type' => 'tractivo']);
        }
    }
};
