<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 1. cajas: id_tractivo nullable (102/103 cajas legacy usan idtractivos=0).
     * 2. tipos_mantenimiento: crea id=0 'SIN TIPO' (las OT legacy de 2026 usan
     *    idtipomtto=0) e id=10 'LEGACY TIPO 10' (líneas de mantenimiento huérfanas).
     */
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->foreignId('id_tractivo')->nullable()->change();
        });

        DB::statement('SET @prev_sql_mode = @@SESSION.sql_mode');
        DB::statement("SET SESSION sql_mode = CONCAT(@@SESSION.sql_mode, ',NO_AUTO_VALUE_ON_ZERO')");

        DB::table('tipos_mantenimiento')->insertOrIgnore([
            [
                'id' => 0,
                'codigo' => 'SIN-TIPO',
                'nombre' => 'SIN TIPO',
                'descripcion' => 'Órdenes de taller legacy sin tipo de mantenimiento asignado',
                'activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'codigo' => 'LEGACY-10',
                'nombre' => 'LEGACY TIPO 10 (S/DEFINIR)',
                'descripcion' => 'Tipo huérfano de líneas de mantenimiento legacy',
                'activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::statement('SET SESSION sql_mode = @prev_sql_mode');
    }

    public function down(): void
    {
        DB::table('tipos_mantenimiento')->whereIn('id', [0, 10])->delete();

        Schema::table('cajas', function (Blueprint $table) {
            $table->foreignId('id_tractivo')->nullable(false)->change();
        });
    }
};
