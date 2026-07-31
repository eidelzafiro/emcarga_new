<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use App\Models\Provincia;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MunicipiosController extends Controller
{
    public function index(Request $request)
    {
        $items = Municipio::with('provincia')
            ->when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->when($request->id_provincia, fn ($q, $v) => $q->where('id_provincia', $v))
            ->orderBy('nombre')
            ->paginate(20);

        $provincias = Provincia::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('Municipios/Index', [
            'title' => 'Municipios',
            'items' => $items,
            'provincias' => $provincias,
            'filters' => $request->only(['search', 'id_provincia']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_provincia' => 'required|exists:provincias,id',
        ]);
        Municipio::create($validated);

        return redirect()->route('municipios.index')->with('success', 'Municipio creado correctamente.');
    }

    public function update(Request $request, Municipio $municipio)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_provincia' => 'required|exists:provincias,id',
        ]);
        $municipio->update($validated);

        return redirect()->route('municipios.index')->with('success', 'Municipio actualizado correctamente.');
    }

    public function destroy(Municipio $municipio)
    {
        $municipio->delete();

        return redirect()->route('municipios.index')->with('success', 'Municipio eliminado correctamente.');
    }
}
