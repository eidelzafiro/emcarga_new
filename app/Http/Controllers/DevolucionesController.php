<?php

namespace App\Http\Controllers;

use App\Models\Devolucione;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DevolucionesController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Devolucione::class);
        $items = Devolucione::query()
            ->when($request->search, fn ($q, $s) => $q->where('observaciones', 'like', "%{$s}%"))
            ->orderBy('id')->paginate(50);

        return Inertia::render('Devoluciones/Index', [
            'items' => $items,
            'title' => 'Devoluciones',
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        
        $this->authorize('create', \App\Models\Devolucione::class);return redirect()->route('devoluciones.index');
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Devolucione::class);
        $validated = $request->validate([
            'id_carta_porte' => 'nullable|integer',
            'id_cliente' => 'nullable|integer',
            'id_cliente_mm' => 'nullable|integer',
            'id_tractivo' => 'nullable|integer',
            'id_empleado' => 'nullable|integer',
            'fecha' => 'nullable|date',
            'aumento_flete_mn' => 'nullable|numeric',
            'aumento_flete_me' => 'nullable|numeric',
            'aumento_demora' => 'nullable|numeric',
            'aumento_salario' => 'nullable|numeric',
            'aumento_alquiler' => 'nullable|numeric',
            'aumento_izaje' => 'nullable|numeric',
            'disminucion_flete_mn' => 'nullable|numeric',
            'disminucion_flete_me' => 'nullable|numeric',
            'disminucion_demora' => 'nullable|numeric',
            'disminucion_salario' => 'nullable|numeric',
            'disminucion_alquiler' => 'nullable|numeric',
            'disminucion_izaje' => 'nullable|numeric',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        Devolucione::create($validated);

        return redirect()->route('devoluciones.index')->with('success', 'Devolución creada.');
    }

    public function show($id)
    {
        return redirect()->route('devoluciones.index');
    }

    public function edit($id)
    {
        return redirect()->route('devoluciones.index');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_carta_porte' => 'nullable|integer',
            'id_cliente' => 'nullable|integer',
            'id_cliente_mm' => 'nullable|integer',
            'id_tractivo' => 'nullable|integer',
            'id_empleado' => 'nullable|integer',
            'fecha' => 'nullable|date',
            'aumento_flete_mn' => 'nullable|numeric',
            'aumento_flete_me' => 'nullable|numeric',
            'aumento_demora' => 'nullable|numeric',
            'aumento_salario' => 'nullable|numeric',
            'aumento_alquiler' => 'nullable|numeric',
            'aumento_izaje' => 'nullable|numeric',
            'disminucion_flete_mn' => 'nullable|numeric',
            'disminucion_flete_me' => 'nullable|numeric',
            'disminucion_demora' => 'nullable|numeric',
            'disminucion_salario' => 'nullable|numeric',
            'disminucion_alquiler' => 'nullable|numeric',
            'disminucion_izaje' => 'nullable|numeric',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $item = Devolucione::findOrFail($id);
        $item->update($validated);

        return redirect()->route('devoluciones.index')->with('success', 'Devolución actualizada.');
    }

    public function destroy($id)
    {
        $item = Devolucione::findOrFail($id);
        $item->delete();

        return redirect()->route('devoluciones.index')->with('success', 'Devolución eliminada.');
    }
}
