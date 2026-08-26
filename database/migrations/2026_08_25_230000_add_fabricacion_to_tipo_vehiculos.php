<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_vehiculos', function (Blueprint $table) {
            $table->integer('fabricacion')->nullable()->after('clase');
        });

        // Backfill desde las subtablas de origen vía las FKs de tipo_vehiculos.
        DB::statement("
            UPDATE tipo_vehiculos tv
            JOIN tipos_tractivos tt ON tt.id = tv.id_tipo_tractivo
            SET tv.fabricacion = tt.fabricacion
            WHERE tv.id_tipo_tractivo IS NOT NULL
        ");

        DB::statement("
            UPDATE tipo_vehiculos tv
            JOIN tipos_arrastres ta ON ta.id = tv.id_tipo_arrastre
            SET tv.fabricacion = ta.fabricacion
            WHERE tv.id_tipo_arrastre IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('tipo_vehiculos', function (Blueprint $table) {
            $table->dropColumn('fabricacion');
        });
    }
};
