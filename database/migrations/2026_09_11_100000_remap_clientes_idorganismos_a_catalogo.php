<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remapea clientes.idorganismos del id legacy (rh_organismos.idorganismos)
 * al id de catalogo_items (tipo = 'organismos', origen_id = id legacy).
 *
 * La unificación de codificadores (2026_08_23_190000) repuntó ~36 columnas de
 * negocio a catalogo_items.id pero omitió clientes.idorganismos, dejando el
 * id legacy. Por eso el reporte "Resumen Mensual Facturación por Organismos"
 * agrupaba contra filas ajenas (tipos_operaciones, tipos_cargas, ...).
 *
 * Idempotente: tras el remapeo los valores quedan como ids de catalogo (>=3000)
 * que ya no coinciden con los origen_id legacy (100-999).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE clientes c
            JOIN catalogo_items ci
              ON ci.tipo = 'organismos' AND ci.origen_id = c.idorganismos
            SET c.idorganismos = ci.id
            WHERE c.idorganismos IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE clientes c
            JOIN catalogo_items ci
              ON ci.id = c.idorganismos AND ci.tipo = 'organismos'
            SET c.idorganismos = ci.origen_id
            WHERE c.idorganismos IS NOT NULL
        ");
    }
};
