<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Models\MedioProteccion;
use App\Models\TipoMedioCargo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposMediosCargoController extends Controller
{
    public function index(Request $request)
    {
        $items = TipoMedioCargo::with(['medioProteccion', 'cargo'])
            ->when($request->id_medio_proteccion, fn ($q, $v) => $q->where('id_medio_proteccion', $v))
            ->paginate(20);

        $medios = MedioProteccion::select('id', 'nombre')->orderBy('nombre')->get();
        $cargos = Cargo::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('TiposMediosCargo/Index', [
            'items' => $items,
            'medios' => $medios,
            'cargos' => $cargos,
            'filters' => $request->only(['id_medio_proteccion']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_medio_proteccion' => 'required|exists:medios_proteccion,id',
            'id_cargo' => 'required|exists:cargos,id',
        ]);
        TipoMedioCargo::create($validated);

        return redirect()->route('tipos-medios-cargo.index')->with('success', 'Asignación creada correctamente.');
    }

    public function update(Request $request, TipoMedioCargo $tiposMediosCargo)
    {
        $validated = $request->validate([
            'id_medio_proteccion' => 'required|exists:medios_proteccion,id',
            'id_cargo' => 'required|exists:cargos,id',
        ]);
        $tiposMediosCargo->update($validated);

        return redirect()->route('tipos-medios-cargo.index')->with('success', 'Asignación actualizada correctamente.');
    }

    public function destroy(TipoMedioCargo $tiposMediosCargo)
    {
        $tiposMediosCargo->delete();

        return redirect()->route('tipos-medios-cargo.index')->with('success', 'Asignación eliminada correctamente.');
    }
}
