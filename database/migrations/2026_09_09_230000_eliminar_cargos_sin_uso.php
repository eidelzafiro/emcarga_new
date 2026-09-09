<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina los cargos sin uso: ningún empleado (bolsa) ni plantilla los
 * referencia. Decisión del usuario (2026-09-09): "Hacer una revisión de los
 * cargos en el legacy que no están en uso por ningún empleado y no migrarlos".
 *
 * La lista completa de 434 cargos eliminados queda documentada en
 * docs/CARGOS_SIN_USO_ELIMINADOS.md (regenerable ejecutando la misma query
 * de diagnóstico antes de la migración).
 */
return new class extends Migration
{
    public function up(): void
    {
        $sinUso = DB::table('cargos as c')
            ->leftJoin('bolsa as b', 'b.id_cargo', '=', 'c.id')
            ->leftJoin('plantilla as p', 'p.id_cargo', '=', 'c.id')
            ->whereNull('b.id')
            ->whereNull('p.id')
            ->where('c.activo', true)
            ->where('c.id', '!=', 1000000) // cargo default SIN ASIGNAR
            ->pluck('c.id');

        if ($sinUso->isNotEmpty()) {
            // Soft-delete (los historiales viejos no referencian cargos, pero
            // el soft-delete es más seguro que el borrado físico).
            DB::table('cargos')->whereIn('id', $sinUso->all())
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now(), 'activo' => false]);
        }
    }

    public function down(): void
    {
        // Reactiva los cargos desactivados que sigan sin referencias (los
        // movimientos nuevos podrían haberlos re-vinculado).
        $reactivables = DB::table('cargos as c')
            ->leftJoin('bolsa as b', 'b.id_cargo', '=', 'c.id')
            ->leftJoin('plantilla as p', 'p.id_cargo', '=', 'c.id')
            ->whereNotNull('c.deleted_at')
            ->whereNull('b.id')
            ->whereNull('p.id')
            ->pluck('c.id');

        if ($reactivables->isNotEmpty()) {
            DB::table('cargos')->whereIn('id', $reactivables->all())
                ->update(['deleted_at' => null, 'activo' => true]);
        }
    }
};
