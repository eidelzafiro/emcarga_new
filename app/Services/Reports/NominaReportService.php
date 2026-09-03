<?php

namespace App\Services\Reports;

use App\Models\Bolsa;
use App\Services\ReportePrenominaService;
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

        $prenominaService = app(ReportePrenominaService::class);

        if ($tipo === 'administrativo') {
            $data = $prenominaService->prenominaAdministrativo($mes, $ano);
        } else {
            $data = $prenominaService->prenominaChoferes($mes, $ano);
        }

        $this->setTitle('Prénómina ' . ($tipo === 'administrativo' ? 'Administrativo' : 'Choferes'));

        $pdf = Pdf::loadView('reports.pdf.prenomina', [
            'data' => $data,
            'tipo' => $tipo,
        ]);

        $nombre = 'prenomina_' . $tipo . '_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.pdf';
        return $pdf->download($nombre);
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

        $prenominaService = app(ReportePrenominaService::class);
        $data = $prenominaService->prenominaChoferes($mes, $ano);

        $this->setTitle('Salario Choferes');

        $pdf = Pdf::loadView('reports.pdf.prenomina', [
            'data' => $data,
            'tipo' => 'choferes',
        ]);

        $nombre = 'salario_choferes_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.pdf';
        return $pdf->download($nombre);
    }

    public function pdfModelo1(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $idBolsa = $request->input('id_bolsa') ? (int) $request->input('id_bolsa') : null;

        $prenominaService = app(ReportePrenominaService::class);
        $data = $prenominaService->modelo1($mes, $ano, $idBolsa);

        $this->setTitle('Modelo 1 - Control Transportaciones');

        $pdf = Pdf::loadView('reports.pdf.modelo1', [
            'data' => $data,
        ]);

        $nombre = 'modelo1_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT);
        if ($idBolsa) $nombre .= '_chofer_' . $idBolsa;
        return $pdf->download($nombre . '.pdf');
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

        // Header
        $sheet->setCellValue('A1', 'PRÉNÓMINA CHOFERES — ' . ($meses[$mes] ?? '') . ' ' . $ano);
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['#', 'CI', 'Chofer', 'Cargo', 'Tarifa', 'Tiempo', 'Básico', 'CLA', 'Nocturnidad', 'Incidencias', 'Salario'];
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
            $sheet->setCellValueByColumnAndRow(1, $row, $i++);
            $sheet->setCellValueByColumnAndRow(2, $row, $reg['ci']);
            $sheet->setCellValueByColumnAndRow(3, $row, $reg['nombre_completo']);
            $sheet->setCellValueByColumnAndRow(4, $row, $reg['cargo']);
            $sheet->setCellValueByColumnAndRow(5, $row, $reg['tarifa']);
            $sheet->setCellValueByColumnAndRow(6, $row, $reg['tiempo']);
            $sheet->setCellValueByColumnAndRow(7, $row, $reg['imp_basico']);
            $sheet->setCellValueByColumnAndRow(8, $row, $reg['imp_cla']);
            $sheet->setCellValueByColumnAndRow(9, $row, $reg['imp_nocturnidad']);
            $sheet->setCellValueByColumnAndRow(10, $row, $reg['imp_incidencias']);
            $sheet->setCellValueByColumnAndRow(11, $row, $reg['salario_final']);
            $row++;
        }

        // Totales
        $row++;
        $sheet->setCellValueByColumnAndRow(6, $row, 'TOTALES');
        $sheet->getStyleByColumnAndRow(6, $row)->getFont()->setBold(true);
        $sheet->setCellValueByColumnAndRow(7, $row, $data['totales']['total_basico'] ?? 0);
        $sheet->setCellValueByColumnAndRow(8, $row, $data['totales']['total_cla'] ?? 0);
        $sheet->setCellValueByColumnAndRow(9, $row, $data['totales']['total_nocturnidad'] ?? 0);
        $sheet->setCellValueByColumnAndRow(10, $row, $data['totales']['total_incidencias'] ?? 0);
        $sheet->setCellValueByColumnAndRow(11, $row, $data['totales']['total_salario'] ?? 0);

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
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['#', 'CI', 'Empleado', 'Cargo', 'Área', 'Tarifa', 'Tiempo', 'Básico', 'Nocturnidad', 'Incidencias', 'Salario'];
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
            $sheet->setCellValueByColumnAndRow(1, $row, $i++);
            $sheet->setCellValueByColumnAndRow(2, $row, $reg['ci']);
            $sheet->setCellValueByColumnAndRow(3, $row, $reg['nombre_completo']);
            $sheet->setCellValueByColumnAndRow(4, $row, $reg['cargo']);
            $sheet->setCellValueByColumnAndRow(5, $row, $reg['area']);
            $sheet->setCellValueByColumnAndRow(6, $row, $reg['tarifa']);
            $sheet->setCellValueByColumnAndRow(7, $row, $reg['tiempo']);
            $sheet->setCellValueByColumnAndRow(8, $row, $reg['imp_basico']);
            $sheet->setCellValueByColumnAndRow(9, $row, $reg['imp_nocturnidad']);
            $sheet->setCellValueByColumnAndRow(10, $row, $reg['imp_incidencias']);
            $sheet->setCellValueByColumnAndRow(11, $row, $reg['salario_final']);
            $row++;
        }

        $row++;
        $sheet->setCellValueByColumnAndRow(6, $row, 'TOTALES');
        $sheet->getStyleByColumnAndRow(6, $row)->getFont()->setBold(true);
        $sheet->setCellValueByColumnAndRow(7, $row, $data['totales']['total_tiempo'] ?? 0);
        $sheet->setCellValueByColumnAndRow(8, $row, $data['totales']['total_basico'] ?? 0);
        $sheet->setCellValueByColumnAndRow(9, $row, $data['totales']['total_nocturnidad'] ?? 0);
        $sheet->setCellValueByColumnAndRow(10, $row, $data['totales']['total_incidencias'] ?? 0);
        $sheet->setCellValueByColumnAndRow(11, $row, $data['totales']['total_salario'] ?? 0);

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

        $sheet->setCellValue('A1', 'MODELO 1 — CONTROL TRANSPORTACIONES — ' . ($meses[$mes] ?? '') . ' ' . $ano);
        $sheet->mergeCells('A1:P1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['#', 'Chofer', 'Fecha', 'CP', 'Cliente', 'Origen', 'Destino', 'Producto',
            'Tipo Carga', 'KMs', 'Toneladas', 'Tiempo', 'Ingreso', 'Tarifa 1', 'Flete', 'Salario'];
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 3, $h);
            $sheet->getStyleByColumnAndRow($col, 3)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($col, 3)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
            $col++;
        }

        $row = 4;
        $i = 1;
        $currentChofer = '';
        foreach ($data['choferes'] as $chofer) {
            foreach ($chofer['cartas_porte'] as $cp) {
                if ($currentChofer !== $chofer['nombre']) {
                    $currentChofer = $chofer['nombre'];
                    $sheet->setCellValueByColumnAndRow(2, $row, $chofer['nombre']);
                    $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true);
                }
                $sheet->setCellValueByColumnAndRow(1, $row, $i++);
                $sheet->setCellValueByColumnAndRow(3, $row, $cp['fecha']);
                $sheet->setCellValueByColumnAndRow(4, $row, $cp['numero_cp']);
                $sheet->setCellValueByColumnAndRow(5, $row, $cp['cliente']);
                $sheet->setCellValueByColumnAndRow(6, $row, $cp['origen']);
                $sheet->setCellValueByColumnAndRow(7, $row, $cp['destino']);
                $sheet->setCellValueByColumnAndRow(8, $row, $cp['producto']);
                $sheet->setCellValueByColumnAndRow(9, $row, $cp['tipo_carga']);
                $sheet->setCellValueByColumnAndRow(10, $row, $cp['kms']);
                $sheet->setCellValueByColumnAndRow(11, $row, $cp['toneladas']);
                $sheet->setCellValueByColumnAndRow(12, $row, $cp['tiempo']);
                $sheet->setCellValueByColumnAndRow(13, $row, $cp['ingreso']);
                $sheet->setCellValueByColumnAndRow(14, $row, $cp['tarifa1']);
                $sheet->setCellValueByColumnAndRow(15, $row, $cp['flete']);
                $sheet->setCellValueByColumnAndRow(16, $row, $cp['salario']);
                $row++;
            }
            // Subtotal chofer
            $sheet->setCellValueByColumnAndRow(2, $row, 'Subtotal ' . $chofer['nombre']);
            $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true);
            $sheet->setCellValueByColumnAndRow(10, $row, $chofer['total_kms']);
            $sheet->setCellValueByColumnAndRow(11, $row, $chofer['total_tn']);
            $sheet->setCellValueByColumnAndRow(13, $row, $chofer['total_ingreso']);
            $sheet->setCellValueByColumnAndRow(16, $row, $chofer['total_salario']);
            $row++;
        }

        // Gran total
        $row++;
        $sheet->setCellValueByColumnAndRow(2, $row, 'GRAN TOTAL');
        $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true)->setSize(12);
        $sheet->setCellValueByColumnAndRow(10, $row, $data['gran_total_kms'] ?? 0);
        $sheet->setCellValueByColumnAndRow(11, $row, $data['gran_total_tn'] ?? 0);
        $sheet->setCellValueByColumnAndRow(13, $row, $data['gran_total_ingreso'] ?? 0);
        $sheet->setCellValueByColumnAndRow(16, $row, $data['gran_total_salario'] ?? 0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'modelo1') . '.xlsx';
        $writer->save($tmp);

        $nombre = 'modelo1_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.xlsx';
        return response()->download($tmp, $nombre)->deleteFileAfterSend(true);
    }
}
