<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina columnas muertas de `bolsa`:
 * - `codigo`: 1 fila con valor, sin uso en la app (el versat es el identificador).
 * - `fecha_nacimiento`: 0 filas con valor; la edad siempre se deriva del CI
 *   cubano (YYMMDD) como el legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bolsa', function (Blueprint $table) {
            if (Schema::hasColumn('bolsa', 'codigo')) {
                $table->dropColumn('codigo');
            }
            if (Schema::hasColumn('bolsa', 'fecha_nacimiento')) {
                $table->dropColumn('fecha_nacimiento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bolsa', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable();
            $table->date('fecha_nacimiento')->nullable();
        });
    }
};
