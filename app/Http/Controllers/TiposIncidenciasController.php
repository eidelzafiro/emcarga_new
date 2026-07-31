<?php

namespace App\Http\Controllers;

class TiposIncidenciasController extends Controller
{
    public function index()
    {
        return redirect()->route('catalogo.index', ['tipo' => 'tipos_incidencias']);
    }

    public function store()
    {
        return redirect()->route('catalogo.index', ['tipo' => 'tipos_incidencias']);
    }

    public function update()
    {
        return redirect()->route('catalogo.index', ['tipo' => 'tipos_incidencias']);
    }

    public function destroy()
    {
        return redirect()->route('catalogo.index', ['tipo' => 'tipos_incidencias']);
    }
}
