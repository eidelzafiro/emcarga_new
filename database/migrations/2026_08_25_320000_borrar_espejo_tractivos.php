<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina del espejo tractivos los 646 arrastres (id_grupo=ARRASTRES / id en arrastres),
     * dejando arrastres como tabla única y canónica. Con guarda: si alguna tabla sigue
     * referenciando esos ids vía id_tractivo/tractivo_id, aborta.
     */
    public function up(): void
    {
        if (!Schema::hasTable('arrastres') || !Schema::hasTable('tractivos')) {
            return;
        }
        // Polimórficas que pudieran tener vehiculo_type='tractivo' y vehiculo_id en arrastres
        foreach (['vehiculos_amortizacion', 'vehiculos_documentacion', 'vehiculos_planes'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'vehiculo_id') && Schema::hasColumn($t, 'vehiculo_type')) {
                DB::table($t)
                    ->where('vehiculo_type', 'tractivo')
                    ->whereIn('vehiculo_id', function ($q) { $q->select('id')->from('arrastres'); })
                    ->update(['vehiculo_type' => 'arrastre']);
            }
        }

        // Guarda: cualquier id_tractivo/tractivo_id apuntando a un id de arrastres
        $tablas = DB::select("
            SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME='tractivos'
        ");
        $huerfanos = 0;
        foreach ($tablas as $fk) {
            $t = $fk->TABLE_NAME;
            $c = $fk->COLUMN_NAME;
            $n = DB::table($t)->whereIn($c, function ($q) { $q->select('id')->from('arrastres'); })->count();
            $huerfanos += $n;
        }
        if ($huerfanos > 0) {
            throw new \RuntimeException("Hay {$huerfanos} referencias colgando a arrastres en tractivos; aborto borrado del espejo.");
        }

        $borradas = DB::table('tractivos')->whereIn('id', function ($q) { $q->select('id')->from('arrastres'); })->delete();
        DB::statement("UPDATE arrastres SET deleted_at = NULL WHERE deleted_at IS NOT NULL");
        echo "Espejo tractivos: {$borradas} arrastres eliminados.\n";
    }

    public function down(): void
    {
        // Restaurar desde salva; no es reversible automáticamente.
    }
};
