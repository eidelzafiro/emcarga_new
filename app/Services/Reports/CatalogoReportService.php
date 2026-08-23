<?php

namespace App\Services\Reports;

use App\Models\CatalogoItem;

class CatalogoReportService extends BaseReportService
{
    public function pdfMarcas(): Response
    {
        return $this->lista('marcas', 'Listado de Marcas', ['Código', 'Nombre', 'Tipo']);
    }

    public function pdfModelos(): Response
    {
        return $this->lista('modelos', 'Listado de Modelos', ['Código', 'Nombre', 'Tipo']);
    }

    public function pdfPaises(): Response
    {
        return $this->lista('paises', 'Listado de Países', ['Código', 'Nombre']);
    }

    /**
     * Listado genérico desde el catálogo unificado; los campos extra del
     * JSON (ej. "tipo") se exponen como columnas planas para la vista.
     */
    private function lista(string $tipo, string $titulo, array $campos): Response
    {
        $this->setTitle($titulo);
        $items = CatalogoItem::where('tipo', $tipo)->where('activo', true)
            ->orderBy('nombre')->get()
            ->map(function ($i) {
                $fila = ['codigo' => $i->codigo, 'nombre' => $i->nombre];
                foreach ((array) $i->extra as $k => $v) {
                    $fila[$k] = $v;
                }

                return (object) $fila;
            });

        $columnas = array_map(fn ($c) => strtolower(str_replace(' ', '', $c)) === 'código' ? 'codigo'
            : (strtolower($c) === 'nombre' ? 'nombre' : strtolower($c)), $campos);

        return $this->streamPdf('reports.pdf.catalogos.lista', [
            'items' => $items,
            'campos' => $campos,
            'columnas' => $columnas,
        ]);
    }
}
