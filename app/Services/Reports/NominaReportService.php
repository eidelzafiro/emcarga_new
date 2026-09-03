<?php

namespace App\Services\Reports;

use App\Models\Bolsa;
use App\Services\ReportePrenominaService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class NominaReportService
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

        $pdf = Pdf::loadView('reports.pdf.prenomina', [
            'data' => $data,
            'tipo' => $tipo,
        ]);

        $nombre = 'prenomina_' . $tipo . '_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.pdf';
        return $pdf->download($nombre);
    }

    public function pdfSalarioChoferes(\Illuminate\Http\Request $request)
    {
        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));

        $prenominaService = app(ReportePrenominaService::class);
        $data = $prenominaService->prenominaChoferes($mes, $ano);

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

        $pdf = Pdf::loadView('reports.pdf.modelo1', [
            'data' => $data,
        ]);

        $nombre = 'modelo1_' . $ano . '_' . str_pad($mes, 2, '0', STR_PAD_LEFT);
        if ($idBolsa) $nombre .= '_chofer_' . $idBolsa;
        return $pdf->download($nombre . '.pdf');
    }
}
