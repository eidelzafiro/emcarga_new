<?php

namespace App\Services\Reports\Fpdf;

use FPDF;

/**
 * Base FPDF que replica fielmente `Reportes_lib::inicio()` y
 * `Reportes_lib::titulos()` del legacy (encabezado, mes, entidad, unidad,
 * pie con página y fecha de emisión, títulos multicolumna).
 */
abstract class DocumentosFpdfBase extends FPDF
{
    protected array $meses = [
        '01' => 'ENERO', '02' => 'FEBRERO', '03' => 'MARZO',
        '04' => 'ABRIL', '05' => 'MAYO', '06' => 'JUNIO',
        '07' => 'JULIO', '08' => 'AGOSTO', '09' => 'SEPTIEMBRE',
        '10' => 'OCTUBRE', '11' => 'NOVIEMBRE', '12' => 'DICIEMBRE',
        '13' => '1ER TRIMESTRE', '14' => '2DO TRIMESTRE',
        '15' => '3ER TRIMESTRE', '16' => '4TO TRIMESTRE',
    ];

    protected ?object $entidad;

    protected bool $multiEntidad;

    protected string $fechaOperaciones;

    public function __construct(
        string $orientation,
        string $paper,
        ?object $entidad,
        bool $multiEntidad,
        string $fechaOperaciones,
    ) {
        parent::__construct($orientation, 'mm', $paper);
        $this->entidad = $entidad;
        $this->multiEntidad = $multiEntidad;
        $this->fechaOperaciones = $fechaOperaciones;
        $this->SetAutoPageBreak(false);
        $this->AliasNbPages();
    }

    /**
     * Encabezado legacy: logo, título, mes, entidad/unidad y pie de página.
     * `$fecha`: 'MM' (mes) o 'YYYY-MM-DD' (fecha).
     */
    public function inicio(string $titulo, string $fecha = '', float $posX = 50, float $posY = 5): void
    {
        $this->AddPage();
        $this->SetAutoPageBreak(false);

        $logo = public_path('images/emcarga.png');
        if (file_exists($logo)) {
            $this->Image($logo, 10, 5, 35, 20);
        }

        $this->SetXY($posX, $posY);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 6, 'GESTION INTEGRAL DEL PARQUE AUTOMOTOR', 0, 1, 'L');
        $posY += 6;

        $this->SetFont('Arial', 'B', 13);
        $this->SetXY($posX, $posY);
        $this->Cell(0, 6, $this->txt($titulo), 0, 1, 'L');
        $posY += 6;

        $this->SetFont('Arial', 'B', 12);
        if (strlen($fecha) === 2) {
            $mes1 = $fecha;
            $this->SetXY($posX, $posY);
            $this->Cell(80, 6, $mes1 !== '00' ? $this->nombreMesCompleto($mes1) : 'ACUMULADO', 0, 1, 'L');
            $fecha = $this->fechaOperaciones;
        } else {
            if ($fecha === '') {
                $fecha = $this->fechaOperaciones;
                $mes1 = $this->fechaOperaciones !== '' ? substr($this->fechaOperaciones, 5, 2) : '';
            } else {
                $mes1 = substr($fecha, 5, 2);
            }
            $this->SetXY($posX, $posY);
            $this->Cell(100, 6, $this->nombreMesCompleto($mes1), 0, 1, 'L');
        }

        // Entidad + código
        $this->SetXY($posX + 30, $posY);
        $this->Cell(80, 6, $this->txt(trim(($this->entidad->nombre ?? '').' '.($this->entidad->codigo ?? ''))), 0, 1, 'L');

        // UNIDAD (solo si hay más de una entidad)
        if ($this->multiEntidad) {
            $this->SetXY($posX + 40, $posY + 6);
            $this->Cell(80, 6, 'UNIDAD: '.$this->txt((string) ($this->entidad->abreviatura ?? '')), 0, 1, 'L');
        }

        // Pie: página + fecha de emisión
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 10, 'Página '.$this->PageNo().'/{nb}', 0, 0, 'C');
        $this->SetXY(10, -15);
        $this->Cell(0, 10, 'Fecha emisión: '.$fecha, 0, 1, 'L');
    }

    /**
     * Títulos multicolumna legacy. `$campos1`/`$campos2` se dibujan como capas
     * superiores y `$campos` como fila principal.
     */
    public function titulos(float $linea, float $posX, float $posY, array $campos, array $campos1 = [], array $campos2 = []): void
    {
        $capas = [];
        if (! empty($campos1)) {
            $capas[] = $campos1;
        }
        if (! empty($campos2)) {
            $capas[] = $campos2;
        }

        foreach ($capas as $capa) {
            $this->SetXY($posX, $posY);
            $this->SetFillColor(200, 200, 200);
            foreach ($capa as $campo) {
                $this->SetFont('Arial', 'B', (int) ($campo['letra'] ?? 11));
                $this->Cell((float) $campo['ancho'], $linea, $this->txt((string) $campo['titulo']), $campo['bordes'] ?? 1, 0, $campo['direccion'], 1);
            }
            $posY += $linea;
        }

        $this->SetXY($posX, $posY);
        $this->SetFillColor(200, 200, 200);
        foreach ($campos as $campo) {
            $this->SetFont('Arial', 'B', (int) ($campo['letra'] ?? 11));
            $this->Cell((float) $campo['ancho'], $linea, $this->txt((string) $campo['titulo']), $campo['bordes'] ?? 1, 0, $campo['direccion'], 1);
        }
    }

    /** Generador genérico legacy `pdf_codificadores`. */
    public function pdfCodificadores(string $orientacion, float $linea, string $titulo, string $fecha, array $data, array $campos, array $campos1 = [], string $hoja = 'Letter'): void
    {
        [$posY, $max] = $this->calcularPosicionMax($campos1, $orientacion, $linea);

        if ($data) {
            $this->inicio($titulo, $fecha);
            $this->titulos($linea, 15, 30, $campos, $campos1);
            $this->SetFillColor(255, 255, 255);
            $letra = $campos[0]['letra2'] ?? 12;
            $e = 0;
            $nro = 1;

            foreach ($data as $arr) {
                if ($e === $max) {
                    $this->inicio($titulo, $fecha);
                    $this->titulos($linea, 15, 30, $campos, $campos1);
                    $this->SetFillColor(255, 255, 255);
                    [$posY, $max] = $this->calcularPosicionMax($campos1, $orientacion, $linea);
                    $e = 0;
                }
                $this->SetXY(15, $posY);
                foreach ($campos as $campo) {
                    if (isset($campo['letra2'])) {
                        $letra = $campo['letra2'];
                    }
                    $this->SetFont('Arial', '', (int) $letra);
                    if ($campo['campo'] === '$nro') {
                        $this->Cell((float) $campo['ancho'], $linea, (string) $nro, 1, 0, $campo['direccion'], 1);
                    } else {
                        $decimal = $campo['decimal'] ?? 0;
                        $valor = $arr->{$campo['campo']} ?? '';
                        if (is_string($valor)) {
                            $valor = $this->txt(substr($valor, 0, (int) ($campo['ancho'] / 2)));
                        }
                        if (is_float($valor) || (is_numeric($valor) && $decimal > 0)) {
                            $valor = $this->cambiarVariable($valor, $decimal);
                        }
                        if ($valor === '0' || $valor === 0) {
                            $valor = '';
                        }
                        $this->Cell((float) $campo['ancho'], $linea, (string) $valor, 1, 0, $campo['direccion'], 1);
                    }
                }
                $posY += $linea;
                $e++;
                $nro++;
            }
        } else {
            $this->inicio($titulo, $fecha);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }
    }

    protected function calcularPosicionMax(array $campos1, string $orientacion, float $linea): array
    {
        if (! empty($campos1)) {
            return [30 + ($linea * 2), $orientacion === 'P' ? 19 : 13];
        }

        return [30 + $linea, $orientacion === 'P' ? 35 : ($linea == 6 ? 26 : 14)];
    }

    /** Bloque REVISADO / APROBADO del legacy. */
    protected function firmasRevisadoAprobado(float $posY = 220): void
    {
        foreach ([['REVISADO', 15], ['APROBADO', 125]] as [$label, $x]) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetXY($x, $posY);
            $this->Cell(0, 6, $label, 0, 1, 'L');
            $this->SetXY($x, $posY + 12);
            $this->Cell(70, 6, 'NOMBRE', 'B', 1, 'L');
            $this->SetXY($x, $posY + 20);
            $this->Cell(70, 6, 'CARGO', 'B', 1, 'L');
            $this->SetXY($x, $posY + 28);
            $this->Cell(70, 6, 'FIRMA', 'B', 1, 'L');
        }
    }

    protected function nombreMesCompleto(string $mes): string
    {
        return $this->meses[$mes] ?? '';
    }

    /** Replica Reportes_lib::cambiarVariable(). */
    protected function cambiarVariable($variable, int $decimas = 0): string
    {
        if ($variable === null || $variable === '' || (float) $variable == 0.0) {
            return '';
        }

        return number_format((float) str_replace(',', '', (string) $variable), $decimas, '.', ',');
    }

    protected function txt(string $texto): string
    {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }

    protected function salida(string $nombre): \Illuminate\Http\Response
    {
        return response($this->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$nombre.'"');
    }
}
