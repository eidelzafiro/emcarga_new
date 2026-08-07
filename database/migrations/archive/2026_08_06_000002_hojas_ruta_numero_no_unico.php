<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El `numero` (nrohr) legacy NO es único: hay duplicados históricos.
 * Se quita el índice UNIQUE y se conserva el índice simple. La unicidad
 * del folio se valida a nivel de aplicación (año/entidad) en el servicio.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE hojas_ruta DROP INDEX hojas_ruta_numero_unique');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE hojas_ruta ADD UNIQUE INDEX hojas_ruta_numero_unique (numero)');
    }
};
