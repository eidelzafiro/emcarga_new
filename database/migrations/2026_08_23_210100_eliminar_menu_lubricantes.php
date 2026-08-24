<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retira el ítem de menú "Lubricantes" (lubricantes.index) de Flota: en su
 * lugar se usa el Control de Lubricantes (control-lubricante.index).
 * Autorizado por EIDEL el 2026-08-23. Reordena los ítems siguientes de Flota.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_items')->where('id', 9)->delete();

        // Compactar el orden bajo Flota (parent 2) tras la baja.
        $orden = 1;
        foreach (DB::table('menu_items')->where('parent_id', 2)->orderBy('orden')->get(['id']) as $item) {
            DB::table('menu_items')->where('id', $item->id)->update(['orden' => $orden]);
            $orden++;
        }
    }

    public function down(): void
    {
        DB::table('menu_items')->insert([
            'id' => 9,
            'parent_id' => 2,
            'label' => 'Lubricantes',
            'icon' => 'pi pi-eye-dropper',
            'route' => 'lubricantes.index',
            'permission' => 'lubricantes.ver',
            'orden' => 11,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
