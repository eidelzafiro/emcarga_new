<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D2 (auditoría): índice UNIQUE en `tarifas` sobre (id_tipo_carga, kms, version).
 *
 * - Elimina primero los duplicados (conservando el de mayor created_at, es decir
 *   el del ETL más reciente, y de menor id).
 * - Crea el índice único para garantizar idempotencia fuerte del ETL y acelerar
 *   el upsert (migrarTarifas tarda minutos sin él).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Borra los duplicados conservando, por triplete, la fila de mayor
        // created_at (ETL más reciente) y de menor id en caso de empate.
        DB::statement(<<<'SQL'
            DELETE t1 FROM tarifas t1
            JOIN tarifas t2
              ON t1.id_tipo_carga = t2.id_tipo_carga
             AND t1.kms = t2.kms
             AND t1.version = t2.version
             AND (
                  t1.created_at < t2.created_at
                  OR (t1.created_at = t2.created_at AND t1.id > t2.id)
             )
        SQL);

        if (! collect(Schema::getIndexes('tarifas'))->contains(fn ($i) => $i['name'] === 'tarifas_tipo_kms_version_unique')) {
            Schema::table('tarifas', function (Blueprint $table) {
                $table->unique(['id_tipo_carga', 'kms', 'version'], 'tarifas_tipo_kms_version_unique');
            });
        }
    }

    public function down(): void
    {
        if (collect(Schema::getIndexes('tarifas'))->contains(fn ($i) => $i['name'] === 'tarifas_tipo_kms_version_unique')) {
            Schema::table('tarifas', function (Blueprint $table) {
                $table->dropUnique('tarifas_tipo_kms_version_unique');
            });
        }
    }
};
