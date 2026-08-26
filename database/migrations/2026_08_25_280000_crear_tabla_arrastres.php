<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fase D (split físico): crea la tabla `arrastres` como copia estructural de
 * `tractivos` y replica los 646 arrastres (grupo ARRASTRES, catalogo_items
 * origen_id=8) preservando su id original, de modo que las FKs existentes en
 * otras tablas (que apuntan a ese id) siguen siendo válidas y la app no se
 * rompe. El borrado de esos registros de `tractivos` y el repunteo de las ~31
 * FKs es una fase posterior y más destructiva.
 *
 * Idempotente: CREATE TABLE IF NOT EXISTS y INSERT con NOT IN.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('arrastres')) {
            DB::statement('CREATE TABLE arrastres LIKE tractivos');
        }

        $grupoArrastres = DB::table('catalogo_items')
            ->where('tipo', 'grupos')
            ->where('origen_id', 8)
            ->pluck('id');

        if ($grupoArrastres->isEmpty()) {
            return;
        }

        // Subquery derivado (_a) para evitar la restricción de MySQL de
        // referenciar la tabla destino (arrastres) dentro del INSERT.
        $ids = $grupoArrastres->implode(',');
        DB::statement("
            INSERT INTO arrastres
            SELECT t.* FROM tractivos t
            WHERE t.id_grupo IN ({$ids})
              AND t.deleted_at IS NULL
              AND t.id NOT IN (SELECT id FROM (SELECT id FROM arrastres) AS _a)
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS arrastres');
    }
};
