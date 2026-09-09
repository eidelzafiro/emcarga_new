<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Añade el número de licencia de conducción a `bolsa`.
 *
 * El ETL inicial (EtlService::migrarBolsa) mapeó el campo legacy `rh_bolsa.licencia`
 * a `bolsa.categorias_licencia`, pero en la BD real `categorias_licencia` terminó
 * guardando las CATEGORÍAS (p.ej. "B,C,D,E") y el NÚMERO de licencia se perdió.
 * Este backfill recupera el número real desde `rh_bolsa.licencia` (match por id).
 *
 * Idempotente: añade la columna si no existe y re-puebla desde el legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bolsa', 'licencia')) {
            Schema::table('bolsa', function (Blueprint $table) {
                $table->string('licencia')->nullable()->after('categorias_licencia');
            });
        }

        DB::connection('legacy')->table('rh_bolsa')
            ->select('idbolsa', 'licencia')
            ->orderBy('idbolsa')
            ->chunk(1000, function ($filas) {
                foreach ($filas as $fila) {
                    $licencia = trim((string) $fila->licencia);
                    if ($licencia === '') {
                        continue;
                    }
                    DB::table('bolsa')
                        ->where('id', (int) $fila->idbolsa)
                        ->update(['licencia' => $licencia]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('bolsa', 'licencia')) {
            Schema::table('bolsa', function (Blueprint $table) {
                $table->dropColumn('licencia');
            });
        }
    }
};
