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
        // Los reportes tabulares grandes (parques de ~1500 vehículos) requieren
        // más memoria que el límite por defecto de la app para renderizar en dompdf.
        if ((int) ini_get('memory_limit') > 0) {
            ini_set('memory_limit', '768M');
        }

        return Pdf::loadView($view, array_merge($data, [
            'title' => $this->title,
            'orientation' => $this->orientation,
        ]))->setPaper($this->paper, $this->orientation);
    }

    protected function streamPdf(string $view, array $data = [], ?string $filename = null): Response
    {
        $filename ??= $this->safeName($this->title).'.pdf';

        return $this->pdf($view, $data)->stream($filename);
    }

    protected function downloadPdf(string $view, array $data = [], ?string $filename = null): Response
    {
        $filename ??= $this->safeName($this->title).'.pdf';

        return $this->pdf($view, $data)->download($filename);
    }

    protected function downloadExcel(string $exportClass, ?string $filename = null): Response
    {
        $filename ??= $this->safeName($this->title).'.xlsx';

        return Excel::download(new $exportClass, $filename);
    }

    /**
     * Fase A: renderiza un reporte tabular genérico (columnas + filas) a PDF
     * usando la vista `reports.reporte_tabla`. $columnas = [['key','label','num']].
     */
    public function reporteTablaPdf(
        string $titulo,
        array $columnas,
        array $filas,
        array $opts = [],
    ): Response {
        $this->setTitle($titulo);
        if (! empty($opts['landscape'])) {
            $this->setOrientation('landscape');
        }
        if (! empty($opts['paper'])) {
            $this->paper = $opts['paper'];
        }

        return $this->streamPdf('reports.reporte_tabla', [
            'titulo'  => $titulo,
            'periodo' => $opts['periodo'] ?? '',
            'columnas'=> $columnas,
            'filas'   => $filas,
            'totales' => $opts['totales'] ?? null,
            'firmas'  => $opts['firmas'] ?? [],
            'encabezado' => $opts['encabezado'] ?? null,
        ]);
    }

    /**
     * Fase A: exporta columnas + filas a Excel (PhpSpreadsheet) y descarga.
     */
    public function reporteTablaExcel(
        string $titulo,
        array $columnas,
        array $filas,
        ?string $nombre = null,
    ): \Symfony\Component\HttpFoundation\Response {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $col = 1;
        foreach ($columnas as $c) {
            $sheet->setCellValueByColumnAndRow($col++, 1, $c['label']);
        }

        $row = 2;
        foreach ($filas as $f) {
            $col = 1;
            foreach ($columnas as $c) {
                $sheet->setCellValueByColumnAndRow($col++, $row, $f[$c['key']] ?? '');
            }
            $row++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $nombre ??= $this->safeName($titulo).'.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'rep').'.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, $nombre)->deleteFileAfterSend(true);
    }

    protected function safeName(string $nombre): string
    {
        return str_replace(['/', '\\', ' '], ['-', '-', '_'], $nombre);
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
    /**
     * Normaliza el filtro `mes` (YYYY-MM) desde el formulario del catálogo.
     */
    protected function mesFiltro(array $filtros): ?string
    {
        if (empty($filtros['mes'])) {
            return null;
        }
        try {
            return Carbon::parse($filtros['mes'])->format('Y-m');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Devuelve [desde, hasta] como YYYY-MM-DD; si solo hay `mes`, usa el mes completo.
     */
    protected function rangoFiltros(array $filtros): array
    {
        $d = $filtros['desde'] ?? null;
        $h = $filtros['hasta'] ?? null;
        if (! $d && ! $h && ! empty($filtros['mes'])) {
            try {
                $m = Carbon::parse($filtros['mes']);
                $d = $m->copy()->startOfMonth()->toDateString();
                $h = $m->copy()->endOfMonth()->toDateString();
            } catch (\Exception) {}
        }

        return [$d, $h];
    }

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
