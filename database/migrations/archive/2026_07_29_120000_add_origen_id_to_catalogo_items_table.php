<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad del origen de cada ítem del catálogo unificado:
 * la tabla tipos_* de la que fue migrado. Permite re-sincronizar
 * de forma idempotente (updateOrCreate por tipo + origen_id) y
 * auditar la procedencia durante la convivencia de ambos sistemas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogo_items', function (Blueprint $table) {
            $table->unsignedBigInteger('origen_id')->nullable()->after('tipo');
            $table->unique(['tipo', 'origen_id'], 'catalogo_items_tipo_origen_unique');
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_items', function (Blueprint $table) {
            $table->dropUnique('catalogo_items_tipo_origen_unique');
            $table->dropColumn('origen_id');
        });
    }
};
