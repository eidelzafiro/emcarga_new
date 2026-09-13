<?php

namespace App\Services\Reports;

use App\Models\ReporteLegacy;
use App\Services\Reports\ReportesDispatcher;

/**
 * Fase A: catálogo de los reportes marcados como usados, agrupados por
 * variante de agrupación (`tipo`) de la tabla legacy `reportes`.
 *
 * Cada reporte trae la variable de configuración y el tipo de filtro que
 * necesita (ver BaseReportService::tipoFiltro) para reusar un único formulario
 * de filtro en el frontend.
 */
class ReporteCatalogoService extends BaseReportService
{
    /** Perfiles → columna en la tabla `reportes`. */
    private const PERFILES = [
        'rechum' => 'RECHUM',
        'com'    => 'COMERCIAL',
        'cont'   => 'CONTABILIDAD',
        'conte'  => 'CONFIGURACIONES',
        'tec'    => 'TECNICA',
        'teccom' => 'TECNICA+COMERCIAL',
    ];

    /**
     * Devuelve [tipo => [ reportes... ]] solo con los reportes marcados como
     * usados (al menos un perfil en 1).
     */
    public function usadosAgrupados(): array
    {
        $query = ReporteLegacy::query();
        $query->where(function ($q): void {
            foreach (array_keys(self::PERFILES) as $col) {
                $q->orWhere($col, 1);
            }
        });

        $rows = $query->orderBy('tipo')->orderBy('nombreporte')->get();

        return $this->agrupar($rows);
    }

    /**
     * Lógica pura de agrupación (testeable sin BD). Recibe una colección de
     * filas con las columnas idreporte, nombreporte, controlador, variable y
     * los flags de perfil.
     */
    public function agrupar(\Illuminate\Support\Collection $rows): array
    {
        $grupos = [];
        foreach ($rows as $r) {
            $perfiles = [];
            foreach (self::PERFILES as $col => $label) {
                if ($r->{$col}) {
                    $perfiles[] = $label;
                }
            }

            $grupos[$r->tipo][] = [
                'id'             => $r->idreporte,
                'nombre'         => $r->nombreporte,
                'controlador'    => $r->controlador,
                'variable'       => $r->variable,
                'perfiles'       => $perfiles,
                'filtro'         => $this->tipoFiltro((string) ($r->variable ?? '')),
                'es_exportacion' => ReportesDispatcher::esExportacion($r->idreporte),
            ];
        }

        return $grupos;
    }

    /**
     * Expone el tipo de filtro de una variable (mes/fecha/consecutivo/tractivo/
     * tarjeta/cliente/agrupacion/directo...) para que el controlador construya
     * los filtros de generación de los reportes agrupados.
     */
    public function tipoFiltroDe(string $variable): string
    {
        return $this->tipoFiltro($variable);
    }

    /**
     * Conteo de reportes usados por agrupación (para el resumen de la página).
     */
    public function resumenPorTipo(): array
    {
        $grupos = $this->usadosAgrupados();
        $resumen = [];
        foreach ($grupos as $tipo => $reportes) {
            $resumen[$tipo] = count($reportes);
        }

        return $resumen;
    }
}
