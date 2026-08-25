<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columna `logo` en catalogo_items para guardar la ruta relativa (disco
 * público) del logotipo de la marca. Se puebla con los logos reales
 * obtenidos de Wikimedia Commons. Al igual que `id_pais`, vive como
 * columna real (no en el JSON `extra`) para consulta directa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogo_items', function (Blueprint $table) {
            $table->string('logo', 512)->nullable()->after('id_pais');
        });
    }

    public function down(): void
    {
        Schema::table('catalogo_items', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
