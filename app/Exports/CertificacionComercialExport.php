<?php

namespace App\Exports;

use App\Services\ReportePrenominaService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * CERTIFICACION CHOFERES AREA COMERCIAL (Excel) — legacy
 * Reportes2::pdf_chofer_certificacion_excel (reporte #1068).
 * Reutiliza ReportePrenominaService::certificacionComercial.
 */
class CertificacionComercialExport implements FromArray, WithHeadings
{
    public function __construct(
        private int $mes,
        private int $ano,
        private ?int $entidadId,
    ) {}

    public function headings(): array
    {
        return [
            'VERSAT', 'CHOFER', 'CP', 'VIAJES PLAN', 'VIAJES REAL', '%',
            'TNS PLAN', 'TNS REAL', '%', 'INGRESO PLAN', 'INGRESO REAL', '%',
        ];
    }

    public function array(): array
    {
        $registros = app(ReportePrenominaService::class)
            ->certificacionComercial($this->mes, $this->ano, $this->entidadId)['registros'] ?? [];

        return array_map(fn ($r) => [
            $r['versat'], $r['nombrecompleto'], $r['nrocp'],
            $r['planviajes'], $r['viajes'], $r['cumplimientoviajes'],
            $r['plantns'], $r['tnreal'], $r['cumplimientotns'],
            $r['planmensual'], $r['produccion'], $r['cumplimiento'],
        ], $registros);
    }
}
