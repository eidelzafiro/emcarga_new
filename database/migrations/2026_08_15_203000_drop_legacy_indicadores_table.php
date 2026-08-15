<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * D1 (auditoría) — retiro del módulo legacy de indicadores.
 *
 * Las filas de indicadores (1-7) ahora viven en `aforo_indicadores`, migradas
 * desde `aforos` (filas 1-2) y `indicadores` (filas 3-7). La tabla `indicadores`
 * queda redundante y se elimina, junto con su CRUD suelto (controller/ruta) y el
 * ítem de menú que apuntaba a una página sin vista.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('indicadores');
    }

    public function down(): void
    {
        // La tabla no se reconstruye (datos ahora en aforo_indicadores).
    }
};
