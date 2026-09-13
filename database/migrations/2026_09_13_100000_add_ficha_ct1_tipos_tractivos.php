<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha técnica CT-1 (EXPEDIENTE TÉCNICO DE VEHÍCULOS).
 *
 * El legacy guarda la ficha del tipo de tractivo en `tec_tipotractivos`
 * (país de origen, ejes, distancias entre ejes, batería, neumáticos, cama).
 * La normalización de tipos movió marca/modelo/fabricación a `tipo_vehiculos`,
 * pero quedaron sin destino `id_pais` y `bat_volt`, y los backfills de
 * `fabricacion` y de las medidas de neumáticos quedaron incompletos.
 *
 * Esta migración:
 *  1. Añade `tipos_tractivos.id_pais` (FK al catálogo unificado `paises`) y
 *     `tipos_tractivos.bat_volt`.
 *  2. Rellena desde el legacy (idempotente):
 *     - `tipo_vehiculos.fabricacion` ← tec_tipotractivos.fabricacion (solo año)
 *     - `tipos_tractivos.id_pais` ← catalogo_items(tipo=paises, origen_id=idpaises)
 *     - `tipos_tractivos.bat_volt` ← tec_tipotractivos.bat_volt
 *     - `tipos_tractivos.id_medida_del/tra/res` ← catalogo_items(tipo=medidas_neumaticos)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            if (! Schema::hasColumn('tipos_tractivos', 'id_pais')) {
                $table->unsignedBigInteger('id_pais')->nullable()->after('eject_trac');
                $table->index('id_pais');
                $table->foreign('id_pais')->references('id')->on('catalogo_items')->nullOnDelete();
            }
            if (! Schema::hasColumn('tipos_tractivos', 'bat_volt')) {
                $table->integer('bat_volt')->nullable()->after('bat_amp');
            }
        });

        if (! $this->legacyDisponible()) {
            return;
        }

        $legacy = DB::connection('legacy')->table('tec_tipotractivos')->get([
            'idtipotractivos', 'fabricacion', 'idpaises', 'bat_volt',
            'idneumaticosmedidasd', 'idneumaticosmedidast', 'idneumaticosmedidasr',
        ]);

        $paises = $this->mapaCatalogo('paises');
        $medidas = $this->mapaCatalogo('medidas_neumaticos');

        foreach ($legacy as $row) {
            $fabricacion = preg_match('/^\d{4}$/', trim((string) $row->fabricacion))
                ? (int) $row->fabricacion
                : null;

            // fabricacion vive a nivel de tipo_vehiculos (clase tractivo).
            DB::table('tipo_vehiculos')
                ->where('id_tipo_tractivo', $row->idtipotractivos)
                ->update(['fabricacion' => $fabricacion, 'updated_at' => now()]);

            DB::table('tipos_tractivos')
                ->where('id', $row->idtipotractivos)
                ->update([
                    'id_pais' => $row->idpaises ? ($paises[(int) $row->idpaises] ?? null) : null,
                    'bat_volt' => $row->bat_volt ?: null,
                    'id_medida_del' => $row->idneumaticosmedidasd ? ($medidas[(int) $row->idneumaticosmedidasd] ?? null) : null,
                    'id_medida_tra' => $row->idneumaticosmedidast ? ($medidas[(int) $row->idneumaticosmedidast] ?? null) : null,
                    'id_medida_res' => $row->idneumaticosmedidasr ? ($medidas[(int) $row->idneumaticosmedidasr] ?? null) : null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            if (Schema::hasColumn('tipos_tractivos', 'id_pais')) {
                $table->dropForeign(['id_pais']);
                $table->dropIndex(['id_pais']);
                $table->dropColumn('id_pais');
            }
            if (Schema::hasColumn('tipos_tractivos', 'bat_volt')) {
                $table->dropColumn('bat_volt');
            }
        });
    }

    /**
     * Mapa origen_id legacy → id de catalogo_items para un tipo dado.
     *
     * @return array<int,int>
     */
    private function mapaCatalogo(string $tipo): array
    {
        return DB::table('catalogo_items')
            ->where('tipo', $tipo)
            ->whereNotNull('origen_id')
            ->pluck('id', 'origen_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function legacyDisponible(): bool
    {
        try {
            return DB::connection('legacy')->getPdo() !== null
                && Schema::connection('legacy')->hasTable('tec_tipotractivos');
        } catch (Throwable) {
            return false;
        }
    }
};
