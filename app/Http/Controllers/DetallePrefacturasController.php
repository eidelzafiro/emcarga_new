<?php

namespace App\Http\Controllers;

use App\Models\DetallePrefactura;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DetallePrefacturasController extends Controller
{
    public function index()
    {
        $items = DetallePrefactura::orderBy('id')->paginate(50);
        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Detalle de Prefacturas',
            'route' => 'detalle-prefacturas',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Detalle de Prefactura',
            'route' => 'detalle-prefacturas',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        DetallePrefactura::create($validated);

        return redirect()->route('detalle-prefacturas.index')->with('success', 'Detalle de prefactura creado.');
    }

    public function show($id)
    {
        $item = DetallePrefactura::findOrFail($id);
        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Detalle de Prefactura',
            'route' => 'detalle-prefacturas',
        ]);
    }

    public function edit($id)
    {
        $item = DetallePrefactura::findOrFail($id);
        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Detalle de Prefactura',
            'route' => 'detalle-prefacturas',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = DetallePrefactura::findOrFail($id);
        $item->update($validated);

        return redirect()->route('detalle-prefacturas.index')->with('success', 'Detalle de prefactura actualizado.');
    }

    public function destroy($id)
    {
        $item = DetallePrefactura::findOrFail($id);
        $item->delete();

        return redirect()->route('detalle-prefacturas.index')->with('success', 'Detalle de prefactura eliminado.');
    }
}
