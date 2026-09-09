<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * DATOS P/NOMINAS (ADICIONALES) — réplica FPDF del legacy.
 * Reportes.php:4764 (pdf_salario_prenomina_adicional) +
 * ModSalarioAdmin::mostrar_salario_emcarga (sistema de pago = 1).
 *
 * Muestra SOLO trabajadores administrativos con pagos adicionales
 * (padicionales2 > 0). Columnas: EXP, NOMBRE DEL TRABAJADOR, NOCTURNIDAD,
 * DOBLAJE, FERIADO, CLA, MAESTRIAS, OTRAS y TOTAL. Agrupa por área con
 * subtotal "TOTAL {area}" y cierra con "TOTAL GENERAL".
 *
 * Formato: Letter horizontal. El EXP usa `bolsa.versat` (el legacy re-aliasa
 * versat como nronomina); `padicionales2` = adicionales + CLA (replica
 * ModSalarioAdmin:968-970).
 */
class AdicionalesFpdfReport extends ReportesnewFpdfBase
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Letter');
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->prenominaAdministrativo((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'DATOS P/NOMINAS (ADICIONALES)';

        $vcampo = 25;
        $campos = [
            ['titulo' => 'EXP',                  'ancho' => $vcampo, 'direccion' => 'C'],
            ['titulo' => 'NOMBRE DEL TRABAJADOR', 'ancho' => 60, 'direccion' => 'C', 'letra' => 10],
            ['titulo' => 'NOCTURNIDAD',          'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'DOBLAJE',              'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'FERIADO',              'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'CLA',                  'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'MAESTRIAS',            'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'OTRAS',                'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'TOTAL',                'ancho' => 25, 'direccion' => 'C'],
        ];

        // Solo registros con pagos adicionales (padicionales2 > 0).
        $registros = array_filter(
            $data['registros'] ?? [],
            fn ($r) => ((float) ($r['padicionales2'] ?? 0)) > 0
        );
        $registros = array_values($registros);

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 35);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->titulos($campos, [], [], 6, 10, 30);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');

        $posY = 36;
        $max = 170;
        $i = 0;
        $area = null;

        $impNoct = 0; $impDob = 0; $impFeri = 0; $impCla = 0; $impMaest = 0; $impOtras = 0; $padic = 0;
        $tNoct = 0; $tDob = 0; $tFeri = 0; $tCla = 0; $tMaest = 0; $tOtras = 0; $tPadic = 0;

        foreach ($registros as $arr) {
            $areaActual = $arr['nombarea'] ?? 'Sin área';

            if ($area !== null && $area !== $areaActual) {
                $this->renderSubtotal($area, $impNoct, $impDob, $impFeri, $impCla, $impMaest, $impOtras, $padic, $vcampo, $posY);
                $posY += 6;
                $i++;
                $impNoct = 0; $impDob = 0; $impFeri = 0; $impCla = 0; $impMaest = 0; $impOtras = 0; $padic = 0;
            }
            $area = $areaActual;

            if ($posY >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 6, 10, 30);
                $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');
                $posY = 36;
                $i = 0;
            }

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', '', 10);
            $this->Cell($vcampo, 6, $arr['versat'] ?? '', 1, 0, 'C', 1);
            $this->Cell(60, 6, $this->latin1(mb_strtoupper(mb_strtolower($arr['nombrecompleto'] ?? ''))), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 12);
            $this->Cell(30, 6, $this->fmtVar($arr['impnocturnidad'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($arr['impdoblaje'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($arr['impferiados'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($arr['impcla'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($arr['impmaestrias'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($arr['impotras'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($arr['padicionales2'] ?? 0, 2), 1, 0, 'R', 1);

            $posY += 6;
            $i++;

            $impNoct += (float) ($arr['impnocturnidad'] ?? 0);
            $impDob += (float) ($arr['impdoblaje'] ?? 0);
            $impFeri += (float) ($arr['impferiados'] ?? 0);
            $impCla += (float) ($arr['impcla'] ?? 0);
            $impMaest += (float) ($arr['impmaestrias'] ?? 0);
            $impOtras += (float) ($arr['impotras'] ?? 0);
            $padic += (float) ($arr['padicionales2'] ?? 0);

            $tNoct += (float) ($arr['impnocturnidad'] ?? 0);
            $tDob += (float) ($arr['impdoblaje'] ?? 0);
            $tFeri += (float) ($arr['impferiados'] ?? 0);
            $tCla += (float) ($arr['impcla'] ?? 0);
            $tMaest += (float) ($arr['impmaestrias'] ?? 0);
            $tOtras += (float) ($arr['impotras'] ?? 0);
            $tPadic += (float) ($arr['padicionales2'] ?? 0);
        }

        if ($area !== null) {
            $this->renderSubtotal($area, $impNoct, $impDob, $impFeri, $impCla, $impMaest, $impOtras, $padic, $vcampo, $posY);
            $posY += 6;
        }

        // Total general.
        $this->SetXY(10, $posY);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($vcampo + 60, 10, 'TOTAL GENERAL', 1, 0, 'L', 1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(30, 10, $this->fmtVar($tNoct, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($tDob, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($tFeri, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tCla, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($tMaest, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($tOtras, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($tPadic, 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function renderSubtotal(string $area, float $impNoct, float $impDob, float $impFeri, float $impCla, float $impMaest, float $impOtras, float $padic, int $vcampo, int $posY): void
    {
        $this->SetXY(10, $posY);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($vcampo + 60, 6, $this->latin1('TOTAL '.$area), 1, 0, 'L', 1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(30, 6, $this->fmtVar($impNoct, 2), 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->fmtVar($impDob, 2), 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->fmtVar($impFeri, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->fmtVar($impCla, 2), 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->fmtVar($impMaest, 2), 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->fmtVar($impOtras, 2), 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->fmtVar($padic, 2), 1, 0, 'R', 1);
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
