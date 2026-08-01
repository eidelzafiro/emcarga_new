<?php

namespace App\Services\Reports;

use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Pais;
use Illuminate\Http\Response;

class CatalogoReportService extends BaseReportService
{
    public function pdfMarcas(): Response
    {
        $this->setTitle('Listado de Marcas');
        $marcas = Marca::where('activo', true)->orderBy('nombre')->get();

        return $this->streamPdf('reports.pdf.catalogos.lista', [
            'items' => $marcas,
            'campos' => ['Código', 'Nombre', 'Tipo'],
            'columnas' => ['codigo', 'nombre', 'tipo'],
        ]);
    }

    public function pdfModelos(): Response
    {
        $this->setTitle('Listado de Modelos');
        $items = Modelo::where('activo', true)->orderBy('nombre')->get();

        return $this->streamPdf('reports.pdf.catalogos.lista', [
            'items' => $items,
            'campos' => ['Código', 'Nombre'],
            'columnas' => ['codigo', 'nombre'],
        ]);
    }

    public function pdfPaises(): Response
    {
        $this->setTitle('Listado de Países');
        $items = Pais::where('activo', true)->orderBy('nombre')->get();

        return $this->streamPdf('reports.pdf.catalogos.lista', [
            'items' => $items,
            'campos' => ['Código', 'Nombre'],
            'columnas' => ['codigo', 'nombre'],
        ]);
    }
}
