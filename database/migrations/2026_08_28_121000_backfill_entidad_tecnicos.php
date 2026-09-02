<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La migración que creó motores/cajas/diferenciales por tractivo no propagó
     * `id_entidad`, dejándolos en NULL. El scoping por entidad de los
     * controladores los ocultaba (solo se veían los de la entidad activa).
     * Se volca `id_entidad` desde el tractivo relacionado.
     */
    public function up(): void
    {
        foreach (['motores', 'cajas', 'diferenciales'] as $tabla) {
            DB::statement("
                UPDATE {$tabla} t
                JOIN tractivos tr ON tr.id = t.id_tractivo
                SET t.id_entidad = tr.id_entidad
                WHERE t.id_entidad IS NULL
                  AND tr.id_entidad IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        // No reversible: no se conoce la entidad original de los registros NULL.
    }
};
