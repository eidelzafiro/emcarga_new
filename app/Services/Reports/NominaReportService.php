<?php

namespace App\Services\Reports;

use App\Models\Salario;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NominaReportService extends BaseReportService
{
    public function pdfPrenomina(Request $request): Response
    {
        $this->setTitle('Salario Prenómina');
        $this->setOrientation('landscape');

        $entidadId = (int) session('entidad_activa_id');
        $salarios = Salario::with(['bolsa', 'cargo', 'area'])
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->when($request->mes, fn ($q, $v) => $q->where('mes', $v))
            ->when($request->ano, fn ($q, $v) => $q->where('ano', $v))
            ->orderBy('numero_nomina')
            ->get();

        return $this->streamPdf('reports.pdf.nomina.prenomina', [
            'salarios' => $salarios,
        ], 'Salario_Prenomina.pdf');
    }

    public function pdfSalarioChoferes(Request $request): Response
    {
        $this->setTitle('Salario Choferes');
        $this->setOrientation('landscape');

        $entidadId = (int) session('entidad_activa_id');
        $salarios = Salario::with(['bolsa', 'cargo', 'area'])
            ->whereHas('cargo', fn ($q) => $q->where('es_chofer', true))
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->when($request->mes, fn ($q, $v) => $q->where('mes', $v))
            ->when($request->ano, fn ($q, $v) => $q->where('ano', $v))
            ->orderBy('numero_nomina')
            ->get();

        return $this->streamPdf('reports.pdf.nomina.prenomina', [
            'salarios' => $salarios,
        ], 'Salario_Choferes.pdf');
    }
}
