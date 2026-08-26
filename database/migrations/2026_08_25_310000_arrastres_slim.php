<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recorta la tabla arrastres al subconjunto de campos definido por el usuario:
     * codigo, placa (chapa), id_tipo_vehiculo, indice_aceite, tara, id_color_primario,
     * id_color_secundario, estado, fecha_alta, fecha_baja (fbaja), id_entidad.
     * (fecha_reconstruccion no existe en la tabla; se omite).
     */
    public function up(): void
    {
        if (!Schema::hasTable('arrastres')) {
            return;
        }
        $mantener = [
            'id','codigo','placa','id_tipo_vehiculo','indice_aceite','tara',
            'id_color_primario','id_color_secundario','estado','fecha_alta','fecha_baja',
            'id_entidad','created_at','updated_at','deleted_at',
        ];
        $actuales = DB::select("SELECT COLUMN_NAME c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='arrastres'");
        $aBorrar = [];
        foreach ($actuales as $col) {
            if (!in_array($col->c, $mantener)) {
                $aBorrar[] = $col->c;
            }
        }
        if (empty($aBorrar)) {
            return;
        }
        $sql = "ALTER TABLE arrastres " . implode(', ', array_map(fn ($c) => "DROP COLUMN `$c`", $aBorrar));
        DB::statement($sql);
    }

    public function down(): void
    {
        // No es trivial restaurar columnas sin el esquema original; se restaura desde salva.
    }
};
