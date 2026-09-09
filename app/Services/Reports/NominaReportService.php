<?php

namespace App\Services\Reports;

use App\Models\Bolsa;
use App\Services\ReportePrenominaService;
use App\Services\Reports\Fpdf\PrenominaAdministrativoFpdfReport;
use App\Services\Reports\Fpdf\PrenominaTransportacionFpdfReport;
use App\Services\Reports\Fpdf\Modelo1ControlDiarioFpdfReport;
use App\Services\Reports\Fpdf\CumpleanosFpdfReport;
use App\Services\Reports\Fpdf\LicenciaConduccionFpdfReport;
use App\Services\Reports\Fpdf\AdicionalesFpdfReport;
use App\Services\Reports\Fpdf\IncidenciasFpdfReport;
use App\Services\Reports\Fpdf\NocturnidadFpdfReport;
use App\Services\Reports\Fpdf\PagoAdministrativoFpdfReport;
use App\Services\Reports\Fpdf\ResumenTiemposChoferesFpdfReport;
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

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $titulo = $tipo === 'administrativo'
            ? 'DATOS P/NOMINAS SALARIO ADMINISTRATIVO'
            : 'PRENOMINA SALARIO TRANSPORTACION';

        if ($tipo === 'administrativo') {
            $report = new PrenominaAdministrativoFpdfReport($entidadId, $mesPad, (string) $ano);
        } else {
            $report = new PrenominaTransportacionFpdfReport($entidadId, $mesPad, (string) $ano);
        }

        $pdfContent = $report->generate();
        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad.'.pdf';

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

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $titulo = 'PRENOMINA SALARIO TRANSPORTACION';

        $report = new PrenominaTransportacionFpdfReport($entidadId, $mesPad, (string) $ano);
        $pdfContent = $report->generate();

        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad.'.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"');
    }

    public function pdfLicenciaConduccion(\Illuminate\Http\Request $request)
    {
        $entidadId = (int) session('entidad_activa_id') ?: null;
        $mesPad = str_pad((string) (int) $request->input('mes', now()->format('m')), 2, '0', STR_PAD_LEFT);
        $ano = (string) $request->input('ano', now()->format('Y'));
        $titulo = 'LISTADO PERSONAL CON LICENCIA CONDUCCION';

        $report = new LicenciaConduccionFpdfReport($entidadId, $mesPad, $ano);
        $pdfContent = $report->generate();

        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad.'.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"');
    }

    public function pdfCumpleanos(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $entidadId = (int) session('entidad_activa_id') ?: null;

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $titulo = 'LISTADO DE CUMPLEAÑOS DEL MES';

        $report = new CumpleanosFpdfReport($entidadId, $mesPad, (string) $ano);
        $pdfContent = $report->generate();

        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad.'.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"');
    }

    public function pdfAdicionales(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $entidadId = (int) session('entidad_activa_id') ?: null;

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $titulo = 'DATOS P/NOMINAS (ADICIONALES)';

        $report = new AdicionalesFpdfReport($entidadId, $mesPad, (string) $ano);
        $pdfContent = $report->generate();

        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad.'.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"');
    }

    public function pdfNocturnidad(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $entidadId = (int) session('entidad_activa_id') ?: null;

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $titulo = 'DATOS P/NOMINAS (NOCTURNIDAD)';

        $report = new NocturnidadFpdfReport($entidadId, $mesPad, (string) $ano);
        $pdfContent = $report->generate();

        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad.'.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"');
    }

    public function pdfPagoAdministrativo(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $entidadId = (int) session('entidad_activa_id') ?: null;

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $titulo = 'DATOS P/NOMINAS PAGO ADMINISTRATIVO';

        $report = new PagoAdministrativoFpdfReport($entidadId, $mesPad, (string) $ano);
        $pdfContent = $report->generate();

        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad.'.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"');
    }

    public function pdfResumenTiemposChoferes(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $entidadId = (int) session('entidad_activa_id') ?: null;

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $titulo = 'MODELO RESUMEN DE LOS TIEMPOS CHOFERES TRANSPORTACION';

        $report = new ResumenTiemposChoferesFpdfReport($entidadId, $mesPad, (string) $ano);
        $pdfContent = $report->generate();

        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad.'.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"');
    }

    public function pdfIncidencias(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $origenId = (int) $request->input('tipo_incidencia', 1);
        $entidadId = (int) session('entidad_activa_id') ?: null;

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $titulo = 'PRENOMINA INCIDENCIAS AL TIEMPO TRABAJADO';

        $report = new IncidenciasFpdfReport($entidadId, $mesPad, (string) $ano, $origenId);
        $pdfContent = $report->generate();

        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad.'.pdf';

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

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $titulo = 'MODELO 1 CONTROL DIARIO DE LAS TRANSPORTACIONES';

        $report = new Modelo1ControlDiarioFpdfReport($entidadId, $mesPad, (string) $ano, $idBolsa);
        $pdfContent = $report->generate();

        $nombre = $this->safeName($titulo).'_'.$ano.'-'.$mesPad;
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
        $sheet->setTitle('Prenomina Choferes');

        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $sheet->setCellValue('A1', 'PRENOMINA SALARIO TRANSPORTACION — ' . ($meses[$mes] ?? '') . ' ' . $ano);
        $sheet->mergeCells('A1:Q1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['EXP', 'Chofer', 'TH', 'TRT', 'Escala', 'CLA', 'STRT', 'Ingresos',
            'Fondo', 'IS Inicial', 'Pen %', 'Pen $', 'IS Final', 'SD', 'FT', 'Salario', 'DT'];
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 3, $h);
            $sheet->getStyleByColumnAndRow($col, 3)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($col, 3)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
            $col++;
        }

        $row = 4;
        foreach ($data['registros'] as $reg) {
            $sheet->setCellValueByColumnAndRow(1, $row, $reg['exp'] ?? '');
            $sheet->setCellValueByColumnAndRow(2, $row, $reg['nombrecompleto'] ?? '');
            $sheet->setCellValueByColumnAndRow(3, $row, $reg['tarifa'] ?? 0);
            $sheet->setCellValueByColumnAndRow(4, $row, $reg['ttotal'] ?? 0);
            $sheet->setCellValueByColumnAndRow(5, $row, $reg['escala'] ?? 0);
            $sheet->setCellValueByColumnAndRow(6, $row, $reg['cla'] ?? 0);
            $sheet->setCellValueByColumnAndRow(7, $row, $reg['strt'] ?? 0);
            $sheet->setCellValueByColumnAndRow(8, $row, $reg['ingresos'] ?? 0);
            $sheet->setCellValueByColumnAndRow(9, $row, $reg['fondo'] ?? 0);
            $sheet->setCellValueByColumnAndRow(10, $row, $reg['is_inicial'] ?? 0);
            $sheet->setCellValueByColumnAndRow(11, $row, $reg['pen_resultado'] ?? '');
            $sheet->setCellValueByColumnAndRow(12, $row, $reg['pen_importe'] ?? 0);
            $sheet->setCellValueByColumnAndRow(13, $row, $reg['is_final'] ?? 0);
            $sheet->setCellValueByColumnAndRow(14, $row, $reg['sd'] ?? 0);
            $sheet->setCellValueByColumnAndRow(15, $row, $reg['ft'] ?? 0);
            $sheet->setCellValueByColumnAndRow(16, $row, $reg['salario'] ?? 0);
            $sheet->setCellValueByColumnAndRow(17, $row, $reg['dt'] ?? 0);
            $row++;
        }

        $row++;
        $sheet->setCellValueByColumnAndRow(2, $row, 'TOTALES');
        $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true);
        $tot = $data['totales'];
        $sheet->setCellValueByColumnAndRow(4, $row, $tot['ttotal'] ?? 0);
        $sheet->setCellValueByColumnAndRow(5, $row, $tot['escala'] ?? 0);
        $sheet->setCellValueByColumnAndRow(6, $row, $tot['cla'] ?? 0);
        $sheet->setCellValueByColumnAndRow(7, $row, $tot['strt'] ?? 0);
        $sheet->setCellValueByColumnAndRow(8, $row, $tot['ingresos'] ?? 0);
        $sheet->setCellValueByColumnAndRow(9, $row, $tot['fondo'] ?? 0);
        $sheet->setCellValueByColumnAndRow(10, $row, $tot['is_inicial'] ?? 0);
        $sheet->setCellValueByColumnAndRow(12, $row, $tot['pen_importe'] ?? 0);
        $sheet->setCellValueByColumnAndRow(13, $row, $tot['is_final'] ?? 0);
        $sheet->setCellValueByColumnAndRow(14, $row, $tot['sd'] ?? 0);
        $sheet->setCellValueByColumnAndRow(15, $row, $tot['ft'] ?? 0);
        $sheet->setCellValueByColumnAndRow(16, $row, $tot['salario'] ?? 0);
        $sheet->setCellValueByColumnAndRow(17, $row, $tot['dt'] ?? 0);

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
        $sheet->mergeCells('A1:S1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['#', 'Chofer', 'F. Inicio', 'F. Term', 'Equipo', 'HR', 'CP', 'KMS',
            'TNS', 'T. Otros', 'T.M', 'T.C', 'T.D', 'T. Total', 'Escala', 'Tasa CLA',
            'Valor CLA', 'Salario X TRT', 'Ingresos', 'Tasa', 'Fondo', 'Nota'];
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 3, $h);
            $sheet->getStyleByColumnAndRow($col, 3)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($col, 3)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
            $col++;
        }

        $notas = [
            1 => 'DOBLE CHOFER',
            2 => 'FERIADO',
            3 => 'DC+FERIADO',
            4 => 'VACACIONES',
            5 => 'ALM',
        ];

        $row = 4;
        $i = 1;
        $gran = array_fill_keys(['kms', 'tons', 'tperm', 'tmov', 'tcarga', 'tdescarga', 'ttotal',
            'saltrt', 'impcla', 'saltrtcla', 'ingresos', 'salario'], 0);

        foreach ($data['por_chofer'] as $chofer) {
            foreach ($chofer['registros'] as $reg) {
                if ($i === 1 || $sheet->getCellByColumnAndRow(2, $row - 1)->getValue() !== $chofer['nombre']) {
                    $sheet->setCellValueByColumnAndRow(2, $row, $chofer['nombre']);
                    $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true);
                }
                $nota = $reg['ajuste'] > 0 ? ($notas[$reg['ajuste']] ?? '') : '';

                $sheet->setCellValueByColumnAndRow(1, $row, $i++);
                $sheet->setCellValueByColumnAndRow(3, $row, ($reg['fcarga'] ?? '') . '-' . ($reg['hcarga1'] ?? ''));
                $sheet->setCellValueByColumnAndRow(4, $row, ($reg['fdescarga'] ?? '') . '-' . ($reg['hdescarga1'] ?? ''));
                $sheet->setCellValueByColumnAndRow(5, $row, ($reg['equipo'] ?? '') . '(' . ($reg['capacidad'] ?? '') . ')');
                $sheet->setCellValueByColumnAndRow(6, $row, $reg['nro_hr']);
                $sheet->setCellValueByColumnAndRow(7, $row, $reg['nro_cp']);
                $sheet->setCellValueByColumnAndRow(8, $row, $reg['kmcarga']);
                $sheet->setCellValueByColumnAndRow(9, $row, $reg['tnreal']);
                $sheet->setCellValueByColumnAndRow(10, $row, $reg['tperm']);
                $sheet->setCellValueByColumnAndRow(11, $row, $reg['tmov']);
                $sheet->setCellValueByColumnAndRow(12, $row, $reg['tcarga']);
                $sheet->setCellValueByColumnAndRow(13, $row, $reg['tdescarga']);
                $sheet->setCellValueByColumnAndRow(14, $row, $reg['ttotal']);
                $sheet->setCellValueByColumnAndRow(15, $row, $reg['saltrt']);
                $sheet->setCellValueByColumnAndRow(16, $row, $reg['tarcla']);
                $sheet->setCellValueByColumnAndRow(17, $row, $reg['impcla']);
                $sheet->setCellValueByColumnAndRow(18, $row, $reg['saltrtcla']);
                $sheet->setCellValueByColumnAndRow(19, $row, $reg['ingresos']);
                $sheet->setCellValueByColumnAndRow(20, $row, $reg['tasa']);
                $sheet->setCellValueByColumnAndRow(21, $row, $reg['salario']);
                $sheet->setCellValueByColumnAndRow(22, $row, $nota);
                $row++;
            }

            // Subtotal chofer
            $tot = $chofer['totales'];
            $sheet->setCellValueByColumnAndRow(2, $row, 'Subtotal ' . $chofer['nombre']);
            $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true);
            $sheet->setCellValueByColumnAndRow(8, $row, $tot['kms']);
            $sheet->setCellValueByColumnAndRow(9, $row, $tot['tons']);
            $sheet->setCellValueByColumnAndRow(10, $row, $tot['tperm']);
            $sheet->setCellValueByColumnAndRow(11, $row, $tot['tmov']);
            $sheet->setCellValueByColumnAndRow(12, $row, $tot['tcarga']);
            $sheet->setCellValueByColumnAndRow(13, $row, $tot['tdescarga']);
            $sheet->setCellValueByColumnAndRow(14, $row, $tot['ttotal']);
            $sheet->setCellValueByColumnAndRow(15, $row, $tot['saltrt']);
            $sheet->setCellValueByColumnAndRow(17, $row, $tot['impcla']);
            $sheet->setCellValueByColumnAndRow(18, $row, $tot['saltrtcla']);
            $sheet->setCellValueByColumnAndRow(19, $row, $tot['ingresos']);
            $sheet->setCellValueByColumnAndRow(21, $row, $tot['salario']);
            $row++;

            foreach (array_keys($gran) as $k) {
                $gran[$k] += $tot[$k] ?? 0;
            }
        }

        // Gran total
        $row++;
        $sheet->setCellValueByColumnAndRow(2, $row, 'GRAN TOTAL');
        $sheet->getStyleByColumnAndRow(2, $row)->getFont()->setBold(true)->setSize(12);
        $sheet->setCellValueByColumnAndRow(8, $row, $gran['kms']);
        $sheet->setCellValueByColumnAndRow(9, $row, $gran['tons']);
        $sheet->setCellValueByColumnAndRow(14, $row, $gran['ttotal']);
        $sheet->setCellValueByColumnAndRow(15, $row, $gran['saltrt']);
        $sheet->setCellValueByColumnAndRow(17, $row, $gran['impcla']);
        $sheet->setCellValueByColumnAndRow(18, $row, $gran['saltrtcla']);
        $sheet->setCellValueByColumnAndRow(19, $row, $gran['ingresos']);
        $sheet->setCellValueByColumnAndRow(21, $row, $gran['salario']);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'modelo1') . '.xlsx';
        $writer->save($tmp);

        $nombre = 'modelo1_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.xlsx';
        return response()->download($tmp, $nombre)->deleteFileAfterSend(true);
    }
}
