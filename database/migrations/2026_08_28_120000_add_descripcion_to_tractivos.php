<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade la columna `descripcion` a `tractivos` (existía en el legacy y la
     * esperan las vistas de Control de Lubricantes, Pizarra y Consumo de
     * Lubricantes). Se volca inicialmente desde `placa` para no dejar nulos.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tractivos', 'descripcion')) {
            Schema::table('tractivos', function (Blueprint $table) {
                $table->string('descripcion', 191)->nullable()->after('placa');
            });
        }

        DB::statement("UPDATE tractivos SET descripcion = placa WHERE descripcion IS NULL OR descripcion = ''");
    }

    public function down(): void
    {
        if (Schema::hasColumn('tractivos', 'descripcion')) {
            Schema::table('tractivos', function (Blueprint $table) {
                $table->dropColumn('descripcion');
            });
        }
    }
};
