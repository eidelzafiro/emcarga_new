<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ComercialController extends Controller
{
    /**
     * Vista de trabajo del módulo Comercial: pestañas con los CRUD de
     * hojas de ruta, solicitudes, cartas de porte, aforos y facturas
     * embebidos (iframe) para cambiar entre ellos sin cerrar los demás.
     */
    public function operaciones()
    {
        return Inertia::render('Comercial/Operaciones', [
            'title' => 'Comercial',
        ]);
    }
}
