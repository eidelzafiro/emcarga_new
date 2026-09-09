<?php

namespace App\Services\Reports\Fpdf;

use App\Models\Entidad;
use App\Services\Reports\IngresosReportService;

/**
 * Parte Ingresos Mensuales Chóferes — réplica FPDF 1:1 del legacy
 * (Reportesnew.php:363 pdf_ingresos_choferes).
 *
 * Formato A4 horizontal. Bloque de filas: pos_y inicial 42, alto 8,
 * 19 filas por página (salto en i==20). Fila de totales alto 10.
 *
 * @see https://github.com/emcarga/app-docs Ingresos por Chóferes
 */
class IngresosChoferesFpdfReport extends FpdfReportBase
{
    private array $filtros;

    public function __construct(?int $entidadId, string $mes, string $ano, array $filtros = [])
    {
        parent::__construct((int) ($entidadId ?? 0), $mes, $ano);
        $this->filtros = $filtros;
    }

    public function generate(): string
    {
        $service = app(IngresosReportService::class);
        $datos = $service->datosIngresosChoferes($this->filtros);

        $titulo = $datos['titulo'];

        if (empty($datos['filas'])) {
            $this->inicio($titulo, 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->inicio($titulo, 50, 5);

        $campos1 = [
            ['titulo' => 'CHOFER',          'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CP',              'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TONS',            'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'HORAS',           'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'MONEDA NACIONAL', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'PRODUCCION',      'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => '% ',              'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'ESTIMADO',        'ancho' => 35, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'PRODUCCION',      'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
        ];

        $campos = [
            ['titulo' => '',          'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',          'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',          'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',          'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'FLETE',     'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'DEMORA',    'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'AFORADA',   'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => ' ',         'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'CP',        'ancho' => 10, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'FLETE',     'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'ESTIMADA',  'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        $this->titulosChoferes($campos, $campos1, 6, 10, 30);

        $posY = 42;
        $i = 1;
        $max = 20;

        foreach ($datos['filas'] as $arr) {
            if ($i == $max) {
                $this->inicio($titulo, 50, 5);
                $this->titulosChoferes($campos, $campos1, 6, 10, 35);
                $posY = 47;
                $i = 1;
            }

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', 'BU', 12);
            $this->Cell(70, 8, $this->latin1(ucwords(mb_strtolower($arr['chofer'], 'UTF-8'))), 1, 0, 'L', 1);
            $this->SetFont('Arial', 'B', 13);
            $this->Cell(10, 8, $this->fmtVar($arr['cp'], 0), 1, 0, 'R', 1);
            $this->Cell(20, 8, $this->fmtVar($arr['tons'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 8, $this->fmtVar($arr['horas'], 2), 1, 0, 'R', 1);
            $this->Cell(25, 8, $this->fmtVar($arr['flete'], 2), 1, 0, 'R', 1);
            $this->Cell(25, 8, $this->fmtVar($arr['demora'], 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(30, 8, $this->fmtVar($arr['produccion_aforada'], 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(10, 8, $this->fmtVar($arr['porcentaje'], 0), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(10, 8, '', 1, 0, 'R', 1);
            $this->Cell(25, 8, '', 1, 0, 'R', 1);
            $this->Cell(30, 8, $this->fmtVar($arr['produccion_estimada'], 2), 1, 0, 'R', 1);

            $posY += 8;
            $i++;
        }

        $tot = $datos['totales'];

        // Fila de totales (replica Reportesnew.php:478-500).
        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->Cell(70, 10, 'TOTAL', 1, 0, 'L', 1);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(10, 10, $this->fmtVar($tot['cp'] ?? 0, 0), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['tons'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['horas'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($tot['flete'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($tot['demora'] ?? 0, 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 13);
        $this->Cell(30, 10, $this->fmtVar($tot['produccion_aforada'] ?? 0, 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(10, 10, $this->fmtVar($tot['porcentaje'] ?? 100, 2), 1, 0, 'R', 1);
        $this->Cell(10, 10, '', 1, 0, 'R', 1);
        $this->Cell(25, 10, '', 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 13);
        $this->Cell(30, 10, $this->fmtVar($tot['produccion_estimada'] ?? 0, 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    /**
     * Header legacy (Reportesnew.php:99 inicio()) con alineación 'L',
     * nombre del mes y bloque ENTIDAD / UNIDAD.
     */
    public function inicio(string $titulo, int $posX = 50, int $posY = 5): void
    {
        $this->AddPage();
        $this->SetAutoPageBreak(false);

        $logoFile = $this->getLogoFile();
        if ($logoFile && file_exists($logoFile)) {
            $this->Image($logoFile, 10, 5, $this->logoWidth, $this->logoHeight);
        }

        $this->SetXY($posX, $posY);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 6, 'GESTION INTEGRAL DEL PARQUE AUTOMOTOR', 0, 1, 'L');
        $posY += 6;

        $this->SetFont('Arial', 'B', 13);
        $this->SetXY($posX, $posY);
        $this->Cell(0, 6, $titulo, 0, 1, 'L');
        $posY += 6;

        $this->SetFont('Arial', 'B', 12);
        $mes = $this->mesMostrable();
        if ($mes === '00') {
            $this->SetXY($posX, $posY);
            $this->Cell(80, 6, 'ACUMULADO ', 0, 1, 'L');
        } else {
            $this->SetXY($posX, $posY);
            $this->Cell(80, 6, $this->nombreMes($mes), 0, 1, 'L');
        }

        $entidad = Entidad::find($this->entidadId);
        if ($entidad) {
            $this->SetXY($posX + 30, $posY);
            $this->Cell(80, 6, $entidad->nombre.' '.$entidad->codigo, 0, 1, 'L');
            $this->SetXY($posX + 40, $posY + 6);
            $this->Cell(80, 6, 'UNIDAD: '.($entidad->abreviatura ?: $entidad->nombre), 0, 1, 'L');
        }

        $this->AliasNbPages();
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 10, $this->latin1('Página '.$this->PageNo().'/{nb}'), 0, 0, 'C');
        $this->SetXY(10, -15);
        $this->Cell(0, 10, $this->latin1('Fecha emisión: '.$this->fechaEmision()), 0, 1, 'L');
    }

    /**
     * Títulos de cabecera en el orden del legacy Reportesnew::titulos():
     * primero campos1 (fila superior), luego campos (fila inferior).
     */
    private function titulosChoferes(array $campos, array $campos1, int $linea = 6, int $posX = 10, int $posY = 30): void
    {
        $this->SetFillColor($this->fillGris());

        foreach (['campos1' => $campos1, 'campos' => $campos] as $fila) {
            $this->SetFont('Arial', 'B', 11);
            $this->SetXY($posX, $posY);
            foreach ($fila as $campo) {
                $this->Cell(
                    $campo['ancho'],
                    $linea,
                    $campo['titulo'],
                    $campo['bordes'] ?? 1,
                    0,
                    $campo['direccion'],
                    $campo['fondo'] ?? 1
                );
            }
            $posY += $linea;
        }
    }

    /**
     * Convierte UTF-8 → ISO-8859-1 para las fuentes core de FPDF
     * (equivalente al utf8_decode() del legacy).
     */
    private function latin1(string $value): string
    {
        return mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
    }

    /**
     * Formatea como cambiarVariable() del legacy: '' si vacío/0,
     * number_format con miles con coma.
     */
    private function fmtVar($value, int $decimals = 0): string
    {
        if ($value === '' || $value === null || (float) $value == 0) {
            return '';
        }
        $value = 0 + str_replace(',', '', (string) $value);
        if (! is_numeric($value)) {
            return '';
        }
        return number_format($value, $decimals);
    }

    /**
     * Mes a mostrar en el encabezado: '00' acumulado, 'MM' o 'YYYY-MM'.
     */
    private function mesMostrable(): string
    {
        $mes = $this->mes;

        if ($mes === '00') {
            return '00';
        }

        if (strlen($mes) === 2) {
            return $mes;
        }

        if (preg_match('/^\d{4}-(\d{2})$/', $mes, $m)) {
            return $m[1];
        }

        $fOperaciones = session('fecha_operaciones');
        return $fOperaciones ? substr($fOperaciones, 5, 2) : date('m');
    }

    private function nombreMes(string $mes): string
    {
        $nombres = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
            '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
            '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
            '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
        ];

        return $nombres[$mes] ?? $mes;
    }

    private function fechaEmision(): string
    {
        return session('fecha_operaciones') ?: date('Y-m-d');
    }

    private function fillGris(): int
    {
        return (int) (session('FillColor') ?? 200);
    }
}