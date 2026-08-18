<?php

namespace App\Http\Controllers;

use App\Models\DescuentosEmpleado;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DescuentosEmpleadosController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\DescuentosEmpleado::class);
        $items = DescuentosEmpleado::query()
            ->when($request->search, fn ($q, $s) => $q->where('motivo', 'like', "%{$s}%"))
            ->orderBy('id')->paginate(50);

        return Inertia::render('DescuentosEmpleados/Index', [
            'items' => $items,
            'title' => 'Desc. Empleados',
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        
        $this->authorize('create', \App\Models\DescuentosEmpleado::class);return redirect()->route('descuentos-empleados.index');
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\DescuentosEmpleado::class);
        $validated = $request->validate([
            'id_empleado' => 'nullable|integer',
            'fecha_inicio' => 'nullable|date',
            'tiempo' => 'nullable|numeric|min:0',
            'motivo' => 'nullable|string|max:1000',
        ]);

        DescuentosEmpleado::create($validated);

        return redirect()->route('descuentos-empleados.index')->with('success', 'Descuento de empleado creado.');
    }

    public function show($id)
    {
        return redirect()->route('descuentos-empleados.index');
    }

    public function edit($id)
    {
        return redirect()->route('descuentos-empleados.index');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_empleado' => 'nullable|integer',
            'fecha_inicio' => 'nullable|date',
            'tiempo' => 'nullable|numeric|min:0',
            'motivo' => 'nullable|string|max:1000',
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
