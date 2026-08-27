<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Compara tractivos/arrastres del legacy (EMCARGA) contra el nuevo esquema
 * (Zafiro) y genera un Excel de discrepancias:
 *   - Hoja "Resumen": conteos legacy vs nuevo y total de diferencias.
 *   - Hoja "Faltantes": ids del legacy que NO existen en el nuevo esquema.
 *   - Hoja "Discrepancias": una fila por cada campo distinto
 *     (id, clase, campo, valor_legacy, valor_nuevo).
 *
 * Uso:
 *   php artisan zafiro:comparar-vehiculos
 *   php artisan zafiro:comparar-vehiculos --salida=/ruta.xlsx
 */
class CompararVehiculos extends Command
{
    protected $signature = 'zafiro:comparar-vehiculos {--salida= : Ruta del .xlsx}';

    protected $description = 'Compara legacy vs nuevo y reporta discrepancias de tractivos/arrastres';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        $nuevo = DB::connection('mysql');

        // ---- Lookups legacy ----
        $lMarca = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $lModelo = $legacy->table('tec_modelo')->pluck('modelo', 'idmodelo');
        $lTipoEquipo = $legacy->table('tec_tipoequipos')->pluck('tipoequipos', 'idtipoequipos');
        $lTipoComb = $legacy->table('tec_tipocombustibles')->pluck('tipocombustibles', 'idtipocombustibles');
        $lColor = $legacy->table('tec_colores')->pluck('colores', 'idcolores');
        $lEstado = $legacy->table('tec_tipoestados')->pluck('tipoestados', 'idtipoestados');
        $lEntidad = $legacy->table('rh_entidades')->pluck('nombentidad', 'identidades');
        $lPais = $legacy->table('tec_paises')->pluck('paises', 'idpaises');
        $lMtto = $legacy->table('tec_tipomtto')->pluck('tipomtto', 'idtipomtto');

        // Resolución de país y tipo de mantenimiento por id_tipo_vehiculo (nuevo) -> legacy.
        // El ETL conservó los ids: nuevo tractivos.id_tipo_vehiculo == legacy idtipotractivos/idtipoarrastres.
        $tpPais = $legacy->table('tec_tipotractivos')->pluck('idpaises', 'idtipotractivos');
        $tpMtto = $legacy->table('tec_tipotractivos')->pluck('idtipomtto', 'idtipotractivos');
        $taPais = $legacy->table('tec_tipoarrastres')->pluck('idpaises', 'idtipoarrastres');
        $taMtto = $legacy->table('tec_tipoarrastres')->pluck('idtipomtto', 'idtipoarrastres');
        // Pivote de arrastre: el nuevo arrastre no trae marca/modelo/tipo_equipo,
        // pero su id_tipo_vehiculo (= legacy idtipoarrastres) sí los resuelve.
        $taMarca = $legacy->table('tec_tipoarrastres')->pluck('idmarca', 'idtipoarrastres');
        $taModelo = $legacy->table('tec_tipoarrastres')->pluck('idmodelo', 'idtipoarrastres');
        $taTipoEquipo = $legacy->table('tec_tipoarrastres')->pluck('idtipoequipos', 'idtipoarrastres');

        // Resolución del lado NUEVO por id (el ETL preserva el id del legacy:
        // tractivos.id / arrastres.id == tec_tractivos.idtractivos). Así el
        // atributo del arrastre se obtiene del pivote tec_tipoarrastres, y el
        // del tractor de tec_tipotractivos, sin depender del id_tipo_vehiculo.
        $legTractById = $legacy->table('tec_tractivos')->where('idgrupo', '!=', 8)->pluck('idtipotractivos', 'idtractivos');
        $legArrById = $legacy->table('tec_tractivos')->where('idgrupo', '=', 8)->pluck('idtipotractivos', 'idtractivos');
        $tpById = $legacy->table('tec_tipotractivos')->get()->keyBy('idtipotractivos');
        $taById = $legacy->table('tec_tipoarrastres')->get()->keyBy('idtipoarrastres');

        $resolverNuevo = function (int $id, bool $esArrastre) use (
            $legTractById, $legArrById, $tpById, $taById,
            $lMarca, $lModelo, $lTipoEquipo, $lPais, $lMtto
        ): array {
            $porId = $esArrastre ? $legArrById : $legTractById;
            $tipoId = $porId[$id] ?? null;
            if ($tipoId === null) {
                return ['SIN DEFINIR', 'SIN DEFINIR', 'SIN DEFINIR', 'SIN DEFINIR', 'SIN DEFINIR'];
            }
            $row = $esArrastre ? ($taById[$tipoId] ?? null) : ($tpById[$tipoId] ?? null);
            if ($row === null) {
                return ['SIN DEFINIR', 'SIN DEFINIR', 'SIN DEFINIR', 'SIN DEFINIR', 'SIN DEFINIR'];
            }
            return [
                $lMarca[$row->idmarca] ?? 'SIN DEFINIR',
                $lModelo[$row->idmodelo] ?? 'SIN DEFINIR',
                $lTipoEquipo[$row->idtipoequipos] ?? 'SIN DEFINIR',
                $lPais[$row->idpaises] ?? 'SIN DEFINIR',
                $lMtto[$row->idtipomtto] ?? 'SIN DEFINIR',
            ];
        };

        // ---- Lookups nuevo ----
        $nCatalogo = $nuevo->table('catalogo_items')->whereNull('deleted_at')->pluck('nombre', 'id');
        $nTipoEquipo = $nuevo->table('tipos_equipos')->pluck('nombre', 'id');
        $nTipoComb = $nuevo->table('tipos_combustibles')->pluck('nombre', 'id');
        $nEstado = $nuevo->table('estados_componentes')->pluck('nombre', 'id');
        $nEntidad = $nuevo->table('entidades')->pluck('nombre', 'id');

        // ---- Cargar legacy ----
        $legacyT = $legacy->table('tec_tractivos as t')
            ->leftJoin('tec_tipotractivos as tp', 'tp.idtipotractivos', '=', 't.idtipotractivos')
            ->where('t.idgrupo', '!=', 8)
            ->select('t.*', 'tp.fabricacion as tp_fabricacion', 'tp.idtipoequipos', 'tp.idtipocombustibles', 'tp.idmarca', 'tp.idmodelo', 'tp.idpaises as tp_idpaises', 'tp.idtipomtto as tp_idtipomtto')
            ->get()->keyBy('idtractivos');

        $legacyA = $legacy->table('tec_tractivos as t')
            ->leftJoin('tec_tipoarrastres as ta', 'ta.idtipoarrastres', '=', 't.idtipotractivos')
            ->where('t.idgrupo', '=', 8)
            ->select('t.*', 'ta.fabricacion as ta_fabricacion', 'ta.idtipoequipos', 'ta.idmarca', 'ta.idmodelo', 'ta.idpaises as ta_idpaises', 'ta.idtipomtto as ta_idtipomtto')
            ->get()->keyBy('idtractivos');

        // ---- Cargar nuevo ----
        $nuevoT = $nuevo->table('tractivos')->whereNull('deleted_at')->get()->keyBy('id');
        $nuevoA = $nuevo->table('arrastres')->whereNull('deleted_at')->get()->keyBy('id');

        $discrepancias = [];
        $faltantes = [];

        // ----- Tractores -----
        foreach ($legacyT as $id => $l) {
            if (! isset($nuevoT[$id])) {
                $faltantes[] = ['id' => $id, 'clase' => 'Tractor', 'motivo' => 'No existe en tabla tractivos (¿fbaja en legacy?)'];
                continue;
            }
            $n = $nuevoT[$id];
            $this->comparar($discrepancias, $id, 'Tractor', [
                'codigo' => [$l->codtractivo, $n->codigo],
                'placa' => [$l->chapa, $n->placa],
                'marca' => [$lMarca[$l->idmarca] ?? null, $n->marca],
                'modelo' => [$lModelo[$l->idmodelo] ?? null, $n->modelo],
                'tipo_combustible' => [$lTipoComb[$l->idtipocombustibles] ?? null, $nTipoComb[$n->id_tipo_combustible] ?? null],
                'color_primario' => [$lColor[$l->idcolorprimario] ?? null, $nCatalogo[$n->id_color_primario] ?? null],
                'color_secundario' => [$lColor[$l->idcolorsecundario] ?? null, $nCatalogo[$n->id_color_secundario] ?? null],
                'estado_componente' => [$lEstado[$l->idtipoestados] ?? null, $nEstado[$n->id_tipo_estado] ?? null],
                'entidad' => [$lEntidad[$l->idunidad] ?? null, $nEntidad[$n->id_entidad] ?? null],
                'vin' => [$l->vin, $n->vin],
                'numero_chasis' => [$l->chassis, $n->numero_chasis],
                'capacidad' => [$l->capacidad, $n->capacidad_toneladas],
                'tara' => [$l->tara, $n->tara],
                'cap_deposito' => [$l->captanque, $n->cap_deposito],
                'cap_hidraulico' => [$l->caphidraulico, $n->cap_hidraulico],
                'cta_combustible' => [$l->ctacomb, $n->cta_combustible],
                'indice_consumo' => [$l->indice, $n->indice_consumo],
                'indice_aceite' => [$l->indiceac, $n->indice_aceite],
                'kilometraje_actual' => [$l->kmsacum, $n->kilometraje_actual],
                'kms_disp' => [$l->kmsdisp, $n->kms_disp],
                'kms_plan_mtto' => [$l->kmsplanmtto, $n->kms_plan_mtto],
                'gps' => [$l->gps, $n->gps],
                'anno' => [$l->tp_fabricacion, $n->anno],
                'estado' => [$lEstado[$l->idtipoestados] ?? null, $n->estado],
                'fecha_alta' => [(string) $l->falta, (string) $n->fecha_alta],
                'fecha_baja' => [(string) $l->fbaja, (string) $n->fecha_baja],
            ]);
        }

        // ----- Arrastres -----
        foreach ($legacyA as $id => $l) {
            if (! isset($nuevoA[$id])) {
                $faltantes[] = ['id' => $id, 'clase' => 'Arrastre', 'motivo' => 'No existe en tabla arrastres'];
                continue;
            }
            $n = $nuevoA[$id];
            $this->comparar($discrepancias, $id, 'Arrastre', [
                'codigo' => [$l->codtractivo, $n->codigo],
                'placa' => [$l->chapa, $n->placa],
                'marca' => [$lMarca[$l->idmarca] ?? null, $n->marca ?? null],
                'modelo' => [$lModelo[$l->idmodelo] ?? null, $n->modelo ?? null],
                'color_primario' => [$lColor[$l->idcolorprimario] ?? null, $nCatalogo[$n->id_color_primario] ?? null],
                'color_secundario' => [$lColor[$l->idcolorsecundario] ?? null, $nCatalogo[$n->id_color_secundario] ?? null],
                'entidad' => [$lEntidad[$l->idunidad] ?? null, $nEntidad[$n->id_entidad] ?? null],
                'tara' => [$l->tara, $n->tara],
                'indice_aceite' => [$l->indiceac, $n->indice_aceite],
                'estado' => [$lEstado[$l->idtipoestados] ?? null, $n->estado],
                'fecha_alta' => [(string) $l->falta, (string) $n->fecha_alta],
                'fecha_baja' => [(string) $l->fbaja, (string) $n->fecha_baja],
            ]);
        }

        // También detectar ids nuevos que no están en legacy (orphans)
        foreach ($nuevoT as $id => $n) {
            if (! isset($legacyT[$id])) {
                $faltantes[] = ['id' => $id, 'clase' => 'Tractor', 'motivo' => 'Existe en nuevo pero NO en legacy'];
            }
        }
        foreach ($nuevoA as $id => $n) {
            if (! isset($legacyA[$id])) {
                $faltantes[] = ['id' => $id, 'clase' => 'Arrastre', 'motivo' => 'Existe en nuevo pero NO en legacy'];
            }
        }

        // ---- Conteos por tipo ----
        $porTipo = ['discrepancia' => 0, 'pendiente_backfill' => 0, 'extra_sin_legacy' => 0];
        foreach ($discrepancias as $d) {
            $porTipo[$d['tipo']] = ($porTipo[$d['tipo']] ?? 0) + 1;
        }

        // ---- Escribir Excel ----
        $spreadsheet = new Spreadsheet();
        $resumen = $spreadsheet->getActiveSheet();
        $resumen->setTitle('Resumen');
        $resumen->fromArray([
            ['Métrica', 'Valor'],
            ['Legacy tractores', $legacyT->count()],
            ['Nuevo tractores', $nuevoT->count()],
            ['Legacy arrastres', $legacyA->count()],
            ['Nuevo arrastres', $nuevoA->count()],
            ['Filas con discrepancia', count($discrepancias)],
            ['  - discrepancias reales (valor vs valor)', $porTipo['discrepancia'] ?? 0],
            ['  - pendiente backfill (nuevo vacío)', $porTipo['pendiente_backfill'] ?? 0],
            ['  - extra sin contraparte legacy', $porTipo['extra_sin_legacy'] ?? 0],
            ['Ids faltantes / orphan', count($faltantes)],
        ], null, 'A1');

        $this->hojaSimple($spreadsheet->createSheet(), 'Faltantes', ['id', 'clase', 'motivo'], $faltantes);
        $this->hojaSimple($spreadsheet->createSheet(), 'Discrepancias', ['id', 'clase', 'campo', 'tipo', 'valor_legacy', 'valor_nuevo'], $discrepancias);

        // ---- Tablas pivot por dimensión (Legacy vs Nuevo) ----
        $inc = function (array &$a, $k): void {
            $a[$k] = ($a[$k] ?? 0) + 1;
        };
        $leg = ['marca' => [], 'modelo' => [], 'tipo_equipo' => [], 'pais' => [], 'mtto' => []];
        $nue = ['marca' => [], 'modelo' => [], 'tipo_equipo' => [], 'pais' => [], 'mtto' => []];

        foreach ($legacyT as $l) {
            $inc($leg['marca'], self::normKey($lMarca[$l->idmarca] ?? 'SIN DEFINIR'));
            $inc($leg['modelo'], self::normKey($lModelo[$l->idmodelo] ?? 'SIN DEFINIR'));
            $inc($leg['tipo_equipo'], $lTipoEquipo[$l->idtipoequipos] ?? 'SIN DEFINIR');
            $inc($leg['pais'], $lPais[$l->tp_idpaises] ?? 'SIN DEFINIR');
            $inc($leg['mtto'], $lMtto[$l->tp_idtipomtto] ?? 'SIN DEFINIR');
        }
        foreach ($legacyA as $l) {
            $inc($leg['marca'], self::normKey($lMarca[$l->idmarca] ?? 'SIN DEFINIR'));
            $inc($leg['modelo'], self::normKey($lModelo[$l->idmodelo] ?? 'SIN DEFINIR'));
            $inc($leg['tipo_equipo'], $lTipoEquipo[$l->idtipoequipos] ?? 'SIN DEFINIR');
            $inc($leg['pais'], $lPais[$l->ta_idpaises] ?? 'SIN DEFINIR');
            $inc($leg['mtto'], $lMtto[$l->ta_idtipomtto] ?? 'SIN DEFINIR');
        }
        foreach ($nuevoT as $n) {
            $inc($nue['marca'], self::normKey($n->marca ?: 'SIN DEFINIR'));
            $inc($nue['modelo'], self::normKey($n->modelo ?: 'SIN DEFINIR'));
            $inc($nue['tipo_equipo'], $nTipoEquipo[$n->id_tipo_equipo] ?? 'SIN DEFINIR');
            // pais/mtto del tractor resueltos por id -> legacy -> pivote tractivo.
            [$m, $mo, $te, $p, $mtto] = $resolverNuevo($n->id, false);
            $inc($nue['pais'], $p);
            $inc($nue['mtto'], $mtto);
        }
        foreach ($nuevoA as $n) {
            // Arrastre: el nuevo esquema no migró marca/modelo/tipo_equipo/pais/mtto.
            // Se resuelven por id (== legacy idtractivos) -> tec_tipoarrastres (pivote arrastre).
            [$m, $mo, $te, $p, $mtto] = $resolverNuevo($n->id, true);
            $inc($nue['marca'], self::normKey($m));
            $inc($nue['modelo'], self::normKey($mo));
            $inc($nue['tipo_equipo'], $te);
            $inc($nue['pais'], $p);
            $inc($nue['mtto'], $mtto);
        }

        $this->pivotSheet($spreadsheet->createSheet(), 'Por_Marca', $leg['marca'], $nue['marca']);
        $this->pivotSheet($spreadsheet->createSheet(), 'Por_Modelo', $leg['modelo'], $nue['modelo']);
        $this->pivotSheet($spreadsheet->createSheet(), 'Por_TipoEquipo', $leg['tipo_equipo'], $nue['tipo_equipo']);
        $this->pivotSheet($spreadsheet->createSheet(), 'Por_Pais', $leg['pais'], $nue['pais']);
        $this->pivotSheet($spreadsheet->createSheet(), 'Por_TipoMantenimiento', $leg['mtto'], $nue['mtto']);

        $salida = $this->option('salida') ?: storage_path('app/exports/comparacion_vehiculos_' . date('Y-m-d_His') . '.xlsx');
        $dir = dirname($salida);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        (new Xlsx($spreadsheet))->save($salida);

        // Resumen por campo (solo discrepancias reales)
        $porCampo = [];
        foreach ($discrepancias as $d) {
            if ($d['tipo'] !== 'discrepancia') {
                continue;
            }
            $porCampo[$d['campo']] = ($porCampo[$d['campo']] ?? 0) + 1;
        }
        arsort($porCampo);
        $this->info("=== Discrepancias REALES por campo (top 25) ===");
        $i = 0;
        foreach ($porCampo as $campo => $n) {
            if ($i++ >= 25) {
                break;
            }
            $this->info(sprintf("  %-22s %d", $campo, $n));
        }
        $this->info("--- Conteo por tipo ---");
        $this->info(sprintf("  %-22s %d", 'discrepancia (valor vs valor)', $porTipo['discrepancia']));
        $this->info(sprintf("  %-22s %d", 'pendiente_backfill (nuevo vacío)', $porTipo['pendiente_backfill']));
        $this->info(sprintf("  %-22s %d", 'extra_sin_legacy', $porTipo['extra_sin_legacy']));

        $this->info("Comparación generada en: {$salida}");
        $this->info("Total filas: " . count($discrepancias) . " | Faltantes/orphan: " . count($faltantes));

        return self::SUCCESS;
    }

    private function comparar(array &$discrepancias, $id, string $clase, array $campos): void
    {
        foreach ($campos as $campo => [$vl, $vn]) {
            // Normaliza: null/''/0 numérico se tratan como "vacío" (legacy 0 => nuevo null es esperado).
            $norm = function ($v) {
                if ($v === null) {
                    return '';
                }
                if (is_numeric($v) && (float) $v == 0) {
                    return '';
                }
                return mb_strtolower(trim((string) $v));
            };
            $a = $norm($vl);
            $b = $norm($vn);
            if ($a === $b) {
                continue;
            }
            // Igualdad numérica (evita falsos positivos por formato: 15.5 vs 15.50)
            if (is_numeric($vl) && is_numeric($vn) && (float) $vl == (float) $vn) {
                continue;
            }
            // Clasifica: ambos con valor y distintos = discrepancia real;
            // nuevo vacío y legacy con valor = pendiente de backfill;
            // nuevo con valor y legacy vacío = extra sin contraparte.
            if ($a !== '' && $b !== '') {
                $tipo = 'discrepancia';
            } elseif ($a !== '' && $b === '') {
                $tipo = 'pendiente_backfill';
            } else {
                $tipo = 'extra_sin_legacy';
            }
            $discrepancias[] = [
                'id' => $id,
                'clase' => $clase,
                'campo' => $campo,
                'tipo' => $tipo,
                'valor_legacy' => $vl === null ? '' : $vl,
                'valor_nuevo' => $vn === null ? '' : $vn,
            ];
        }
    }

    private function pivotSheet($sheet, string $titulo, array $leg, array $nue): void
    {
        $sheet->setTitle($titulo);
        $headers = ['Valor', 'Legacy', 'Nuevo', 'Diferencia'];
        $sheet->fromArray($headers, null, 'A1');
        $headerStyle = $sheet->getStyle('A1:' . $sheet->getCellByColumnAndRow(count($headers), 1)->getCoordinate());
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('1F4E78');
        $headerStyle->getFont()->getColor()->setRGB('FFFFFF');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $keys = array_values(array_unique(array_merge(array_keys($leg), array_keys($nue))));
        usort($keys, fn ($a, $b) => ($leg[$b] ?? 0) <=> ($leg[$a] ?? 0) ?: strcmp($a, $b));

        $fila = 2;
        $tL = 0;
        $tN = 0;
        foreach ($keys as $k) {
            $vl = $leg[$k] ?? 0;
            $vn = $nue[$k] ?? 0;
            $sheet->setCellValue("A{$fila}", $k);
            $sheet->setCellValue("B{$fila}", $vl);
            $sheet->setCellValue("C{$fila}", $vn);
            $sheet->setCellValue("D{$fila}", $vn - $vl);
            $tL += $vl;
            $tN += $vn;
            $fila++;
        }
        $sheet->setCellValue("A{$fila}", 'TOTAL');
        $sheet->setCellValue("B{$fila}", $tL);
        $sheet->setCellValue("C{$fila}", $tN);
        $sheet->setCellValue("D{$fila}", $tN - $tL);
        $tot = $sheet->getStyle("A{$fila}:D{$fila}");
        $tot->getFont()->setBold(true);
        $tot->getFill()->setFillType(Fill::FILL_SOLID);
        $tot->getFill()->getStartColor()->setRGB('D9E1F2');

        foreach (range(1, 4) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
        $sheet->freezePane('A2');
    }

    /**
     * Normaliza la clave de marca/modelo para fusionar valores repetidos por
     * variantes de espacios (incl. no-breaking space) y unificar las variantes
     * de "sin definir" (S/DEFINIR, SIN DEFINIR, etc.) en un solo valor.
     * No se aplica a tipo_equipo/pais/mtto (ya reconcilian sin duplicados).
     */
    private static function normKey(?string $v): string
    {
        if ($v === null) {
            return 'SIN DEFINIR';
        }
        $v = str_replace(["\xc2\xa0", "\xa0"], ' ', $v);
        $v = preg_replace('/\s+/u', ' ', $v);
        $v = trim($v);
        if ($v === '') {
            return 'SIN DEFINIR';
        }
        $low = mb_strtolower($v);
        if (in_array($low, ['s/definir', 's definir', 'sin definir', 's.definir', 's-definir', 'sdefinir'], true)) {
            return 'SIN DEFINIR';
        }
        // Para el resto, elimina espacios internos para fusionar variantes
        // como "469 B" / "469B" sin alterar la etiqueta "SIN DEFINIR".
        return preg_replace('/\s+/u', '', $v);
    }

    private function hojaSimple($sheet, string $titulo, array $headers, array $filas): void
    {
        $sheet->setTitle($titulo);
        $sheet->fromArray($headers, null, 'A1');
        $headerStyle = $sheet->getStyle('A1:' . $sheet->getCellByColumnAndRow(count($headers), 1)->getCoordinate());
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('C00000');
        $headerStyle->getFont()->getColor()->setRGB('FFFFFF');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fila = 2;
        foreach ($filas as $reg) {
            $col = 1;
            foreach ($headers as $h) {
                $sheet->setCellValueByColumnAndRow($col, $fila, $reg[$h] ?? '');
                $col++;
            }
            $fila++;
        }
        foreach (range(1, count($headers)) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
        $sheet->freezePane('A2');
    }
}
