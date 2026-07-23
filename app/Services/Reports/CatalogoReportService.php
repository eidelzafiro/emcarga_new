<?php

namespace App\Services\Reports;

use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Pais;

class CatalogoReportService extends BaseReportService
{
    public function pdfMarcas(): \Illuminate\Http\Response
    {
        $this->setTitle('Listado de Marcas');
        $marcas = Marca::where('activo', true)->orderBy('nombre')->get();
        return $this->streamPdf('reports.pdf.catalogos.lista', [
            'items' => $marcas,
            'campos' => ['Código', 'Nombre', 'Tipo'],
            'columnas' => ['codigo', 'nombre', 'tipo'],
        ]);
    }

    public function pdfModelos(): \Illuminate\Http\Response
    {
        $this->setTitle('Listado de Modelos');
        $items = Modelo::with('marca')->where('activo', true)->orderBy('nombre')->get();
        return $this->streamPdf('reports.pdf.catalogos.lista', [
            'items' => $items,
            'campos' => ['Código', 'Nombre', 'Marca'],
            'columnas' => ['codigo', 'nombre', 'marca.nombre'],
        ]);
    }

    public function pdfPaises(): \Illuminate\Http\Response
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
