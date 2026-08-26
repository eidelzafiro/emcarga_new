<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tablas = ['otros_agregados', 'tarjetero', 'tipo_vehiculos'];

        DB::transaction(function () use ($tablas) {
            // 1) Fusionar duplicados por nombre (mantener el referenciado, si no el de menor id)
            $dupes = DB::table('catalogo_items')
                ->where('tipo', 'modelos')
                ->select('nombre', DB::raw('COUNT(*) AS c'), DB::raw('GROUP_CONCAT(id ORDER BY id) AS ids'))
                ->groupBy('nombre')
                ->having('c', '>', 1)
                ->get();

            foreach ($dupes as $d) {
                $ids = array_map('intval', explode(',', $d->ids));
                $referenciado = null;
                foreach ($tablas as $t) {
                    $encontrado = DB::table($t)->whereIn('id_modelo', $ids)->value('id_modelo');
                    if ($encontrado) { $referenciado = (int) $encontrado; break; }
                }
                $survivor = $referenciado ?: min($ids);
                $otros = array_values(array_filter($ids, fn ($x) => $x !== $survivor));

                foreach ($tablas as $t) {
                    DB::table($t)->whereIn('id_modelo', $otros)->update(['id_modelo' => $survivor]);
                }
                DB::table('catalogo_items')->whereIn('id', $otros)->delete();
            }

            // 2) Eliminar los modelos que no están en uso (sin referencia en ninguna tabla)
            $usados = collect();
            foreach ($tablas as $t) {
                $usados = $usados->merge(
                    DB::table($t)->whereNotNull('id_modelo')->distinct()->pluck('id_modelo')
                );
            }
            $usados = $usados->unique()->all();

            DB::table('catalogo_items')
                ->where('tipo', 'modelos')
                ->whereNotIn('id', $usados)
                ->delete();
        });
    }

    public function down(): void
    {
        // Los datos eliminados no son recreables de forma automática;
        // restaurar desde salva si es necesario.
    }
};
