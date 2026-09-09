<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pobla campos de nómina faltantes desde el legacy:
 *  - bolsa.versat (NRO EXP en prenómina), bolsa.garantia, bolsa.falta
 *  - areas.id_tipo_sistema_pago (1=regulación, 2=choferes transporte, 3=paquetería)
 *  - areas.orden (orden legacy para agrupar áreas)
 *
 * Estos campos no migraron en el ETL original (config/etl.php) y son necesarios
 * para los reportes de prenómina (transporte y administrativo).
 *
 * Idempotente: actualiza por id legacy (identidades/idareas).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bolsa', 'versat')) {
            Schema::table('bolsa', function ($table) {
                $table->string('versat', 8)->nullable()->after('id_entidad');
            });
        }
        if (!Schema::hasColumn('bolsa', 'garantia')) {
            Schema::table('bolsa', function ($table) {
                $table->decimal('garantia', 5, 2)->nullable()->after('versat');
            });
        }
        if (!Schema::hasColumn('bolsa', 'falta')) {
            Schema::table('bolsa', function ($table) {
                $table->date('falta')->nullable()->after('garantia');
            });
        }
        if (!Schema::hasColumn('areas', 'id_tipo_sistema_pago')) {
            Schema::table('areas', function ($table) {
                $table->integer('id_tipo_sistema_pago')->nullable()->after('id_area_padre');
            });
        }

        // Poblar bolsa desde rh_bolsa
        DB::connection('legacy')->table('rh_bolsa')
            ->orderBy('idbolsa')
            ->chunk(1000, function ($filas) {
                foreach ($filas as $fila) {
                    DB::table('bolsa')
                        ->where('id', (int) $fila->idbolsa)
                        ->update([
                            'versat' => $fila->versat ?: null,
                            'garantia' => $fila->garantia !== null ? (float) $fila->garantia : null,
                            'falta' => ($fila->falta && ! str_starts_with((string) $fila->falta, '0000-00-00')) ? $fila->falta : null,
                        ]);
                }
            });

        // Poblar areas desde rh_areas
        DB::connection('legacy')->table('rh_areas')
            ->orderBy('idareas')
            ->chunk(1000, function ($filas) {
                foreach ($filas as $fila) {
                    DB::table('areas')
                        ->where('codigo', (string) $fila->idareas)
                        ->update([
                            'orden' => (int) $fila->order,
                            'id_tipo_sistema_pago' => (int) $fila->idtiposistemapago ?: null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No se revierte — los datos correctos son los del legacy.
    }
};
