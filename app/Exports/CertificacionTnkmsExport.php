<?php

namespace App\Exports;

use App\Services\ReportePrenominaService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * CERTIFICACION CHOFERES AREA COMERCIAL TN-KM (Excel) — legacy
 * Reportes2::pdf_chofer_certificacion_tnskms_excel (reporte #1073).
 */
class CertificacionTnkmsExport implements FromArray, WithHeadings
{
    public function __construct(
        private int $mes,
        private int $ano,
        private ?int $entidadId,
    ) {}

    public function headings(): array
    {
        return ['VERSAT', 'CHOFER', '$ 1-30', '$ 31-190', '$ 191-350', '$ 351+', '$ TOTAL'];
    }

    public function array(): array
    {
        $registros = app(ReportePrenominaService::class)
            ->certificacionTnkms($this->mes, $this->ano, $this->entidadId)['registros'] ?? [];

        return array_map(fn ($r) => [
            $r['versat'], $r['nombrecompleto'], $r['t1'], $r['t2'], $r['t3'], $r['t4'], $r['total'],
        ], $registros);
    }
}
