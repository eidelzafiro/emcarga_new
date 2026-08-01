<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El catálogo de modelos no necesita id_marca: la marca se relaciona con el
 * modelo solo a nivel de tractivos/tipos de tractivos y arrastres.
 * Se elimina la columna y su FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modelos', function (Blueprint $table) {
            $table->dropForeign(['id_marca']);
            $table->dropColumn('id_marca');
        });
    }

    public function down(): void
    {
        Schema::table('modelos', function (Blueprint $table) {
            $table->foreignId('id_marca')->nullable()->after('nombre')->constrained('marcas');
        });
    }
};
