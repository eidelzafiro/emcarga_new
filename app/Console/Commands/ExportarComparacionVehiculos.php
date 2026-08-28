<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta a Excel una tabla comparativa lado a lado (legacy EMCARGA vs nuevo
 * Zafiro) con las columnas: entidad, chapa, marca, modelo, tipo de equipo y
 * mantenimiento. El empalme es por id (tractivos.id / arrastres.id ==
 * tec_tractivos.idtractivos, verificado 100% de coincidencia).
 *
 * Genera dos hojas:
 *   - "Comparacion": todas las filas, con columnas legacy y nuevo en paralelo
 *     y una columna "discrepancias" que lista los campos que no coinciden.
 *   - "Discrepancias": solo las filas que difieren (o faltan en el nuevo).
 *
 * Uso:
 *   php artisan zafiro:exportar-comparacion-vehiculos
 *   php artisan zafiro:exportar-comparacion-vehiculos --solo=tractivos
 *   php artisan zafiro:exportar-comparacion-vehiculos --solo=arrastres
 *   php artisan zafiro:exportar-comparacion-vehiculos --salida=/ruta.xlsx
 */
class ExportarComparacionVehiculos extends Command
{
    protected $signature = 'zafiro:exportar-comparacion-vehiculos
                            {--solo= : tractivos | arrastres | todos}
                            {--salida= : Ruta del archivo .xlsx de salida}';

    protected $description = 'Tabla comparativa legacy vs nuevo: entidad, chapa, marca, modelo, tipo equipo, mantenimiento';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        $nuevo = DB::connection('mysql');

        // ---- Lookups legacy ----
        $lMarca = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $lModelo = $legacy->table('tec_modelo')->pluck('modelo', 'idmodelo');
        $lEquipo = $legacy->table('tec_tipoequipos')->pluck('tipoequipos', 'idtipoequipos');
        $lMtto = $legacy->table('tec_tipomtto')->pluck('tipomtto', 'idtipomtto');
        $lEntidad = $legacy->table('rh_entidades')->pluck('nombentidad', 'identidades');

        // ---- Lookups nuevo ----
        $nCatalogo = $nuevo->table('catalogo_items')->whereNull('deleted_at')->pluck('nombre', 'id');
        $nEquipo = $nuevo->table('tipos_equipos')->pluck('nombre', 'id');
        $nMtto = $nuevo->table('tipos_mantenimiento')->pluck('nombre', 'id');
        $nEntidad = $nuevo->table('entidades')->pluck('nombre', 'id');

        // ---- Nuevo: tractores y arrastres con su tipo_vehiculo ----
        $mapNuevo = function (string $tabla) use ($nuevo) {
            return $nuevo->table($tabla . ' as v')
                ->leftJoin('tipo_vehiculos as tv', 'tv.id', '=', 'v.id_tipo_vehiculo')
                ->select(
                    'v.id', 'v.placa', 'v.codigo', 'v.id_entidad',
                    'tv.id_marca', 'tv.id_modelo', 'tv.id_tipo_equipo', 'tv.id_tipo_mantenimiento'
                )
                ->get()
                ->keyBy('id');
        };
        $nTractores = $mapNuevo('tractivos');
        $nArrastres = $mapNuevo('arrastres');

        // ---- Legacy: tractores (idgrupo != 8) y arrastres (idgrupo = 8) ----
        $legT = $legacy->table('tec_tractivos as t')
            ->leftJoin('tec_tipotractivos as tp', 'tp.idtipotractivos', '=', 't.idtipotractivos')
            ->where('t.idgrupo', '!=', 8)
            ->select('t.idtractivos', 't.chapa', 't.codtractivo', 't.idunidad', 'tp.idmarca', 'tp.idmodelo', 'tp.idtipoequipos', 'tp.idtipomtto')
            ->get();
        $legA = $legacy->table('tec_tractivos as t')
            ->leftJoin('tec_tipoarrastres as ta', 'ta.idtipoarrastres', '=', 't.idtipotractivos')
            ->where('t.idgrupo', '=', 8)
            ->select('t.idtractivos', 't.chapa', 't.codtractivo', 't.idunidad', 'ta.idmarca', 'ta.idmodelo', 'ta.idtipoequipos', 'ta.idtipomtto')
            ->get();

        $solo = $this->option('solo') ?: 'todos';
        $filas = [];

        $procesar = function ($legRows, $nuevos, $clase) use (
            &$filas, $lMarca, $lModelo, $lEquipo, $lMtto, $lEntidad,
            $nCatalogo, $nEquipo, $nMtto, $nEntidad
        ) {
            foreach ($legRows as $l) {
                $id = $l->idtractivos;
                $entL = $lEntidad[$l->idunidad] ?? null;
                $marcaL = $lMarca[$l->idmarca] ?? null;
                $modeloL = $lModelo[$l->idmodelo] ?? null;
                $equipoL = $lEquipo[$l->idtipoequipos] ?? null;
                $mttoL = $lMtto[$l->idtipomtto] ?? null;

                $n = $nuevos[$id] ?? null;
                if ($n === null) {
                    $filas[] = [
                        $id, $clase,
                        $entL, '—',
                        $l->chapa, '—',
                        $marcaL, '—',
                        $modeloL, '—',
                        $l->codtractivo, '—',
                        $equipoL, '—',
                        $mttoL, '—',
                        'FALTA EN NUEVO',
                    ];
                    continue;
                }

                $entN = $nEntidad[$n->id_entidad] ?? null;
                $marcaN = $nCatalogo[$n->id_marca] ?? null;
                $modeloN = $nCatalogo[$n->id_modelo] ?? null;
                $equipoN = $nEquipo[$n->id_tipo_equipo] ?? null;
                $mttoN = $nMtto[$n->id_tipo_mantenimiento] ?? null;

                $diffs = [];
                if (self::norm($entL) !== self::norm($entN)) {
                    $diffs[] = 'entidad';
                }
                if (self::norm($l->chapa) !== self::norm($n->placa)) {
                    $diffs[] = 'chapa';
                }
                if (self::norm($marcaL) !== self::norm($marcaN)) {
                    $diffs[] = 'marca';
                }
                if (self::norm($modeloL) !== self::norm($modeloN)) {
                    $diffs[] = 'modelo';
                }
                if (self::norm($l->codtractivo) !== self::norm($n->codigo)) {
                    $diffs[] = 'tractivo';
                }
                if (self::norm($equipoL) !== self::norm($equipoN)) {
                    $diffs[] = 'tipo_equipo';
                }
                if (self::norm($mttoL) !== self::norm($mttoN)) {
                    $diffs[] = 'mantenimiento';
                }

                $filas[] = [
                    $id, $clase,
                    $entL, $entN,
                    $l->chapa, $n->placa,
                    $marcaL, $marcaN,
                    $modeloL, $modeloN,
                    $l->codtractivo, $n->codigo,
                    $equipoL, $equipoN,
                    $mttoL, $mttoN,
                    $diffs ? implode(', ', $diffs) : 'OK',
                ];
            }
        };

        if ($solo === 'todos' || $solo === 'tractivos') {
            $procesar($legT, $nTractores, 'Tractor');
        }
        if ($solo === 'todos' || $solo === 'arrastres') {
            $procesar($legA, $nArrastres, 'Arrastre');
        }

        // Orphans: ids nuevos sin contraparte legacy (no debería ocurrir).
        $legIds = $legT->pluck('idtractivos')->merge($legA->pluck('idtractivos'))->all();
        $legIdSet = array_flip($legIds);
        $revisarOrphan = function ($nuevos, $clase) use (&$filas, $legIdSet, $nCatalogo, $nEquipo, $nMtto, $nEntidad) {
            foreach ($nuevos as $n) {
                if (isset($legIdSet[$n->id])) {
                    continue;
                }
                $filas[] = [
                    $n->id, $clase,
                    '—', $nEntidad[$n->id_entidad] ?? null,
                    '—', $n->placa,
                    '—', $nCatalogo[$n->id_marca] ?? null,
                    '—', $nCatalogo[$n->id_modelo] ?? null,
                    '—', $n->codigo,
                    '—', $nEquipo[$n->id_tipo_equipo] ?? null,
                    '—', $nMtto[$n->id_tipo_mantenimiento] ?? null,
                    'SOLO EN NUEVO (sin legacy)',
                ];
            }
        };
        if ($solo === 'todos' || $solo === 'tractivos') {
            $revisarOrphan($nTractores, 'Tractor');
        }
        if ($solo === 'todos' || $solo === 'arrastres') {
            $revisarOrphan($nArrastres, 'Arrastre');
        }

        // ---- Tally de campos divergentes ----
        $tally = [
            'entidad' => 0, 'chapa' => 0, 'marca' => 0,
            'modelo' => 0, 'tractivo' => 0, 'tipo_equipo' => 0, 'mantenimiento' => 0,
            'faltan_en_nuevo' => 0, 'solo_en_nuevo' => 0,
        ];
        foreach ($filas as $f) {
            $d = end($f);
            if ($d === 'FALTA EN NUEVO') {
                $tally['faltan_en_nuevo']++;
                continue;
            }
            if ($d === 'SOLO EN NUEVO (sin legacy)') {
                $tally['solo_en_nuevo']++;
                continue;
            }
            if ($d === 'OK') {
                continue;
            }
            foreach (explode(', ', $d) as $campo) {
                if (isset($tally[$campo])) {
                    $tally[$campo]++;
                }
            }
        }
        $this->info('Divergencias por campo:');
        foreach ($tally as $campo => $cant) {
            $this->info("  - {$campo}: {$cant}");
        }

        // ---- Escribir Excel (una sola hoja, columnas legacy y nuevo en paralelo) ----
        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Comparacion');
        $hoja->fromArray([
            [
                'id', 'clase',
                'entidad_legacy', 'entidad_nuevo',
                'chapa_legacy', 'chapa_nuevo',
                'marca_legacy', 'marca_nuevo',
                'modelo_legacy', 'modelo_nuevo',
                'tractivo_legacy', 'tractivo_nuevo',
                'tipo_equipo_legacy', 'tipo_equipo_nuevo',
                'mantenimiento_legacy', 'mantenimiento_nuevo',
                'discrepancias',
            ],
        ], null, 'A1');
        $hoja->fromArray($filas, null, 'A2');

        // Auto-ancho de columnas.
        foreach (range('A', 'P') as $col) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }

        $salida = $this->option('salida')
            ?: storage_path('app/exports/comparacion_vehiculos_' . date('Y-m-d_His') . '.xlsx');
        $dir = dirname($salida);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        (new Xlsx($spreadsheet))->save($salida);

        $this->info("Excel generado en: {$salida}");
        $totalDisc = count(array_filter($filas, fn ($f) => end($f) !== 'OK'));
        $this->info('Total filas: ' . count($filas) . ' | Discrepancias: ' . $totalDisc);

        return self::SUCCESS;
    }

    private static function norm($valor): string
    {
        return mb_strtoupper(trim((string) ($valor ?? '')));
    }
}
