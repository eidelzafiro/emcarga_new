<?php

namespace App\Services\Reports;

use App\Models\Bolsa;
use App\Services\ReportePrenominaService;
use App\Services\Reports\Fpdf\PrenominaAdministrativoFpdfReport;
use App\Services\Reports\Fpdf\PrenominaTransportacionFpdfReport;
use App\Services\Reports\Fpdf\Modelo1ControlDiarioFpdfReport;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class NominaReportService extends BaseReportService
{
    public function pdfSalarioPrenomina(\Illuminate\Http\Request $request)
    {
        return $this->pdfPrenomina($request);
    }

    public function pdfPrenomina(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $tipo = $request->input('tipo', 'choferes'); // choferes | administrativo
        $entidadId = (int) session('entidad_activa_id') ?: null;

        if ($tipo === 'administrativo') {
            $report = new PrenominaAdministrativoFpdfReport($entidadId, (string) $mes, (string) $ano);
        } else {
            $report = new PrenominaTransportacionFpdfReport($entidadId, (string) $mes, (string) $ano);
        }

        $pdfContent = $report->generate();
        $nombre = 'prenomina_' . $tipo . '_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"');
    }

    public function excelPrenomina(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $tipo = $request->input('tipo', 'choferes');

        $prenominaService = app(ReportePrenominaService::class);

        if ($tipo === 'administrativo') {
            $data = $prenominaService->prenominaAdministrativo($mes, $ano);
            return $this->exportarExcelAdministrativo($data, $mes, $ano);
        } else {
            $data = $prenominaService->prenominaChoferes($mes, $ano);
            return $this->exportarExcelChoferes($data, $mes, $ano);
        }
    }

    public function pdfSalarioChoferes(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $entidadId = (int) session('entidad_activa_id') ?: null;

        $report = new PrenominaTransportacionFpdfReport($entidadId, (string) $mes, (string) $ano);
        $pdfContent = $report->generate();

        $nombre = 'salario_choferes_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"');
    }

    public function pdfModelo1(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $idBolsa = $request->input('id_bolsa') ? (int) $request->input('id_bolsa') : null;
        $entidadId = (int) session('entidad_activa_id') ?: null;

        $report = new Modelo1ControlDiarioFpdfReport($entidadId, (string) $mes, (string) $ano, $idBolsa);
        $pdfContent = $report->generate();

        $nombre = 'modelo1_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT);
        if ($idBolsa) $nombre .= '_chofer_' . $idBolsa;

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '.pdf"');
    }

    public function excelModelo1(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $idBolsa = $request->input('id_bolsa') ? (int) $request->input('id_bolsa') : null;

        $prenominaService = app(ReportePrenominaService::class);
        $data = $prenominaService->modelo1($mes, $ano, $idBolsa);

        return $this->exportarExcelModelo1($data, $mes, $ano);
    }

    private function exportarExcelChoferes(array $data, int $mes, int $ano): \Symfony\Component\HttpFoundation\Response
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prénómina Choferes');

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $sheet->setCellValue('A1', 'PRÉNÓMINA CHOFERES — ' . ($meses[$mes] ?? '') . ' ' . $ano);
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['#', 'CI', 'Chofer', 'Cargo', 'Tarifa', 'Horas', 'Ingresos', 'Salario CP', 'CLA', 'Nocturnidad', 'Feriados', 'Salario'];
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 3, $h);
            $sheet->getStyleByColumnAndRow($col, 3)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($col, 3)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
            $col++;
        }

        $row = 4;
        $i = 1;
        foreach ($data['registros'] as $reg) {
            $nocturnidad = $reg['impnocturnidad'] ?? 0;
            $sheet->setCellValueByColumnAndRow(1, $row, $i++);
            $sheet->setCellValueByColumnAndRow(2, $row, $reg['cidentidad'] ?? '');
            $sheet->setCellValueByColumnAndRow(3, $row, $reg['nombrecompleto'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $row, $reg['nombcargo'] ?? '');
            $sheet->setCellValueByColumnAndRow(5, $row, $reg['tasa'] ?? 0);
            $sheet->setCellValueByColumnAndRow(6, $row, $reg['ttotal'] ?? 0);
            $sheet->setCellValueByColumnAndRow(7, $row, $reg['strt'] ?? 0);
            $sheet->setCellValueByColumnAndRow(8, $row, $reg['sc'] ?? 0);
            $sheet->setCellValueByColumnAndRow(9, $row, $reg['impcla'] ?? 0);
            $sheet->setCellValueByColumnAndRow(10, $row, $nocturnidad);
            $sheet->setCellValueByColumnAndRow(11, $row, $reg['impferiados'] ?? 0);
            $sheet->setCellValueByColumnAndRow(12, $row, $reg['impreporte'] ?? 0);
            $row++;
        }

        $row++;
        $sheet->setCellValueByColumnAndRow(6, $row, 'TOTALES');
        $sheet->getStyleByColumnAndRow(6, $row)->getFont()->setBold(true);
        $sheet->setCellValueByColumnAndRow(6, $row + 1, count($data['registros']) . ' choferes');
        $sheet->setCellValueByColumnAndRow(7, $row, $data['totales']['strt'] ?? 0);
        $sheet->setCellValueByColumnAndRow(8, $row, $data['totales']['sc'] ?? 0);
        $sheet->setCellValueByColumnAndRow(9, $row, $data['totales']['impcla'] ?? 0);
        $sheet->setCellValueByColumnAndRow(10, $row, $data['totales']['impnocturnidad'] ?? 0);
        $sheet->setCellValueByColumnAndRow(11, $row, $data['totales']['impferiados'] ?? 0);
        $sheet->setCellValueByColumnAndRow(12, $row, $data['totales']['impreporte'] ?? 0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'prenomina_ch') . '.xlsx';
        $writer->save($tmp);

        $nombre = 'prenomina_choferes_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.xlsx';
        return response()->download($tmp, $nombre)->deleteFileAfterSend(true);
    }

    private function exportarExcelAdministrativo(array $data, int $mes, int $ano): \Symfony\Component\HttpFoundation\Response
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prénómina Administrativo');

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $sheet->setCellValue('A1', 'PRÉNÓMINA ADMINISTRATIVO — ' . ($meses[$mes] ?? '') . ' ' . $ano);
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['#', 'Empleado', 'Cargo', 'Área', 'Tarifa', 'Horas', 'Imp. Regular', 'Imp. Irregular', 'CLA', 'Nocturnidad', 'Incidencias', 'Salario'];
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 3, $h);
            $sheet->getStyleByColumnAndRow($col, 3)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($col, 3)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
            $col++;
        }

        $row = 4;
        $i = 1;
        $totImpRegular = 0;
        $totImpIrregular = 0;
        $totCla = 0;
        $totNocturnidad = 0;
        $totIncidencias = 0;
        $totSalario = 0;
        $totHoras = 0;

        foreach ($data['registros'] as $reg) {
            $horas = $reg['ttotal'] ?? 0;
            $sheet->setCellValueByColumnAndRow(1, $row, $i++);
            $sheet->setCellValueByColumnAndRow(2, $row, $reg['nombrecompleto'] ?? '');
            $sheet->setCellValueByColumnAndRow(3, $row, $reg['nombcargo'] ?? '');
            $sheet->setCellValueByColumnAndRow(4, $row, $reg['nombarea'] ?? '');
            $sheet->setCellValueByColumnAndRow(5, $row, $reg['tarifa'] ?? 0);
            $sheet->setCellValueByColumnAndRow(6, $row, $horas);
            $sheet->setCellValueByColumnAndRow(7, $row, $reg['impregular'] ?? 0);
            $sheet->setCellValueByColumnAndRow(8, $row, $reg['impirregular'] ?? 0);
            $sheet->setCellValueByColumnAndRow(9, $row, $reg['impcla'] ?? 0);
            $sheet->setCellValueByColumnAndRow(10, $row, $reg['impnocturnidad'] ?? 0);
            $sheet->setCellValueByColumnAndRow(11, $row, ($reg['impbaja'] ?? 0) + ($reg['implicencia'] ?? 0) + ($reg['impprestamo'] ?? 0));
            $sheet->setCellValueByColumnAndRow(12, $row, $reg['impreporte'] ?? 0);
            $row++;

            $totHoras += $horas;
            $totImpRegular += $reg['impregular'] ?? 0;
            $totImpIrregular += $reg['impirregular'] ?? 0;
            $totCla += $reg['impcla'] ?? 0;
            $totNocturnidad += $reg['impnocturnidad'] ?? 0;
            $totIncidencias += ($reg['impbaja'] ?? 0) + ($reg['implicencia'] ?? 0) + ($reg['impprestamo'] ?? 0);
            $totSalario += $reg['impreporte'] ?? 0;
        }

        $row++;
        $sheet->setCellValueByColumnAndRow(2, $row, 'TOTALES');
        $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true);
        $sheet->setCellValueByColumnAndRow(2, $row + 1, count($data['registros']) . ' empleados');
        $sheet->setCellValueByColumnAndRow(6, $row, $totHoras);
        $sheet->setCellValueByColumnAndRow(7, $row, $totImpRegular);
        $sheet->setCellValueByColumnAndRow(8, $row, $totImpIrregular);
        $sheet->setCellValueByColumnAndRow(9, $row, $totCla);
        $sheet->setCellValueByColumnAndRow(10, $row, $totNocturnidad);
        $sheet->setCellValueByColumnAndRow(11, $row, $totIncidencias);
        $sheet->setCellValueByColumnAndRow(12, $row, $totSalario);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'prenomina_ad') . '.xlsx';
        $writer->save($tmp);

        $nombre = 'prenomina_administrativo_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.xlsx';
        return response()->download($tmp, $nombre)->deleteFileAfterSend(true);
    }

    private function exportarExcelModelo1(array $data, int $mes, int $ano): \Symfony\Component\HttpFoundation\Response
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Modelo 1');

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $titulo = $data['titulo'] ?? 'MODELO 1';
        $sheet->setCellValue('A1', $titulo . ' — ' . ($meses[$mes] ?? '') . ' ' . $ano);
        $sheet->mergeCells('A1:Q1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['#', 'Chofer', 'Fecha', 'CP', 'Cliente', 'Origen', 'Destino', 'Producto',
            'Tipo Carga', 'KMs', 'Toneladas', 'Tiempo', 'Ingreso', 'Tasa', 'Salario', 'Feriado', 'Doble'];
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 3, $h);
            $sheet->getStyleByColumnAndRow($col, 3)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($col, 3)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
            $col++;
        }

        $row = 4;
        $i = 1;
        $granKm = 0;
        $granTn = 0;
        $granIngreso = 0;
        $granSalario = 0;

        foreach ($data['por_chofer'] as $chofer) {
            foreach ($chofer['registros'] as $reg) {
                if ($i === 1 || $sheet->getCellByColumnAndRow(2, $row - 1)->getValue() !== $chofer['nombre']) {
                    $sheet->setCellValueByColumnAndRow(2, $row, $chofer['nombre']);
                    $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true);
                }
                $tasa = $reg['tasa_nombre'] ?: '';
                if ($reg['tasa_valor']) $tasa .= ' (' . $reg['tasa_valor'] . ')';

                $sheet->setCellValueByColumnAndRow(1, $row, $i++);
                $sheet->setCellValueByColumnAndRow(3, $row, $reg['fecha']);
                $sheet->setCellValueByColumnAndRow(4, $row, $reg['nro_cp']);
                $sheet->setCellValueByColumnAndRow(5, $row, $reg['cliente']);
                $sheet->setCellValueByColumnAndRow(6, $row, $reg['origen']);
                $sheet->setCellValueByColumnAndRow(7, $row, $reg['destino']);
                $sheet->setCellValueByColumnAndRow(8, $row, $reg['producto']);
                $sheet->setCellValueByColumnAndRow(9, $row, $reg['tipo_carga']);
                $sheet->setCellValueByColumnAndRow(10, $row, $reg['km_total']);
                $sheet->setCellValueByColumnAndRow(11, $row, $reg['tn_real']);
                $sheet->setCellValueByColumnAndRow(12, $row, $reg['tiempo_total']);
                $sheet->setCellValueByColumnAndRow(13, $row, $reg['ingreso']);
                $sheet->setCellValueByColumnAndRow(14, $row, $tasa);
                $sheet->setCellValueByColumnAndRow(15, $row, $reg['salario']);
                $sheet->setCellValueByColumnAndRow(16, $row, $reg['es_feriado'] ? 'X' : '');
                $sheet->setCellValueByColumnAndRow(17, $row, $reg['doble_chofer'] ? 'X' : '');
                $row++;
            }

            // Subtotal chofer
            $tot = $chofer['totales'];
            $sheet->setCellValueByColumnAndRow(2, $row, 'Subtotal ' . $chofer['nombre']);
            $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true);
            $sheet->setCellValueByColumnAndRow(10, $row, $tot['km_total']);
            $sheet->setCellValueByColumnAndRow(11, $row, $tot['toneladas']);
            $sheet->setCellValueByColumnAndRow(13, $row, $tot['ingresos']);
            $sheet->setCellValueByColumnAndRow(15, $row, $tot['salario_cp']);
            $row++;

            $granKm += $tot['km_total'];
            $granTn += $tot['toneladas'];
            $granIngreso += $tot['ingresos'];
            $granSalario += $tot['salario_cp'];
        }

        // Gran total
        $row++;
        $sheet->setCellValueByColumnAndRow(2, $row, 'GRAN TOTAL');
        $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true)->setSize(12);
        $sheet->setCellValueByColumnAndRow(10, $row, $granKm);
        $sheet->setCellValueByColumnAndRow(11, $row, $granTn);
        $sheet->setCellValueByColumnAndRow(13, $row, $granIngreso);
        $sheet->setCellValueByColumnAndRow(15, $row, $granSalario);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'modelo1') . '.xlsx';
        $writer->save($tmp);

        $nombre = 'modelo1_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.xlsx';
        return response()->download($tmp, $nombre)->deleteFileAfterSend(true);
    }
}
