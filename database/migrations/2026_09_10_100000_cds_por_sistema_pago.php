<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CDS por tipo de sistema de pago (2026-09-10, decisión del usuario):
 *
 * 1. Remapea areas.id_tipo_sistema_pago de ids legacy (1/2/3) a
 *    catalogo_items.id del tipo 'tipos_sistemas_pago' (1562=REGULACION Y
 *    CONTROL, 1563=CHOFERES TRANSPORTACION, 1564=CHOFERES PAQUETERÍA).
 *    La migración 2026_09_06_110000 guardó el id legacy crudo, que además
 *    coincidía con ids de 'tipo_ingresos' (FLETE TRANSPORTACION...) y hacía
 *    que los joins con catalogo resolvieran al ítem equivocado.
 * 2. Amplía cds_entidades con id_tipo_sistema_pago (el CDS ahora se guarda
 *    por entidad+mes+año+sistema de pago, no un solo valor global) y lo
 *    pobla copiando el valor existente a los sistemas de pago usados por
 *    las áreas de cada entidad (1=REGULACION por compatibilidad del
 *    reporte administrativo).
 * 3. Índice único por (entidad, mes, año, sistema).
 *
 * Idempotente: no hace nada si el remapeo ya se aplicó.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Remapeo de areas.id_tipo_sistema_pago → catalogo_items.id.
        $mapa = DB::table('catalogo_items')
            ->where('tipo', 'tipos_sistemas_pago')
            ->pluck('id', 'origen_id'); // [1 => 1562, 2 => 1563, 3 => 1564]

        if ($mapa->isNotEmpty()) {
            foreach ($mapa as $origen => $idCatalogo) {
                DB::table('areas')
                    ->where('id_tipo_sistema_pago', $origen)
                    ->update(['id_tipo_sistema_pago' => $idCatalogo]);
            }
        }

        // 2. cds_entidades + id_tipo_sistema_pago.
        if (! Schema::hasColumn('cds_entidades', 'id_tipo_sistema_pago')) {
            Schema::table('cds_entidades', function ($table) {
                $table->unsignedInteger('id_tipo_sistema_pago')->nullable()->after('ano');
                $table->foreign('id_tipo_sistema_pago')
                    ->references('id')->on('catalogo_items')->nullOnDelete();
            });
        }

        // Poblar: los CDS ya guardados se replican al sistema de pago vigente
        // (REGULACION Y CONTROL, origen 1) que es el que usa el reporte
        // "DATOS P/NOMINAS PAGO ADMINISTRATIVO". NULL queda como comodín
        // global (retro-compatibile con cdsDe sin sistema).
        $sistemaRegulacion = $mapa->get(1);
        if ($sistemaRegulacion) {
            DB::table('cds_entidades')
                ->whereNull('id_tipo_sistema_pago')
                ->update(['id_tipo_sistema_pago' => $sistemaRegulacion]);
        }

        // 3. Índice único nuevo (entidad+mes+año+sistema). Para soltar el
        // viejo hay que quitar antes la FK que lo usa (MariaDB no permite
        // drop index usado por FK) y re-crearla después.
        $indices = fn () => collect(DB::select('SHOW INDEX FROM cds_entidades'))->pluck('Key_name');

        if (! $indices()->contains('uq_cds_entidad_mes_ano_sp')) {
            Schema::table('cds_entidades', function ($table) {
                $table->unique(['id_entidad', 'mes', 'ano', 'id_tipo_sistema_pago'], 'uq_cds_entidad_mes_ano_sp');
            });
        }
        if ($indices()->contains('uq_cds_entidad_mes_ano')) {
            Schema::table('cds_entidades', function ($table) {
                $table->dropForeign('cds_entidades_id_entidad_foreign');
                $table->dropUnique('uq_cds_entidad_mes_ano');
                $table->foreign('id_entidad')
                    ->references('id')->on('entidades')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Revierte el remapeo de areas (ids legacy) y quita la columna SP.
        $mapa = DB::table('catalogo_items')
            ->where('tipo', 'tipos_sistemas_pago')
            ->pluck('origen_id', 'id');

        if ($mapa->isNotEmpty()) {
            foreach ($mapa as $idCatalogo => $origen) {
                DB::table('areas')
                    ->where('id_tipo_sistema_pago', $idCatalogo)
                    ->update(['id_tipo_sistema_pago' => $origen]);
            }
        }

        $indices = collect(DB::select('SHOW INDEX FROM cds_entidades'))->pluck('Key_name');

        if ($indices->contains('uq_cds_entidad_mes_ano_sp')) {
            Schema::table('cds_entidades', function ($table) {
                $table->dropUnique('uq_cds_entidad_mes_ano_sp');
            });
        }
        if (! $indices->contains('uq_cds_entidad_mes_ano')) {
            Schema::table('cds_entidades', function ($table) {
                $table->foreign('id_entidad')
                    ->references('id')->on('entidades')->nullOnDelete();
                $table->unique(['id_entidad', 'mes', 'ano'], 'uq_cds_entidad_mes_ano');
            });
        }

        if (Schema::hasColumn('cds_entidades', 'id_tipo_sistema_pago')) {
            Schema::table('cds_entidades', function ($table) {
                $table->dropForeign(['id_tipo_sistema_pago']);
                $table->dropColumn('id_tipo_sistema_pago');
            });
        }
    }
};
