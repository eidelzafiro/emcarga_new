<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\Entidad;
use App\Models\Plantilla;
use App\Models\TipoContrato;
use App\Models\TipoSistemaPago;
use App\Models\Turno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlantillaController extends Controller
{
    public function index(Request $request)
    {
        $items = Plantilla::with(['cargo', 'entidad', 'bolsa', 'turno', 'tipoContrato', 'tipoSistemaPago'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('codigo', 'like', "%{$s}%")
                    ->orWhere('nombre', 'like', "%{$s}%");
            }))
            ->orderBy('nombre')
            ->paginate(20);

        $cargos = Cargo::orderBy('nombre')->get(['id', 'nombre']);
        $entidades = Entidad::orderBy('nombre')->get(['id', 'nombre']);
        $bolsa = Bolsa::orderBy('nombre')->get(['id', 'nombre', 'apellidos']);
        $turnos = Turno::orderBy('nombre')->get(['id', 'nombre']);
        $tiposContratos = TipoContrato::orderBy('nombre')->get(['id', 'nombre']);
        $tiposSistemasPago = TipoSistemaPago::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Plantilla/Index', [
            'title' => 'Plantilla',
            'items' => $items,
            'cargos' => $cargos,
            'entidades' => $entidades,
            'bolsa' => $bolsa,
            'turnos' => $turnos,
            'tiposContratos' => $tiposContratos,
            'tiposSistemasPago' => $tiposSistemasPago,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:plantilla,codigo|max:50',
            'nombre' => 'required|max:255',
            'id_cargo' => 'nullable|exists:cargos,id',
            'id_entidad' => 'nullable|exists:entidades,id',
            'id_bolsa' => 'nullable|exists:bolsa,id',
            'id_turno' => 'nullable|exists:turnos,id',
            'id_tipo_contrato' => 'nullable|exists:tipos_contratos,id',
            'id_tipo_sistema_pago' => 'nullable|exists:tipos_sistemas_pago,id',
            'plazas' => 'integer|min:0',
            'cubiertas' => 'integer|min:0',
            'salario_base_mn' => 'nullable|numeric|min:0',
            'salario_base_mlc' => 'nullable|numeric|min:0',
            'categoria' => 'nullable|max:50',
            'aseo' => 'boolean',
        ]);
        Plantilla::create($validated);

        return redirect()->route('plantilla.index')->with('success', 'Registro creado correctamente.');
    }

    public function update(Request $request, Plantilla $plantilla)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:plantilla,codigo,'.$plantilla->id.'|max:50',
            'nombre' => 'required|max:255',
            'id_cargo' => 'nullable|exists:cargos,id',
            'id_entidad' => 'nullable|exists:entidades,id',
            'id_bolsa' => 'nullable|exists:bolsa,id',
            'id_turno' => 'nullable|exists:turnos,id',
            'id_tipo_contrato' => 'nullable|exists:tipos_contratos,id',
            'id_tipo_sistema_pago' => 'nullable|exists:tipos_sistemas_pago,id',
            'plazas' => 'integer|min:0',
            'cubiertas' => 'integer|min:0',
            'salario_base_mn' => 'nullable|numeric|min:0',
            'salario_base_mlc' => 'nullable|numeric|min:0',
            'categoria' => 'nullable|max:50',
            'aseo' => 'boolean',
        ]);
        $plantilla->update($validated);

        return redirect()->route('plantilla.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Plantilla $plantilla)
    {
        $plantilla->delete();

        return redirect()->route('plantilla.index')->with('success', 'Registro eliminado correctamente.');
    }
}
