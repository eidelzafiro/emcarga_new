<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Exporta a Excel los tractivos y arrastres YA MIGRADOS al nuevo esquema
 * (Zafiro / emcarga_new), resolviendo las relaciones con las tablas del
 * nuevo modelo (tipos_equipos, tipos_combustibles, catalogo_items para
 * colores/grupos/marcas/modelos, estados_componentes, motores, cajas,
 * diferenciales, entidades, lubricantes).
 *
 * Uso:
 *   php artisan zafiro:exportar-vehiculos-nuevo
 *   php artisan zafiro:exportar-vehiculos-nuevo --solo=tractivos
 *   php artisan zafiro:exportar-vehiculos-nuevo --salida=/ruta.xlsx
 */
class ExportarVehiculosNuevo extends Command
{
    protected $signature = 'zafiro:exportar-vehiculos-nuevo
                            {--solo= : tractivos | arrastres (omita para ambos)}
                            {--salida= : Ruta del archivo .xlsx de salida}';

    protected $description = 'Exporta tractivos y arrastres migrados (nuevo esquema) a un Excel con sus relaciones';

    public function handle(): int
    {
        $db = DB::connection('mysql');

        // Lookups del nuevo esquema
        $catalogo = $db->table('catalogo_items')->whereNull('deleted_at')->pluck('nombre', 'id');
        $tiposEquipos = $db->table('tipos_equipos')->pluck('nombre', 'id');
        $tiposCombustibles = $db->table('tipos_combustibles')->pluck('nombre', 'id');
        $estadosComponentes = $db->table('estados_componentes')->pluck('nombre', 'id');
        $entidades = $db->table('entidades')->pluck('nombre', 'id');
        $motoresSerie = $db->table('motores')->pluck('numero_serie', 'id');
        $cajasSerie = $db->table('cajas')->pluck('numero_serie', 'id');
        $diferencialesCodigo = $db->table('diferenciales')->pluck('codigo', 'id');
        $lubricantes = $db->table('lubricantes')->pluck('nombre', 'id');

        $solo = $this->option('solo');
        $spreadsheet = new Spreadsheet();
        $primeraHoja = true;

        if (! $solo || $solo === 'tractivos') {
            $sheet = $primeraHoja ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $primeraHoja = false;
            $sheet->setTitle('Tractivos');
            $this->volcar($sheet, $this->filasTractivos($db, $catalogo, $tiposEquipos, $tiposCombustibles, $estadosComponentes, $entidades, $motoresSerie, $cajasSerie, $diferencialesCodigo, $lubricantes));
        }

        if (! $solo || $solo === 'arrastres') {
            $sheet = $primeraHoja ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $primeraHoja = false;
            $sheet->setTitle('Arrastres');
            $this->volcar($sheet, $this->filasArrastres($db, $catalogo, $entidades));
        }

        if ($spreadsheet->getSheetCount() > 1 && $spreadsheet->getSheetByName('Worksheet') !== null) {
            $idx = $spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet'));
            $spreadsheet->removeSheetByIndex($idx);
        }

        $salida = $this->option('salida')
            ?: storage_path('app/exports/vehiculos_nuevo_' . date('Y-m-d_His') . '.xlsx');

        $dir = dirname($salida);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        (new Xlsx($spreadsheet))->save($salida);

        $this->info("Excel (nuevo esquema) generado en: {$salida}");

        return self::SUCCESS;
    }

    private function filasTractivos($db, $catalogo, $tiposEquipos, $tiposCombustibles, $estadosComponentes, $entidades, $motoresSerie, $cajasSerie, $diferencialesCodigo, $lubricantes): array
    {
        $filas = $db->table('tractivos')->whereNull('deleted_at')->orderBy('id')->get();

        $salida = [];
        foreach ($filas as $f) {
            $salida[] = [
                'id' => $f->id,
                'codigo' => $f->codigo,
                'placa' => $f->placa,
                'clase' => 'Tractor',
                'id_tipo_vehiculo' => $f->id_tipo_vehiculo,
                'marca' => $f->marca,
                'modelo' => $f->modelo,
                'tipo_equipo' => $tiposEquipos[$f->id_tipo_equipo] ?? null,
                'tipo_combustible' => $tiposCombustibles[$f->id_tipo_combustible] ?? null,
                'color_primario' => $catalogo[$f->id_color_primario] ?? null,
                'color_secundario' => $catalogo[$f->id_color_secundario] ?? null,
                'grupo' => $catalogo[$f->id_grupo] ?? null,
                'tipo_servicio' => $catalogo[$f->id_tipo_servicio] ?? null,
                'estado_componente' => $estadosComponentes[$f->id_tipo_estado] ?? null,
                'entidad' => $entidades[$f->id_entidad] ?? null,
                'numero_motor' => $motoresSerie[$f->id_motor] ?? null,
                'numero_caja' => $cajasSerie[$f->id_caja] ?? null,
                'codigo_diferencial' => $diferencialesCodigo[$f->id_diferencial] ?? null,
                'lubricante_hidraulico' => $lubricantes[$f->id_lubricante_hidraulico] ?? null,
                'vin' => $f->vin,
                'numero_chasis' => $f->numero_chasis,
                'capacidad_toneladas' => $f->capacidad_toneladas,
                'tara' => $f->tara,
                'cap_deposito' => $f->cap_deposito,
                'cap_hidraulico' => $f->cap_hidraulico,
                'cta_combustible' => $f->cta_combustible,
                'indice_consumo' => $f->indice_consumo,
                'indice_aceite' => $f->indice_aceite,
                'kilometraje_actual' => $f->kilometraje_actual,
                'kms_disp' => $f->kms_disp,
                'kms_plan_mtto' => $f->kms_plan_mtto,
                'gps' => $f->gps,
                'anno' => $f->anno,
                'estado' => $f->estado,
                'fecha_alta' => $f->fecha_alta,
                'fecha_baja' => $f->fecha_baja,
                'color_texto' => $f->color,
            ];
        }

        return $salida;
    }

    private function filasArrastres($db, $catalogo, $entidades): array
    {
        $filas = $db->table('arrastres')->whereNull('deleted_at')->orderBy('id')->get();

        $salida = [];
        foreach ($filas as $f) {
            $salida[] = [
                'id' => $f->id,
                'codigo' => $f->codigo,
                'placa' => $f->placa,
                'clase' => 'Arrastre',
                'id_tipo_vehiculo' => $f->id_tipo_vehiculo,
                'color_primario' => $catalogo[$f->id_color_primario] ?? null,
                'color_secundario' => $catalogo[$f->id_color_secundario] ?? null,
                'entidad' => $entidades[$f->id_entidad] ?? null,
                'tara' => $f->tara,
                'indice_aceite' => $f->indice_aceite,
                'estado' => $f->estado,
                'fecha_alta' => $f->fecha_alta,
                'fecha_baja' => $f->fecha_baja,
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
