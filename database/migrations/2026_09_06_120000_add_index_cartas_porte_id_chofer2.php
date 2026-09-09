<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice en cartas_porte.id_chofer2.
 *
 * La migración 2026_08_10_110000 indexó id_chofer pero omitió id_chofer2.
 * Las consultas de prenómina de choferes filtran por (id_chofer = ? OR
 * id_chofer2 = ?); sin índice en id_chofer2 el OR degradaba a full scan
 * (pasaba de ~18s a ~3s por reporte tras añadirlo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->index('id_chofer2', 'cartas_porte_id_chofer2_index');
        });
    }

    public function down(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->dropIndex('cartas_porte_id_chofer2_index');
        });
    }
};
