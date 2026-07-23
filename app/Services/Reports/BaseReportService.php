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
}
