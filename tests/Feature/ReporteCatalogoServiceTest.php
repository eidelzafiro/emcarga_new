<?php

namespace Tests\Feature;

use App\Services\Reports\ReporteCatalogoService;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;

/**
 * Fase A: lógica de agrupación del catálogo de reportes (sin tocar BD legacy).
 */
class ReporteCatalogoServiceTest extends TestCase
{
    private function fila(array $attrs): object
    {
        return (object) array_merge([
            'idreporte'   => 0,
            'nombreporte' => '',
            'controlador' => '',
            'tipo'        => '',
            'variable'    => null,
            'rechum'      => 0,
            'com'         => 0,
            'cont'        => 0,
            'conte'       => 0,
            'tec'         => 0,
            'teccom'      => 0,
        ], $attrs);
    }

    public function test_agrupa_por_tipo_y_clasifica_filtro_y_perfiles(): void
    {
        $servicio = new ReporteCatalogoService();

        $filas = new Collection([
            $this->fila([
                'idreporte' => 1, 'nombreporte' => 'ANALISIS CARGAS X RANGO DE KMS',
                'controlador' => 'reportes2/pdf_cargas_resumen/', 'tipo' => 'INDICADORES',
                'variable' => 'mes', 'rechum' => 1, 'com' => 1,
            ]),
            $this->fila([
                'idreporte' => 2, 'nombreporte' => 'VIAJES POR FECHAS',
                'controlador' => 'reportes2/pdf_viajes/', 'tipo' => 'INDICADORES',
                'variable' => 'fecha', 'cont' => 1,
            ]),
            $this->fila([
                'idreporte' => 3, 'nombreporte' => 'POSICION GPS EQUIPO',
                'controlador' => 'reportes2/pdf_gps/', 'tipo' => 'GPS',
                'variable' => 'tractivo', 'tec' => 1, 'teccom' => 1,
            ]),
        ]);

        $grupos = $servicio->agrupar($filas);

        $this->assertArrayHasKey('INDICADORES', $grupos);
        $this->assertArrayHasKey('GPS', $grupos);
        $this->assertCount(2, $grupos['INDICADORES']);
        $this->assertCount(1, $grupos['GPS']);

        $primero = $grupos['INDICADORES'][0];
        $this->assertEquals('ANALISIS CARGAS X RANGO DE KMS', $primero['nombre']);
        $this->assertEquals('mes', $primero['filtro']);
        $this->assertEquals(['RECHUM', 'COMERCIAL'], $primero['perfiles']);

        $segundo = $grupos['INDICADORES'][1];
        $this->assertEquals('fecha', $segundo['filtro']);
        $this->assertEquals(['CONTABILIDAD'], $segundo['perfiles']);

        $gps = $grupos['GPS'][0];
        $this->assertEquals('tractivo', $gps['filtro']);
        $this->assertEquals(['TECNICA', 'TECNICA+COMERCIAL'], $gps['perfiles']);
    }

    public function test_variable_desconocida_se_marca_como_directo(): void
    {
        $servicio = new ReporteCatalogoService();

        $filas = new Collection([
            $this->fila([
                'idreporte' => 9, 'nombreporte' => 'RESUMEN GENERAL',
                'controlador' => 'reportes2/pdf_general/', 'tipo' => 'ADMINISTRACION',
                'variable' => 'cosa_inexistente', 'conte' => 1,
            ]),
        ]);

        $grupos = $servicio->agrupar($filas);
        $this->assertEquals('directo', $grupos['ADMINISTRACION'][0]['filtro']);
    }
}
