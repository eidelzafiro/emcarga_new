<?php

namespace App\Http\Controllers;

use App\Models\TipoTasa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposTasasController extends Controller
{
    public function index(Request $request)
    {
        $tipos = TipoTasa::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"))
            ->when(true, function ($q) {
                $entidadId = (int) session('entidad_activa_id');
                if ($entidadId) {
                    $q->where('id_entidad', $entidadId);
                }
                return $q;
            })
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TiposTasas/Index', [
            'title' => 'Tipos de Tasas',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_tasas,codigo|max:50',
            'nombre' => 'required|max:255',
            'unidad' => 'nullable|max:100',
            'valor' => 'nullable|numeric|min:0',
        ]);
        $validated['id_entidad'] = (int) session('entidad_activa_id');
        TipoTasa::create($validated);

        return redirect()->route('tipos-tasas.index')->with('success', 'Tipo de tasa creado correctamente.');
    }

    public function update(Request $request, TipoTasa $tiposTasa)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_tasas,codigo,'.$tiposTasa->id.'|max:50',
            'nombre' => 'required|max:255',
            'unidad' => 'nullable|max:100',
            'valor' => 'nullable|numeric|min:0',
        ]);
        $tiposTasa->update($validated);

        return redirect()->route('tipos-tasas.index')->with('success', 'Tipo de tasa actualizado correctamente.');
    }

    public function destroy(TipoTasa $tiposTasa)
    {
        $tiposTasa->delete();

        return redirect()->route('tipos-tasas.index')->with('success', 'Tipo de tasa eliminado correctamente.');
    }
}
