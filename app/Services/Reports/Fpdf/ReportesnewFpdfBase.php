<?php

namespace App\Services\Reports\Fpdf;

use App\Models\Entidad;

/**
 * Base común de los reportes de la familia Reportesnew (Ingresos/Indicadores
 * de explotación). Replica fielmente:
 * - inicio() del legacy Reportesnew::inicio (alineación 'L', logo, título,
 *   mes/ACUMULADO, bloque ENTIDAD + UNIDAD, pie "Página {n}/{nb}" y fecha).
 * - titulos() de dos filas (campos1 superior, campos inferior) con fondo gris.
 * - Formateadores: cambiarVariable (fmtVar), utf8_decode (latin1), nombreMes.
 */
abstract class ReportesnewFpdfBase extends FpdfReportBase
{
    protected array $filtros = [];

    public function __construct(?int $entidadId, string $mes, string $ano, array $filtros = [], string $orientation = 'L', string $paper = 'A4')
    {
        parent::__construct((int) ($entidadId ?? 0), $mes, $ano, $orientation, $paper);
        $this->filtros = $filtros;
    }

    /**
     * Header legacy Reportesnew::inicio(): logo (10,5,35,20), título B15/B13,
     * mes o ACUMULADO B12, bloque ENTIDAD nombre+codigo y UNIDAD abreviatura,
     * pie de página con "Página {n}/{nb}" y "Fecha emisión".
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
            $this->Cell(0, 6, $this->latin1($entidad->nombre).' '.$entidad->codigo, 0, 1, 'L');
            $this->SetXY($posX + 40, $posY + 6);
            $this->Cell(0, 6, 'UNIDAD: '.$this->latin1($entidad->abreviatura ?: $entidad->nombre), 0, 1, 'L');
        }

        $this->AliasNbPages();
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 10, $this->latin1('Página '.$this->PageNo().'/{nb}'), 0, 0, 'C');
        $this->SetXY(10, -15);
        $this->Cell(0, 10, $this->latin1('Fecha emisión: '.$this->fechaEmision()), 0, 1, 'L');
    }

    /**
     * Dos filas de títulos (réplica Reportesnew::titulos): primero campos1
     * (fila superior, celdas combinadas), luego campos (fila inferior).
     */
    protected function titulosFilas(array $campos1, array $campos, int $linea = 6, int $posX = 10, int $posY = 30): void
    {
        $this->SetFillColor($this->fillGris());

        $this->SetFont('Arial', 'B', 11);
        $this->SetXY($posX, $posY);
        foreach ($campos1 as $campo) {
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

        $this->SetXY($posX, $posY);
        foreach ($campos as $campo) {
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
    }

    /**
     * Títulos de hasta tres filas (réplica fiel de Reportes.php::titulos).
     * Dibuja las filas en orden superior → medio → inferior, en negrita, con
     * letra/bordes/fondo configurables por columna (valores por defecto del
     * legacy: letra 11, bordes 1, fondo 1). Las filas vacías se omiten.
     */
    public function titulos(array $superior, array $medio, array $inferior, int $linea = 6, int $posX = 5, int $posY = 35): void
    {
        $this->SetFillColor($this->fillGris());

        foreach ([$superior, $medio, $inferior] as $fila) {
            if (empty($fila)) {
                continue;
            }

            $this->SetXY($posX, $posY);
            foreach ($fila as $campo) {
                $letra = (int) ($campo['letra'] ?? 11);
                $this->SetFont('Arial', 'B', $letra);
                $bordes = $campo['bordes'] ?? 1;
                $fondo = $campo['fondo'] ?? 1;
                $this->Cell($campo['ancho'], $linea, $campo['titulo'], $bordes, 0, $campo['direccion'], $fondo);
            }
            $posY += $linea;
        }
    }

    /**
     * Firmas del reporte (réplica de Reportes.php::pdf_salario_firmas).
     * Lee de la tabla `firmas` por nombre de modelo ("SISTEMA PAGO CHOFERES",
     * "SISTEMA PAGO ADMINISTRATIVO", ...) FILTRANDO por la entidad del
     * reporte (cada entidad tiene sus firmantes); si la entidad no tiene
     * fila propia cae a la matriz (OFICINA CENTRAL) y por último a cualquier
     * fila activa. Dibuja CONF / APROB / ACUSE RECIBO.
     */
    public function firmasSalario(string $nombreModelo, string $orientacion = 'L', ?int $posY = null): void
    {
        $firma = \App\Models\Firma::query()
            ->where('nombre', $nombreModelo)
            ->where('activo', true);

        $entidadId = (int) ($this->entidadId ?? 0);

        $fila = null;
        if ($entidadId) {
            // Firma de la entidad del reporte; si no, la de la matriz.
            $fila = (clone $firma)->where('id_entidad', $entidadId)->first();
            if (! $fila && ($matrizId = \App\Models\Entidad::where('es_matriz', true)->value('id'))) {
                $fila = (clone $firma)->where('id_entidad', $matrizId)->first();
            }
        }
        $fila ??= (clone $firma)->first();

        if (! $fila) {
            return;
        }
        $firma = $fila;

        $posX = 10;
        if ($posY === null) {
            $posY = $orientacion === 'P' ? 245 : 185;
        }
        $ajuste = 0;

        if ($firma->confecciona_nombre) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY($posX, $posY);
            $this->Cell(0, 6, 'CONF: '.$this->latin1($firma->confecciona_nombre), 0, 1, 'L');
            $this->SetFont('Arial', 'B', 8);
            $this->SetXY($posX, $posY + 6);
            $this->Cell(65, 6, $this->latin1($firma->confecciona_cargo ?? ''), 0, 1, 'L');
            $posX += 85 + $ajuste;
        }

        if ($firma->revisa_nombre) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY($posX, $posY);
            $this->Cell(0, 6, 'APROB : '.$this->latin1($firma->revisa_nombre), 0, 1, 'L');
            $this->SetFont('Arial', 'B', 8);
            $this->SetXY($posX, $posY + 6);
            $this->Cell(60, 6, $this->latin1($firma->revisa_cargo ?? ''), 0, 1, 'L');
            $posX += 85 + $ajuste;
        }

        if ($firma->aprueba_nombre) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY($posX, $posY);
            $this->Cell(0, 6, 'ACUSE RECIBO: '.$this->latin1($firma->aprueba_nombre), 0, 1, 'L');
            $this->SetFont('Arial', 'B', 8);
            $this->SetXY($posX, $posY + 6);
            $this->Cell(60, 6, $this->latin1($firma->aprueba_cargo ?? ''), 0, 1, 'L');
        }
    }

    protected function latin1(string $value): string
    {
        return mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
    }

    protected function fmtVar($value, int $decimals = 0): string
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

    protected function mesMostrable(): string
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

    protected function nombreMes(string $mes): string
    {
        $nombres = [
            '01' => 'ENERO', '02' => 'FEBRERO', '03' => 'MARZO',
            '04' => 'ABRIL', '05' => 'MAYO', '06' => 'JUNIO',
            '07' => 'JULIO', '08' => 'AGOSTO', '09' => 'SEPTIEMBRE',
            '10' => 'OCTUBRE', '11' => 'NOVIEMBRE', '12' => 'DICIEMBRE',
            '13' => '1ER TRIMESTRE', '14' => '2DO TRIMESTRE',
            '15' => '3ER TRIMESTRE', '16' => '4TO TRIMESTRE',
        ];

        return $nombres[$mes] ?? $mes;
    }

    protected function fechaEmision(): string
    {
        // Paridad legacy: el pie usa formato Y/m/d (Reportesnew::Footer).
        $fecha = session('fecha_operaciones') ?: date('Y-m-d');

        return str_replace('-', '/', substr($fecha, 0, 10));
    }

    protected function fillGris(): int
    {
        return (int) (session('FillColor') ?? 200);
    }
}
