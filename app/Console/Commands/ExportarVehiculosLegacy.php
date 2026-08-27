<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Exporta a Excel todos los datos de tractivos y arrastres del sistema legacy
 * (CodeIgniter / EMCARGA), incluyendo las relaciones resueltas con las tablas
 * de marca, modelo, tipo de equipo, tipo de combustible, color, estado,
 * motor, caja, diferencial y entidad.
 *
 * Uso:
 *   php artisan zafiro:exportar-vehiculos-legacy
 *   php artisan zafiro:exportar-vehiculos-legacy --solo=tractivos
 *   php artisan zafiro:exportar-vehiculos-legacy --solo=arrastres
 *   php artisan zafiro:exportar-vehiculos-legacy --salida=/ruta/archivo.xlsx
 */
class ExportarVehiculosLegacy extends Command
{
    protected $signature = 'zafiro:exportar-vehiculos-legacy
                            {--solo= : tractivos | arrastres (omita para ambos)}
                            {--salida= : Ruta del archivo .xlsx de salida}';

    protected $description = 'Exporta tractivos y arrastres del legacy a un Excel con sus relaciones';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');

        // Lookups de catálogos pequeños (clave => nombre)
        $marcas = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $modelos = $legacy->table('tec_modelo')->pluck('modelo', 'idmodelo');
        $tiposEquipos = $legacy->table('tec_tipoequipos')->pluck('tipoequipos', 'idtipoequipos');
        $tiposCombustibles = $legacy->table('tec_tipocombustibles')->pluck('tipocombustibles', 'idtipocombustibles');
        $colores = $legacy->table('tec_colores')->pluck('colores', 'idcolores');
        $tiposEstados = $legacy->table('tec_tipoestados')->pluck('tipoestados', 'idtipoestados');
        $entidades = $legacy->table('rh_entidades')->pluck('nombentidad', 'identidades');
        $paises = $legacy->table('tec_paises')->pluck('paises', 'idpaises');
        $neumaticosMedidas = $legacy->table('tec_neumaticosmedidas')->pluck('neumaticosmedidas', 'idneumaticosmedidas');
        $tiposNeumaticos = $legacy->table('tec_tiponeumaticos')->pluck('tiponeumaticos', 'idtiponeumaticos');
        $lubricantes = $legacy->table('tec_lubricantes')->pluck('lubricantes', 'idlubricantes');
        $motoresSerie = $legacy->table('tec_motores')->pluck('nroserie', 'idmotores');
        $cajasSerie = $legacy->table('tec_cajas')->pluck('nroserie', 'idcajas');
        $diferencialesCodigo = $legacy->table('tec_diferenciales')->pluck('codigo', 'iddiferenciales');

        $solo = $this->option('solo');
        $spreadsheet = new Spreadsheet();
        $primeraHoja = true;

        if (! $solo || $solo === 'tractivos') {
            $sheet = $primeraHoja ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $primeraHoja = false;
            $sheet->setTitle('Tractivos');
            $this->volcar($sheet, $this->filasTractivos($legacy, $marcas, $modelos, $tiposEquipos, $tiposCombustibles, $colores, $tiposEstados, $entidades, $paises, $neumaticosMedidas, $tiposNeumaticos, $lubricantes, $motoresSerie, $cajasSerie, $diferencialesCodigo));
        }

        if (! $solo || $solo === 'arrastres') {
            $sheet = $primeraHoja ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $primeraHoja = false;
            $sheet->setTitle('Arrastres');
            $this->volcar($sheet, $this->filasArrastres($legacy, $marcas, $modelos, $tiposEquipos, $colores, $entidades, $paises, $neumaticosMedidas, $tiposNeumaticos, $lubricantes, $diferencialesCodigo));
        }

        // Quitar hoja en blanco si quedó una activa sin título
        if ($spreadsheet->getSheetCount() > 1 && $spreadsheet->getSheetByName('Worksheet') !== null) {
            $idx = $spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet'));
            $spreadsheet->removeSheetByIndex($idx);
        }

        $salida = $this->option('salida')
            ?: storage_path('app/exports/vehiculos_legacy_' . date('Y-m-d_His') . '.xlsx');

        $dir = dirname($salida);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        (new Xlsx($spreadsheet))->save($salida);

        $this->info("Excel generado en: {$salida}");

        return self::SUCCESS;
    }

    /**
     * Tractores: tec_tractivos donde idgrupo != 8, ficha desde tec_tipotractivos.
     */
    private function filasTractivos($legacy, $marcas, $modelos, $tiposEquipos, $tiposCombustibles, $colores, $tiposEstados, $entidades, $paises, $neumaticosMedidas, $tiposNeumaticos, $lubricantes, $motoresSerie, $cajasSerie, $diferencialesCodigo): array
    {
        $filas = $legacy->table('tec_tractivos as t')
            ->leftJoin('tec_tipotractivos as tp', 'tp.idtipotractivos', '=', 't.idtipotractivos')
            ->where('t.idgrupo', '!=', 8)
            ->orderBy('t.idtractivos')
            ->select('t.*',                 'tp.fabricacion as tp_fabricacion', 'tp.idtipoequipos', 'tp.idtipocombustibles', 'tp.idpaises as tp_idpaises', 'tp.idneumaticosmedidas', 'tp.idtiponeumaticos', 'tp.idmarca', 'tp.idmodelo', 'tp.ejes_cant', 'tp.eject_trac', 'tp.neum_del_cant', 'tp.neum_tras_cant', 'tp.neum_resp_cant', 'tp.captanque as tp_captanque')
            ->get();

        $salida = [];
        foreach ($filas as $f) {
            $salida[] = [
                'id_tractivo' => $f->idtractivos,
                'codigo' => $f->codtractivo,
                'chapa' => $f->chapa,
                'clase' => 'Tractor',
                'id_tipo_vehiculo' => $f->idtipotractivos,
                'fabricacion' => $f->tp_fabricacion,
                'marca' => $marcas[$f->idmarca] ?? null,
                'modelo' => $modelos[$f->idmodelo] ?? null,
                'tipo_equipo' => $tiposEquipos[$f->idtipoequipos] ?? null,
                'tipo_combustible' => $tiposCombustibles[$f->idtipocombustibles] ?? null,
                'pais_fabricacion' => $paises[$f->tp_idpaises] ?? null,
                'grupo' => $f->idgrupo,
                'tipo_servicio' => $f->idtiposervicios,
                'estado' => $tiposEstados[$f->idtipoestados] ?? null,
                'color_primario' => $colores[$f->idcolorprimario] ?? null,
                'color_secundario' => $colores[$f->idcolorsecundario] ?? null,
                'entidad' => $entidades[$f->idunidad] ?? null,
                'numero_motor' => $motoresSerie[$f->idmotores] ?? null,
                'numero_caja' => $cajasSerie[$f->idcajas] ?? null,
                'codigo_diferencial' => $diferencialesCodigo[$f->iddiferenciales] ?? null,
                'vin' => $f->vin,
                'chassis' => $f->chassis,
                'capacidad_ton' => $f->capacidad,
                'tara' => $f->tara,
                'cap_tanque' => $f->tp_captanque ?? $f->captanque,
                'cap_hidraulico' => $f->caphidraulico,
                'cta_combustible' => $f->ctacomb,
                'indice_consumo' => $f->indice,
                'indice_aceite' => $f->indiceac,
                'kms_acum' => $f->kmsacum,
                'kms_disp' => $f->kmsdisp,
                'kms_plan_mtto' => $f->kmsplanmtto,
                'gps' => $f->gps,
                'ejes_cant' => $f->ejes_cant,
                'neum_del_cant' => $f->neum_del_cant,
                'neum_tras_cant' => $f->neum_tras_cant,
                'neum_resp_cant' => $f->neum_resp_cant,
                'medida_neumatico' => $neumaticosMedidas[$f->idneumaticosmedidas] ?? null,
                'tipo_neumatico' => $tiposNeumaticos[$f->idtiponeumaticos] ?? null,
                'amortmn' => $f->amortmn,
                'amortme' => $f->amortme,
                'vchapa' => $f->vchapa,
                'fecha_alta' => $f->falta,
                'fecha_baja' => $f->fbaja,
                'ficav' => $f->ficav,
                'lot' => $f->lot,
                'circulacion' => $f->circulacion,
                'femision_circ' => $f->femision_circ,
                'fvence_circ' => $f->fvence_circ,
                'observaciones' => $f->observaciones,
            ];
        }

        return $salida;
    }

    /**
     * Arrastres: tec_tractivos donde idgrupo = 8, ficha desde tec_tipoarrastres.
     */
    private function filasArrastres($legacy, $marcas, $modelos, $tiposEquipos, $colores, $entidades, $paises, $neumaticosMedidas, $tiposNeumaticos, $lubricantes, $diferencialesCodigo): array
    {
        $filas = $legacy->table('tec_tractivos as t')
            ->leftJoin('tec_tipoarrastres as ta', 'ta.idtipoarrastres', '=', 't.idtipotractivos')
            ->where('t.idgrupo', '=', 8)
            ->orderBy('t.idtractivos')
            ->select('t.*',                 'ta.fabricacion as ta_fabricacion', 'ta.idtipoequipos', 'ta.idpaises as ta_idpaises', 'ta.idneumaticosmedidas', 'ta.idtiponeumaticos', 'ta.idlubricantes as ta_idlubricantes', 'ta.idmarca', 'ta.idmodelo', 'ta.ejes_cant', 'ta.eject_trac', 'ta.neum_del_cant', 'ta.neum_tras_cant', 'ta.neum_resp_cant', 'ta.largo_total', 'ta.ancho_total', 'ta.altura_total', 'ta.altura_piso')
            ->get();

        $salida = [];
        foreach ($filas as $f) {
            $salida[] = [
                'id_tractivo' => $f->idtractivos,
                'codigo' => $f->codtractivo,
                'chapa' => $f->chapa,
                'clase' => 'Arrastre',
                'id_tipo_vehiculo' => $f->idtipotractivos,
                'fabricacion' => $f->ta_fabricacion,
                'marca' => $marcas[$f->idmarca] ?? null,
                'modelo' => $modelos[$f->idmodelo] ?? null,
                'tipo_equipo' => $tiposEquipos[$f->idtipoequipos] ?? null,
                'pais_fabricacion' => $paises[$f->ta_idpaises] ?? null,
                'grupo' => $f->idgrupo,
                'tipo_servicio' => $f->idtiposervicios,
                'estado' => $tiposEstados[$f->idtipoestados] ?? null,
                'color_primario' => $colores[$f->idcolorprimario] ?? null,
                'color_secundario' => $colores[$f->idcolorsecundario] ?? null,
                'entidad' => $entidades[$f->idunidad] ?? null,
                'numero_motor' => $motoresSerie[$f->idmotores] ?? null,
                'numero_caja' => $cajasSerie[$f->idcajas] ?? null,
                'codigo_diferencial' => $diferencialesCodigo[$f->iddiferenciales] ?? null,
                'vin' => $f->vin,
                'chassis' => $f->chassis,
                'capacidad_ton' => $f->capacidad,
                'tara' => $f->tara,
                'cap_tanque' => $f->captanque,
                'cap_hidraulico' => $f->caphidraulico,
                'cta_combustible' => $f->ctacomb,
                'indice_consumo' => $f->indice,
                'indice_aceite' => $f->indiceac,
                'kms_acum' => $f->kmsacum,
                'kms_disp' => $f->kmsdisp,
                'kms_plan_mtto' => $f->kmsplanmtto,
                'gps' => $f->gps,
                'lubricante' => $lubricantes[$f->ta_idlubricantes] ?? null,
                'ejes_cant' => $f->ejes_cant,
                'neum_del_cant' => $f->neum_del_cant,
                'neum_tras_cant' => $f->neum_tras_cant,
                'neum_resp_cant' => $f->neum_resp_cant,
                'medida_neumatico' => $neumaticosMedidas[$f->idneumaticosmedidas] ?? null,
                'tipo_neumatico' => $tiposNeumaticos[$f->idtiponeumaticos] ?? null,
                'largo_total' => $f->largo_total,
                'ancho_total' => $f->ancho_total,
                'altura_total' => $f->altura_total,
                'altura_piso' => $f->altura_piso,
                'amortmn' => $f->amortmn,
                'amortme' => $f->amortme,
                'vchapa' => $f->vchapa,
                'fecha_alta' => $f->falta,
                'fecha_baja' => $f->fbaja,
                'ficav' => $f->ficav,
                'lot' => $f->lot,
                'circulacion' => $f->circulacion,
                'femision_circ' => $f->femision_circ,
                'fvence_circ' => $f->fvence_circ,
                'observaciones' => $f->observaciones,
            ];
        }

        return $salida;
    }

    private function volcar($sheet, array $filas): void
    {
        if (empty($filas)) {
            $sheet->setCellValue('A1', 'Sin registros');
            return;
        }

        $encabezados = array_keys($filas[0]);
        $sheet->fromArray($encabezados, null, 'A1');

        $headerStyle = $sheet->getStyle('A1:' . $sheet->getCellByColumnAndRow(count($encabezados), 1)->getCoordinate());
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('1F4E78');
        $headerStyle->getFont()->getColor()->setRGB('FFFFFF');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fila = 2;
        foreach ($filas as $registro) {
            $col = 1;
            foreach ($registro as $valor) {
                $sheet->setCellValueByColumnAndRow($col, $fila, $valor === null ? '' : $valor);
                $col++;
            }
            $fila++;
        }

        foreach (range(1, count($encabezados)) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
        $sheet->freezePane('A2');
    }
}
