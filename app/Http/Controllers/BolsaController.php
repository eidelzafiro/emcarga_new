<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\Entidad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BolsaController extends Controller
{
    public function index(Request $request)
    {
        $items = Bolsa::with(['cargo', 'entidad'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nombre', 'like', "%{$s}%")
                  ->orWhere('apellidos', 'like', "%{$s}%")
                  ->orWhere('ci', 'like', "%{$s}%");
            }))
            ->orderBy('nombre')
            ->paginate(20);

        $cargos = Cargo::orderBy('nombre')->get(['id', 'nombre']);
        $entidades = Entidad::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Bolsa/Index', [
            'title' => 'Bolsa de Trabajo',
            'items' => $items,
            'cargos' => $cargos,
            'entidades' => $entidades,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ci' => 'required|unique:bolsa,ci|max:20',
            'nombre' => 'required|max:255',
            'apellidos' => 'required|max:255',
            'sexo' => 'nullable|max:1',
            'fecha_nacimiento' => 'nullable|date',
            'direccion' => 'nullable|max:500',
            'telefono' => 'nullable|max:100',
            'email' => 'nullable|email|max:255',
            'id_cargo' => 'nullable|exists:cargos,id',
            'id_entidad' => 'nullable|exists:entidades,id',
        ]);
        Bolsa::create($validated);
        return redirect()->route('bolsa.index')->with('success', 'Registro creado correctamente.');
    }

    public function update(Request $request, Bolsa $bolsa)
    {
        $validated = $request->validate([
            'ci' => 'required|unique:bolsa,ci,' . $bolsa->id . '|max:20',
            'nombre' => 'required|max:255',
            'apellidos' => 'required|max:255',
            'sexo' => 'nullable|max:1',
            'fecha_nacimiento' => 'nullable|date',
            'direccion' => 'nullable|max:500',
            'telefono' => 'nullable|max:100',
            'email' => 'nullable|email|max:255',
            'id_cargo' => 'nullable|exists:cargos,id',
            'id_entidad' => 'nullable|exists:entidades,id',
        ]);
        $bolsa->update($validated);
        return redirect()->route('bolsa.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Bolsa $bolsa)
    {
        $bolsa->delete();
        return redirect()->route('bolsa.index')->with('success', 'Registro eliminado correctamente.');
    }
}
