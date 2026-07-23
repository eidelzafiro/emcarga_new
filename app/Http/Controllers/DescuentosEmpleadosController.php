<?php

namespace App\Http\Controllers;

use App\Models\DescuentosEmpleado;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DescuentosEmpleadosController extends Controller
{
    public function index()
    {
        $items = DescuentosEmpleado::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Descuentos de Empleados',
            'route' => 'descuentos-empleados',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Descuento de Empleado',
            'route' => 'descuentos-empleados',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        DescuentosEmpleado::create($validated);

        return redirect()->route('descuentos-empleados.index')->with('success', 'Descuento de empleado creado.');
    }

    public function show($id)
    {
        $item = DescuentosEmpleado::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Descuento de Empleado',
            'route' => 'descuentos-empleados',
        ]);
    }

    public function edit($id)
    {
        $item = DescuentosEmpleado::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Descuento de Empleado',
            'route' => 'descuentos-empleados',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = DescuentosEmpleado::findOrFail($id);
        $item->update($validated);

        return redirect()->route('descuentos-empleados.index')->with('success', 'Descuento de empleado actualizado.');
    }

    public function destroy($id)
    {
        $item = DescuentosEmpleado::findOrFail($id);
        $item->delete();

        return redirect()->route('descuentos-empleados.index')->with('success', 'Descuento de empleado eliminado.');
    }
}
