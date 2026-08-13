<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige la FK de aforos.id_carta_porte: apuntaba a `giros` (tabla
     * reliquia del diseño inicial, vacía y sin modelo) pero el ETL puebla
     * `cartas_porte` (id = idcartaporte legacy) y el modelo Aforo::cartaPorte()
     * relaciona con CartaPorte. El índice UNIQUE id_carta_porte existente
     * cubre la FK, por eso puede re-construirse sin índice nuevo.
     */
    public function up(): void
    {
        Schema::table('aforos', function (Blueprint $table) {
            $table->dropForeign(['id_carta_porte']);
        });

        Schema::table('aforos', function (Blueprint $table) {
            $table->foreign('id_carta_porte')->references('id')->on('cartas_porte');
        });
    }

    public function down(): void
    {
        Schema::table('aforos', function (Blueprint $table) {
            $table->dropForeign(['id_carta_porte']);
        });

        Schema::table('aforos', function (Blueprint $table) {
            $table->foreign('id_carta_porte')->references('id')->on('giros');
        });
    }
};