<?php

namespace App\Http\Controllers;

use App\Models\OtrosAgregado;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OtrosAgregadosController extends Controller
{
    public function index(Request $request)
    {
        $agregados = OtrosAgregado::with('marca:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('descripcion', 'like', "%{$s}%"))
            ->paginate(20);

        return Inertia::render('OtrosAgregados/Index', [
            'title' => 'Otros Agregados',
            'agregados' => $agregados,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:otros_agregados,codigo',
            'descripcion' => 'required|string|max:255',
            'numero_serie' => 'nullable|string|max:100',
            'id_marca' => 'nullable|exists:marcas,id',
            'id_estado' => 'nullable|exists:estados_componentes,id',
            'fecha_baja' => 'nullable|date',
        ]);

        OtrosAgregado::create($validated);

        return redirect()->route('otros-agregados.index')
            ->with('success', 'Agregado creado correctamente.');
    }

    public function update(Request $request, OtrosAgregado $otrosAgregado)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:otros_agregados,codigo,' . $otrosAgregado->id,
            'descripcion' => 'required|string|max:255',
            'numero_serie' => 'nullable|string|max:100',
            'id_marca' => 'nullable|exists:marcas,id',
            'id_estado' => 'nullable|exists:estados_componentes,id',
            'fecha_baja' => 'nullable|date',
        ]);

        $otrosAgregado->update($validated);

        return redirect()->route('otros-agregados.index')
            ->with('success', 'Agregado actualizado correctamente.');
    }

    public function destroy(OtrosAgregado $otrosAgregado)
    {
        $otrosAgregado->delete();

        return redirect()->route('otros-agregados.index')
            ->with('success', 'Agregado eliminado correctamente.');
    }
}
