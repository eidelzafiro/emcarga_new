<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Limpia clientes.idorganismos con valores que no corresponden a un ítem de
 * catálogo de tipo 'organismos' (96 filas: 32 ids huérfanos que no existen en
 * rh_organismos legacy + 64 que apuntan a ítems de otro tipo). Se anulan para
 * que la relación quede íntegramente contra catalogo_items.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE clientes
            SET idorganismos = NULL
            WHERE idorganismos IS NOT NULL
              AND idorganismos NOT IN (
                  SELECT id FROM catalogo_items WHERE tipo = 'organismos'
              )
        ");
    }

    public function down(): void
    {
        // Limpieza de datos inválidos; no se revierte.
    }
};
