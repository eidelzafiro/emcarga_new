<?php

namespace App\Services\Reports\Fpdf;

use App\Services\Reports\Fpdf\Concerns\TecnicaBateriasParque;
use App\Services\Reports\Fpdf\Concerns\TecnicaGeneral;
use App\Services\Reports\Fpdf\Concerns\TecnicaNeumaticos;
use App\Services\Reports\Fpdf\Concerns\TecnicaTaller;

/**
 * Reportes del módulo TÉCNICA (Técnica, Baterías, Neumáticos y Control Taller)
 * replicando el layout exacto del legacy `Reportestec.php` con FPDF.
 */
class TecnicaFpdfReport extends DocumentosFpdfBase
{
    use TecnicaTaller, TecnicaGeneral, TecnicaBateriasParque, TecnicaNeumaticos;

    /** @var int[] */
    protected array $entidadIds;

    public function __construct(
        string $orientation,
        string $paper,
        ?object $entidad,
        bool $multiEntidad,
        string $fechaOperaciones,
        array $entidadIds = [],
    ) {
        parent::__construct($orientation, $paper, $entidad, $multiEntidad, $fechaOperaciones);
        $this->entidadIds = $entidadIds ?: [23];
    }

    /** Devuelve [anio, mes] desde 'MM', 'YYYY-MM' o vacío (sesión). */
    protected function anioMes(?string $mes): array
    {
        $fechaOps = $this->fechaOperaciones ?: now()->toDateString();
        $mes = trim((string) $mes);

        if ($mes === '' || $mes === 'TODOS') {
            return [(int) substr($fechaOps, 0, 4), (int) substr($fechaOps, 5, 2)];
        }
        if (strlen($mes) === 2) {
            return [(int) substr($fechaOps, 0, 4), (int) $mes];
        }
        if (strlen($mes) >= 7) {
            return [(int) substr($mes, 0, 4), (int) substr($mes, 5, 2)];
        }

        return [(int) substr($fechaOps, 0, 4), (int) substr($fechaOps, 5, 2)];
    }
}
