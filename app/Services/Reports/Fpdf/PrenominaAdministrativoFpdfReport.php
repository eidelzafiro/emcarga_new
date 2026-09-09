<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * Prenómina Salario Administrativo — réplica FPDF del legacy.
 * Reportes.php:4283 (DATOS P/NOMINAS SALARIO ADMINISTRATIVO, mostrar_salario_emcarga).
 *
 * Columnas: No, Cod, NOMBRE, CARNET, CARGO, ESCALA, TARIFA, CLA, TIEMPO,
 * SALARIO ESCALA, SALARIO CLA, NOCTURNIDAD, TRABAJO EXTRA (tarifa/tiempo/salario),
 * MAESTRIA y SALARIO A DEVENGAR.
 *
 * Formato A4 horizontal; anchos reducidos respecto al legacy (Letter, 329mm)
 * para que las 17 columnas quepan en ~284mm.
 */
class PrenominaAdministrativoFpdfReport extends ReportesnewFpdfBase
{
    /** Anchos de las 17 columnas (suman ~284mm, caben en A4 landscape). */
    private const ANCHOS = [8, 16, 33, 20, 36, 17, 14, 9, 13, 16, 13, 17, 13, 15, 13, 13, 18];

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->prenominaAdministrativo((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'DATOS P/NOMINAS SALARIO ADMINISTRATIVO';

        if (empty($data['registros'])) {
            $this->inicio($titulo, 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->inicio($titulo, 50, 5);
        $a = self::ANCHOS;

        // Cabecera nivel 1 (agrupada).
        $h1 = [
            ['t' => 'No', 'w' => $a[0], 'c' => 'C'],
            ['t' => 'Cod', 'w' => $a[1], 'c' => 'C'],
            ['t' => 'NOMBRE Y APELLIDOS', 'w' => $a[2], 'c' => 'C'],
            ['t' => 'CARNET', 'w' => $a[3], 'c' => 'C'],
            ['t' => 'CARGO', 'w' => $a[4], 'c' => 'C'],
            ['t' => 'ESCALA', 'w' => $a[5], 'c' => 'C'],
            ['t' => 'TARIFA', 'w' => $a[6], 'c' => 'C'],
            ['t' => 'CLA', 'w' => $a[7], 'c' => 'C'],
            ['t' => 'TIEMPO', 'w' => $a[8], 'c' => 'C'],
            ['t' => 'SALARIOS', 'w' => $a[9] + $a[10] + $a[11], 'c' => 'C'],
            ['t' => 'TRABAJO EXTRAORDINARIO', 'w' => $a[12] + $a[13] + $a[14], 'c' => 'C'],
            ['t' => 'MAESTRIA', 'w' => $a[15], 'c' => 'C'],
            ['t' => 'SALARIO', 'w' => $a[16], 'c' => 'C'],
        ];

        // Cabecera nivel 2 (subcolumnas).
        $h2 = [
            ['t' => '', 'w' => $a[0], 'c' => 'C'],
            ['t' => '', 'w' => $a[1], 'c' => 'C'],
            ['t' => '', 'w' => $a[2], 'c' => 'C'],
            ['t' => 'IDENTIDAD', 'w' => $a[3], 'c' => 'C'],
            ['t' => '', 'w' => $a[4], 'c' => 'C'],
            ['t' => '', 'w' => $a[5], 'c' => 'C'],
            ['t' => '', 'w' => $a[6], 'c' => 'C'],
            ['t' => '', 'w' => $a[7], 'c' => 'C'],
            ['t' => 'TOTAL', 'w' => $a[8], 'c' => 'C'],
            ['t' => 'ESCALA', 'w' => $a[9], 'c' => 'C'],
            ['t' => 'CLA', 'w' => $a[10], 'c' => 'C'],
            ['t' => 'NOCTURNIDAD', 'w' => $a[11], 'c' => 'C'],
            ['t' => 'TARIFA', 'w' => $a[12], 'c' => 'C'],
            ['t' => 'TIEMPO EXTRA', 'w' => $a[13], 'c' => 'C'],
            ['t' => 'SALARIO', 'w' => $a[14], 'c' => 'C'],
            ['t' => '', 'w' => $a[15], 'c' => 'C'],
            ['t' => 'A DEVENGAR', 'w' => $a[16], 'c' => 'C'],
        ];

        $this->dibujarCabecera($h1, $h2);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L', 180);

        $posY = 48;
        $max = 175;
        $i = 0;
        $nro = 1;
        $area = null;

        $subt = null;
        $tot = null;

        foreach ($data['registros'] as $arr) {
            $areaActual = $arr['nombarea'] ?? 'Sin área';

            if ($area !== null && $area !== $areaActual) {
                $this->renderSubtotal($area, $subt, $a, $posY);
                $posY += 10;
                $i++;
                $nro++;
                $subt = null;
            }
            $area = $areaActual;

            if ($posY >= $max) {
                $this->inicio($titulo, 50, 5);
                $this->dibujarCabecera($h1, $h2);
                $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L', 180);
                $posY = 48;
                $i = 0;
            }

            $this->SetXY(5, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', '', 9);
            $this->Cell($a[0], 6, (string) $nro, 1, 0, 'C', 1);
            $this->SetFont('Arial', '', 7);
            $this->Cell($a[1], 6, $this->codMostrar($arr['nronomina'] ?? null, $arr['cidentidad'] ?? null), 1, 0, 'C', 1);
            $this->SetFont('Arial', '', 9);
            $this->Cell($a[2], 6, $this->latin1(mb_strtoupper(mb_strtolower($arr['nombrecompleto'] ?? ''))), 1, 0, 'L', 1);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell($a[3], 6, $arr['cidentidad'] ?? '', 1, 0, 'C', 1);
            $this->SetFont('Arial', '', 7);
            $this->Cell($a[4], 6, $this->latin1(mb_substr($arr['nombcargo'] ?? '', 0, 25)), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 10);
            $this->Cell($a[5], 6, $this->fmt($arr['salario'] ?? 0, 0), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell($a[6], 6, $this->fmt($arr['tarifa'] ?? 0, 4), 1, 0, 'C', 1);
            $this->SetFont('Arial', '', 10);
            $this->Cell($a[7], 6, $this->fmt($arr['cla'] ?? 0, 2), 1, 0, 'C', 1);
            $this->Cell($a[8], 6, $this->fmt($arr['ttotal'] ?? 0, 2), 1, 0, 'C', 1);
            $this->Cell($a[9], 6, $this->fmt($arr['impescala'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell($a[10], 6, $this->fmt($arr['impcla'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell($a[11], 6, $this->fmt($arr['impnocturnidad'] ?? 0, 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell($a[12], 6, $this->fmt($arr['tarhextras'] ?? 0, 4), 1, 0, 'C', 1);
            $this->SetFont('Arial', '', 10);
            $this->Cell($a[13], 6, $this->fmt($arr['tiempoextra'] ?? 0, 2), 1, 0, 'C', 1);
            $this->Cell($a[14], 6, $this->fmt($arr['impextra'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell($a[15], 6, $this->fmt($arr['impmaestrias'] ?? 0, 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell($a[16], 6, $this->fmt($arr['impsalario2'] ?? 0, 2), 1, 0, 'R', 1);
            $posY += 6;
            $i++;
            $nro++;

            $subt = $this->acumular($subt, $arr);
            $tot = $this->acumular($tot, $arr);
        }

        if ($area !== null) {
            $this->renderSubtotal($area, $subt, $a, $posY);
            $posY += 10;
        }

        // Total general.
        $this->SetXY(5, $posY);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($a[0] + $a[1] + $a[2] + $a[3] + $a[4], 10, 'TOTAL GENERAL', 1, 0, 'L', 1);
        $this->Cell($a[5], 10, $this->fmt($tot['salario'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[6], 10, '', 1, 0, 'R', 1);
        $this->Cell($a[7], 10, '', 1, 0, 'R', 1);
        $this->Cell($a[8], 10, $this->fmt($tot['ttotal'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell($a[9], 10, $this->fmt($tot['impescala'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[10], 10, $this->fmt($tot['impcla'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[11], 10, $this->fmt($tot['impnocturnidad'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[12], 10, '', 1, 0, 'R', 1);
        $this->Cell($a[13], 10, $this->fmt($tot['tiempoextra'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[14], 10, $this->fmt($tot['impextra'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[15], 10, $this->fmt($tot['impmaestrias'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[16], 10, $this->fmt($tot['impsalario2'] ?? 0, 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function dibujarCabecera(array $h1, array $h2): void
    {
        // Fondo gris claro para el encabezado (evita el relleno negro por defecto).
        $this->SetFillColor(200, 200, 200);
        $this->SetXY(5, 36);
        foreach ($h1 as $c) {
            $this->SetFont('Arial', 'B', 7);
            $this->Cell($c['w'], 6, $c['t'], 1, 0, $c['c'], 1);
        }
        $this->Ln(6);
        $this->SetX(5);
        foreach ($h2 as $c) {
            $this->SetFont('Arial', 'B', 7);
            $this->Cell($c['w'], 6, $c['t'], 1, 0, $c['c'], 1);
        }
        $this->Ln(6);
    }

    private function renderSubtotal(string $area, ?array $s, array $a, int $posY): void
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY(5, $posY);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($a[0] + $a[1] + $a[2] + $a[3] + $a[4], 10, $this->latin1(mb_strtoupper($area)), 1, 0, 'L', 1);
        $this->Cell($a[5], 10, $this->fmt($s['salario'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[6], 10, '', 1, 0, 'R', 1);
        $this->Cell($a[7], 10, '', 1, 0, 'R', 1);
        $this->Cell($a[8], 10, $this->fmt($s['ttotal'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell($a[9], 10, $this->fmt($s['impescala'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[10], 10, $this->fmt($s['impcla'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[11], 10, $this->fmt($s['impnocturnidad'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[12], 10, '', 1, 0, 'R', 1);
        $this->Cell($a[13], 10, $this->fmt($s['tiempoextra'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[14], 10, $this->fmt($s['impextra'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[15], 10, $this->fmt($s['impmaestrias'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell($a[16], 10, $this->fmt($s['impsalario2'] ?? 0, 2), 1, 0, 'R', 1);
    }

    /** Blanqueo del código cuando coincide con la cédula (ceros a la izquierda ignorados). */
    private function codMostrar(?string $cod, ?string $carnet): string
    {
        if ($cod === null || $cod === '' || $carnet === null || $carnet === '') {
            return $cod ?? '';
        }

        return ltrim($cod, '0') === ltrim($carnet, '0') ? '' : $cod;
    }

    private function acumular(?array $acc, array $r): array
    {
        if ($acc === null) {
            $acc = ['salario' => 0, 'ttotal' => 0, 'impescala' => 0, 'impcla' => 0,
                'impnocturnidad' => 0, 'tiempoextra' => 0, 'impextra' => 0,
                'impmaestrias' => 0, 'impsalario2' => 0];
        }
        $acc['salario'] += (float) ($r['salario'] ?? 0);
        $acc['ttotal'] += (float) ($r['ttotal'] ?? 0);
        $acc['impescala'] += (float) ($r['impescala'] ?? 0);
        $acc['impcla'] += (float) ($r['impcla'] ?? 0);
        $acc['impnocturnidad'] += (float) ($r['impnocturnidad'] ?? 0);
        $acc['tiempoextra'] += (float) ($r['tiempoextra'] ?? 0);
        $acc['impextra'] += (float) ($r['impextra'] ?? 0);
        $acc['impmaestrias'] += (float) ($r['impmaestrias'] ?? 0);
        $acc['impsalario2'] += (float) ($r['impsalario2'] ?? 0);
        return $acc;
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
