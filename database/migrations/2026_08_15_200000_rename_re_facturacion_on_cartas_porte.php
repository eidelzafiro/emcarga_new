<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D4 (auditoría): renombra la columna de `cartas_porte` con espacio en el
 * nombre (`re Facturacion`) a un identificador válido (`refacturacion`).
 * La columna no se usa en el código; el renombrado evita tener que usar
 * backticks en toda consulta.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cartas_porte', 're Facturacion') && ! Schema::hasColumn('cartas_porte', 'refacturacion')) {
            Schema::table('cartas_porte', function (Blueprint $table) {
                $table->renameColumn('re Facturacion', 'refacturacion');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cartas_porte', 'refacturacion') && ! Schema::hasColumn('cartas_porte', 're Facturacion')) {
            Schema::table('cartas_porte', function (Blueprint $table) {
                $table->renameColumn('refacturacion', 're Facturacion');
            });
        }
    }
};
