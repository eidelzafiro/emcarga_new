<?php

namespace Tests\Feature;

use App\Services\Reports\BaseReportService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Fase 0: verifica la base común de reportes (formateadores reutilizables y
 * componentes Blade) antes de migrar los 196 reportes usados.
 */
class ReportesBaseTest extends TestCase
{
    private function invocar(BaseReportService $svc, string $method, ...$args)
    {
        $r = new ReflectionMethod($svc, $method);
        $r->setAccessible(true);

        return $r->invoke($svc, ...$args);
    }

    public function test_formateadores(): void
    {
        $svc = new class extends BaseReportService {};

        $this->assertSame('$ 1.234,50', $this->invocar($svc, 'formatoMoneda', 1234.5, 'MN'));
        $this->assertSame('ME 100,00', $this->invocar($svc, 'formatoMoneda', 100, 'ME'));
        $this->assertSame('MT 100,00', $this->invocar($svc, 'formatoMoneda', 100, 'MT'));
        $this->assertSame('1.234,50', $this->invocar($svc, 'formatoNumero', 1234.5));
        $this->assertSame('12,50 %', $this->invocar($svc, 'formatoPorcentaje', 12.5));
        $this->assertSame('Agosto 2026', $this->invocar($svc, 'formatoMesAnio', 8, 2026));

        $this->assertSame('CERO', $this->invocar($svc, 'numeroALetras', 0));
        $this->assertSame('CIEN', $this->invocar($svc, 'numeroALetras', 100));
        $this->assertSame('VEINTIUNO', $this->invocar($svc, 'numeroALetras', 21));
        $this->assertSame('MIL DOSCIENTOS TREINTA Y CUATRO CON 50/100', $this->invocar($svc, 'numeroALetras', 1234.5));

        $this->assertSame('mes', $this->invocar($svc, 'tipoFiltro', 'mes'));
        $this->assertSame('fecha', $this->invocar($svc, 'tipoFiltro', 'fecha'));
        $this->assertSame('consecutivo', $this->invocar($svc, 'tipoFiltro', 'consecutivo'));
        $this->assertSame('tractivo', $this->invocar($svc, 'tipoFiltro', 'tractivo'));
        $this->assertSame('cliente', $this->invocar($svc, 'tipoFiltro', 'clientes'));
        $this->assertSame('tarjeta', $this->invocar($svc, 'tipoFiltro', 'tarjeta'));
        $this->assertSame('agrupacion', $this->invocar($svc, 'tipoFiltro', 'cargas'));
        $this->assertSame('directo', $this->invocar($svc, 'tipoFiltro', 'todos'));
        $this->assertSame('directo', $this->invocar($svc, 'tipoFiltro', ''));
    }

    public function test_componente_tabla(): void
    {
        $html = view('components.reporte.tabla', [
            'cabeceras' => ['Concepto', 'Importe'],
            'filas' => [['Salario', '$ 1.000,00']],
            'totales' => ['TOTAL', '$ 1.000,00'],
        ])->render();

        $this->assertStringContainsString('Concepto', $html);
        $this->assertStringContainsString('Salario', $html);
        $this->assertStringContainsString('TOTAL', $html);
    }

    public function test_componente_encabezado(): void
    {
        $html = view('components.reporte.encabezado', [
            'titulo' => 'RESUMEN MENSUAL',
            'empresa' => 'ZAFIRO',
        ])->render();

        $this->assertStringContainsString('RESUMEN MENSUAL', $html);
        $this->assertStringContainsString('ZAFIRO', $html);
    }
}
