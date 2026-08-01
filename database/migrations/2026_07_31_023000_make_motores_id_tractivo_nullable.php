<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los motores legacy (tec_motores) tienen idtractivos=0 (sin tractivo
     * asignado). Se hace nullable para poder migrarlos sin bloqueo de FK.
     */
    public function up(): void
    {
        Schema::table('motores', function (Blueprint $table) {
            $table->foreignId('id_tractivo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('motores', function (Blueprint $table) {
            $table->foreignId('id_tractivo')->nullable(false)->change();
        });
    }
};
