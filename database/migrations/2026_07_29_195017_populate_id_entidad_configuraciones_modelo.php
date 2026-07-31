<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE configuraciones_modelo cm
            JOIN users u ON u.id = cm.id_user
            SET cm.id_entidad = u.id_entidad
            WHERE cm.id_entidad IS NULL AND u.id_entidad IS NOT NULL
        ");
    }

    public function down(): void
    {
        // irreversible
    }
};
