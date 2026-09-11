<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class OperativosController extends Controller
{
    /**
     * Vista de trabajo del módulo Operativos: pestañas con los CRUD de
     * hojas de ruta, solicitudes y cartas de porte embebidos (iframe) para
     * cambiar entre ellos sin cerrar los demás.
     */
    public function operaciones()
    {
        return Inertia::render('Operativos/Operaciones', [
            'title' => 'Operaciones',
        ]);
    }
}
