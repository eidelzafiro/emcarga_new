<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migra a la BD nueva los vehículos legacy tec_tractivos que aún no están
 * (los de baja, fbaja != null, que el ETL excluye por diseño) para que el
 * parque quede exacto (1595).
 *
 * - Replica el mapeo de columnas de EtlService::migrarTractivos.
 * - Si placa/codigo colisionan con un registro ya migrado (UNIQUE), se prefija "R-".
 * - Solo tractores (idgrupo != 8); los arrastres de baja no aplican en este lote.
 */
class MigrarVehiculosPendientes extends Command
{
    protected $signature = 'zafiro:migrar-vehiculos-pendientes';

    protected $description = 'Migra los tractivos de baja (fbaja) y resuelve placas/códigos repetidos con prefijo R-';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');

        $yaTractivo = DB::table('tractivos')->pluck('id')->flip();
        $yaArrastre = DB::table('arrastres')->pluck('id')->flip();

        $tipos = $legacy->table('tec_tipotractivos')->get(['idtipotractivos', 'idmarca', 'idmodelo', 'fabricacion'])->keyBy('idtipotractivos');
        $marcas = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $modelos = $legacy->table('tec_modelo')->pluck('modelo', 'idmodelo');
        $coloresLeg = $legacy->table('tec_colores')->pluck('colores', 'idcolores');
        $motoresLeg = $legacy->table('tec_motores')->pluck('nroserie', 'idmotores');
        $cajasLeg = $legacy->table('tec_cajas')->pluck('nroserie', 'idcajas');
        $tiposNuevos = DB::table('tipo_vehiculos')->pluck('id')->flip();

        // FKs que resuelven contra catalogo_items (tipo, origen_id) -> id
        $catalogMap = [];
        foreach (['grupos', 'colores', 'tipos_servicios'] as $tipo) {
            $catalogMap[$tipo] = DB::table('catalogo_items')->where('tipo', $tipo)->pluck('id', 'origen_id')->flip();
        }

        // FKs que son tablas reales: el id nuevo coincide con el legacy si existe
        $realTables = [];
        foreach (['estados_componentes', 'lubricantes', 'motores', 'cajas', 'diferenciales'] as $t) {
            $realTables[$t] = DB::table($t)->pluck('id')->flip();
        }

        $fkCat = function (string $tipo, $v) use ($catalogMap): ?int {
            if ($v === null || (int) $v === 0) {
                return null;
            }

            return $catalogMap[$tipo][(int) $v] ?? null;
        };
        $fkReal = function (string $tabla, $v) use ($realTables): ?int {
            if ($v === null || (int) $v === 0) {
                return null;
            }
            $id = (int) $v;

            return isset($realTables[$tabla][$id]) ? $id : null;
        };

        $fecha = function ($v): ?string {
            if ($v === null) {
                return null;
            }
            $s = (string) $v;

            return str_starts_with($s, '0000-00-00') ? null : $s;
        };

        $estados = [
            14 => 'activo', 26 => 'taller', 23 => 'trabajando', 27 => 'nuevo',
            25 => 'paralizado', 22 => 'propuesta_baja',
        ];

        $placasNuevas = DB::table('tractivos')->whereNotNull('placa')->where('placa', '!=', '')->pluck('placa')->flip();
        $codigosNuevos = DB::table('tractivos')->whereNotNull('codigo')->pluck('codigo')->flip();

        $filas = $legacy->table('tec_tractivos')
            ->where('idgrupo', '!=', 8)
            ->orderBy('idtractivos')
            ->get();

        $migrados = 0;
        $omitidos = 0;
        foreach ($filas as $fila) {
            if (isset($yaTractivo[$fila->idtractivos]) || isset($yaArrastre[$fila->idtractivos])) {
                continue; // ya migrado
            }

            $tipo = $tipos->get($fila->idtipotractivos);
            $idTipoVehiculo = isset($tiposNuevos[$fila->idtipotractivos]) ? $fila->idtipotractivos : null;

            $placa = trim((string) ($fila->chapa ?? '')) ?: null;
            if ($placa !== null && isset($placasNuevas[$placa])) {
                $placa = 'R-' . $placa;
                while (isset($placasNuevas[$placa])) {
                    $placa = 'R-' . $placa;
                }
            }
            if ($placa !== null) {
                $placasNuevas[$placa] = true;
            }

            $codigo = trim((string) ($fila->codtractivo ?? '')) ?: null;
            if ($codigo !== null && isset($codigosNuevos[$codigo])) {
                $codigo = 'R-' . $codigo;
                while (isset($codigosNuevos[$codigo])) {
                    $codigo = 'R-' . $codigo;
                }
            }
            if ($codigo !== null) {
                $codigosNuevos[$codigo] = true;
            }

            $fabricacion = $tipo ? trim((string) $tipo->fabricacion) : '';
            $anno = preg_match('/^\d{4}$/', $fabricacion) ? (int) $fabricacion : null;

            $falta = $fila->falta;
            if (is_string($falta) && str_starts_with($falta, '0000-00-00')) {
                $falta = null;
            }

            $upsert = function (?string $codigoFinal, ?string $placaFinal) use ($fila, $tipo, $marcas, $modelos, $coloresLeg, $motoresLeg, $cajasLeg, $estados, $anno, $falta, $fecha, $fkCat, $fkReal, $idTipoVehiculo) {
                DB::table('tractivos')->updateOrInsert(
                    ['id' => $fila->idtractivos],
                    [
                        'codigo' => $codigoFinal,
                        'descripcion' => trim((string) ($fila->codtractivo ?? '')) ?: '',
                        'placa' => $placaFinal ?? '',
                        'id_tipo_vehiculo' => $idTipoVehiculo,
                        'id_motor' => $fkReal('motores', $fila->idmotores),
                        'id_caja' => $fkReal('cajas', $fila->idcajas),
                        'id_diferencial' => $fkReal('diferenciales', $fila->iddiferenciales),
                        'id_grupo' => $fkCat('grupos', $fila->idgrupo),
                        'id_tipo_servicio' => $fkCat('tipos_servicios', $fila->idtiposervicios),
                        'id_color_primario' => $fkCat('colores', $fila->idcolorprimario),
                        'id_color_secundario' => $fkCat('colores', $fila->idcolorsecundario),
                        'id_tipo_estado' => $fkReal('estados_componentes', $fila->idtipoestados),
                        'id_lubricante_hidraulico' => $fkReal('lubricantes', $fila->idlubricantes),
                        'marca' => $tipo ? ($marcas[$tipo->idmarca] ?? null) : null,
                        'modelo' => $tipo ? ($modelos[$tipo->idmodelo] ?? null) : null,
                        'anno' => $anno,
                        'color' => $coloresLeg[$fila->idcolorprimario] ?? null,
                        'vin' => trim((string) ($fila->vin ?? '')) ?: null,
                        'numero_motor' => $motoresLeg[$fila->idmotores] ?? null,
                        'numero_chasis' => trim((string) ($fila->chassis ?? '')) ?: null,
                        'numero_caja' => $cajasLeg[$fila->idcajas] ?? null,
                        'capacidad_toneladas' => $fila->capacidad,
                        'tara' => $fila->tara && $fila->tara < 100000000 ? $fila->tara : null,
                        'cap_deposito' => $fila->captanque ?: null,
                        'cap_hidraulico' => $fila->caphidraulico ?: null,
                        'cta_combustible' => trim((string) ($fila->ctacomb ?? '')) ?: null,
                        'indice_consumo' => $fila->indice ?: null,
                        'indice_aceite' => $fila->indiceac ?: null,
                        'kms_disp' => $fila->kmsdisp ?: null,
                        'kms_plan_mtto' => $fila->kmsplanmtto ?: null,
                        'kilometraje_actual' => $fila->kmsacum ?? 0,
                        'estado' => $estados[$fila->idtipoestados] ?? 'activo',
                        'fecha_alta' => $falta,
                        'fecha_baja' => $fecha($fila->fbaja),
                        'gps' => $fila->gps ?: null,
                        'id_entidad' => $fila->idunidad ?: null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $vid = $fila->idtractivos;
                DB::table('vehiculos_amortizacion')->updateOrInsert(
                    ['vehiculo_type' => 'tractivo', 'vehiculo_id' => $vid],
                    ['amortmn' => $fila->amortmn ?? 0, 'amortme' => $fila->amortme ?? 0, 'vchapa' => $fila->vchapa ?? 0, 'updated_at' => now()]
                );
                DB::table('vehiculos_planes')->updateOrInsert(
                    ['vehiculo_type' => 'tractivo', 'vehiculo_id' => $vid],
                    ['plan_comb' => $fila->plancomb ?? null, 'plan_tn' => $fila->plantn ?? null, 'plan_viajes' => $fila->planviajes ?? null, 'plan_gastos' => $fila->plangastos ?? null, 'plan_cdt' => $fila->plancdt ?? null, 'plan_diario' => $fila->plandiario ?: null, 'updated_at' => now()]
                );
                DB::table('vehiculos_documentacion')->updateOrInsert(
                    ['vehiculo_type' => 'tractivo', 'vehiculo_id' => $vid],
                    [
                        'ficav' => trim((string) ($fila->ficav ?? '')) ?: null,
                        'femision_ficav' => $fecha($fila->femision_ficav),
                        'fvence_ficav' => $fecha($fila->fvence_ficav),
                        'lot' => trim((string) ($fila->lot ?? '')) ?: null,
                        'femision_lot' => $fecha($fila->femision_lot),
                        'fvence_lot' => $fecha($fila->fvence_lot),
                        'circulacion' => trim((string) ($fila->circulacion ?? '')) ?: null,
                        'femision_circ' => $fecha($fila->femision_circ),
                        'fvence_circ' => $fecha($fila->fvence_circ),
                        'f_reconstruccion' => $fecha($fila->fureconstruccion),
                        'updated_at' => now(),
                    ]
                );
            };

            try {
                $upsert($codigo, $placa);
                $migrados++;
                $this->info("  migrado tractivo#{$fila->idtractivos} placa=" . ($placa ?? '') . " codigo=" . ($codigo ?? ''));
            } catch (\Throwable $e) {
                $omitidos++;
                $this->warn("  NO migrado tractivo#{$fila->idtractivos}: " . $e->getMessage());
            }
        }

        $this->info("Migrados: {$migrados} | Omitidos: {$omitidos}");
        $this->info('Total tractivos en nuevo: ' . DB::table('tractivos')->count());

        return self::SUCCESS;
    }
}
