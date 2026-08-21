<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

abstract class BaseReportService
{
    protected string $title = '';

    protected string $orientation = 'portrait';

    protected string $paper = 'letter';

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setOrientation(string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    protected function pdf(string $view, array $data = []): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView($view, array_merge($data, [
            'title' => $this->title,
            'orientation' => $this->orientation,
        ]))->setPaper($this->paper, $this->orientation);
    }

    protected function streamPdf(string $view, array $data = [], ?string $filename = null): Response
    {
        $filename ??= str_replace(' ', '_', $this->title).'.pdf';

        return $this->pdf($view, $data)->stream($filename);
    }

    protected function downloadPdf(string $view, array $data = [], ?string $filename = null): Response
    {
        $filename ??= str_replace(' ', '_', $this->title).'.pdf';

        return $this->pdf($view, $data)->download($filename);
    }

    protected function downloadExcel(string $exportClass, ?string $filename = null): Response
    {
        $filename ??= str_replace(' ', '_', $this->title).'.xlsx';

        return Excel::download(new $exportClass, $filename);
    }

    protected function cambiarFormatoFecha(?string $fecha, string $formato = 'd/m/Y'): string
    {
        if (! $fecha) {
            return '';
        }
        try {
            return Carbon::parse($fecha)->format($formato);
        } catch (\Exception) {
            return $fecha;
        }
    }

    protected function nombreMes(int $mes): string
    {
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        return $meses[$mes] ?? '';
    }

    protected function cambiarMinutosHoras(int $minutos): string
    {
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    protected function cambiarHoraMinutos(string $hora): int
    {
        $parts = explode(':', $hora);

        return (int) ($parts[0] ?? 0) * 60 + (int) ($parts[1] ?? 0);
    }

    // === Formateadores reutilizables (paridad legacy) ===

    /**
     * Formatea un importe según la moneda (MN / ME / MLC / MT).
     * El legacy usa estas siglas para separar nacional, extranjera y total.
     */
    protected function formatoMoneda($valor, string $moneda = 'MN'): string
    {
        $simbolo = match (strtoupper($moneda)) {
            'ME', 'MLC' => strtoupper($moneda),
            'MT' => 'MT',
            default => '$',
        };

        return $simbolo.' '.number_format((float) $valor, 2, ',', '.');
    }

    protected function formatoNumero($valor, int $decimales = 2): string
    {
        return number_format((float) $valor, $decimales, ',', '.');
    }

    protected function formatoPorcentaje($valor, int $decimales = 2): string
    {
        return number_format((float) $valor, $decimales, ',', '.').' %';
    }

    protected function formatoMesAnio(int $mes, int $anio): string
    {
        return $this->nombreMes($mes).' '.$anio;
    }

    /**
     * Convierte un número (con 2 decimales) a texto en español.
     * Usado en facturas y certificados del legacy.
     */
    protected function numeroALetras($valor): string
    {
        $valor = (float) $valor;
        $entero = (int) floor($valor);
        $centavos = (int) round(($valor - $entero) * 100);

        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE',
            'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE',
            'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
        $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA',
            'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS',
            'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($entero === 0) {
            $letras = 'CERO';
        } elseif ($entero === 100) {
            $letras = 'CIEN';
        } elseif ($entero < 20) {
            $letras = $unidades[$entero];
        } elseif ($entero < 100) {
            $d = intdiv($entero, 10);
            $u = $entero % 10;
            if ($u === 0) {
                $letras = $decenas[$d];
            } else {
                $letras = ($d === 2 ? 'VEINTI' : $decenas[$d].' Y ').$unidades[$u];
            }
        } elseif ($entero < 1000) {
            $c = intdiv($entero, 100);
            $r = $entero % 100;
            $letras = $centenas[$c];
            if ($r > 0) {
                $letras .= ' '.$this->numeroALetras($r);
            }
        } else {
            // Miles en adelante (suficiente para importes de reportes).
            $mil = intdiv($entero, 1000);
            $r = $entero % 1000;
            $letras = ($mil === 1 ? 'MIL' : $this->numeroALetras($mil).' MIL');
            if ($r > 0) {
                $letras .= ' '.$this->numeroALetras($r);
            }
        }

        return trim($letras).($centavos > 0 ? ' CON '.str_pad($centavos, 2, '0', STR_PAD_LEFT).'/100' : '');
    }

    /**
     * Clasifica la columna `variable` de la tabla legacy `reportes` en el tipo
     * de filtro que necesita el reporte. Permite reusar un único formulario por tipo.
     */
    protected function tipoFiltro(string $variable): string
    {
        return match (strtolower(trim($variable))) {
            'mes', 'mes1' => 'mes',
            'fecha', 'fparte' => 'fecha',
            'consecutivo' => 'consecutivo',
            'tractivo' => 'tractivo',
            'cliente', 'clientes', 'abreviatura', 'organismo' => 'cliente',
            'tarjeta' => 'tarjeta',
            'cargas', 'devoluciones', 'ingresos', 'indicadores', 'variable',
            'variable2', 'imptipocomb', 'nombrecompleto2' => 'agrupacion',
            default => 'directo',
        };
    }
}
