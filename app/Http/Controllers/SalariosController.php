<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\Salario;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalariosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Salario::class);
        $salarios = Salario::with(['bolsa', 'area', 'cargo', 'user'])
            ->when($request->search, fn ($q, $s) => $q->where('numero_nomina', 'like', "%{$s}%"))
            ->when($request->mes, fn ($q, $v) => $q->where('mes', $v))
            ->when($request->ano, fn ($q, $v) => $q->where('ano', $v))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->orderBy('ano', 'desc')
            ->orderBy('mes', 'desc')
            ->paginate(20);

        $bolsas = Bolsa::select('id', 'nombre', 'codigo')->orderBy('nombre')->get();
        $areas = Area::select('id', 'nombre')->orderBy('nombre')->get();
        $cargos = Cargo::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('Salarios/Index', [
            'title' => 'Salarios',
            'salarios' => $salarios,
            'bolsas' => $bolsas,
            'areas' => $areas,
            'cargos' => $cargos,
            'filters' => $request->only(['search', 'mes', 'ano']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Salario::class);
        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:2000',
            'id_bolsa' => 'required|exists:bolsa,id',
            'numero_nomina' => 'nullable|max:15',
            'salario_base' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:borrador,aprobado,cerrado',
        ]);
        $validated['id_entidad'] = (int) session('entidad_activa_id');
        $validated['id_user'] = auth()->id();
        Salario::create($validated);

        return redirect()->route('salarios.index')->with('success', 'Salario creado correctamente.');
    }

    public function update(Request $request, Salario $salario)
    {
        
        $this->authorize('update', $salario);
        $this->autorizarEntidad($salario->id_entidad);

        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:2000',
            'id_bolsa' => 'required|exists:bolsa,id',
            'numero_nomina' => 'nullable|max:15',
            'salario_base' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:borrador,aprobado,cerrado',
        ]);
        $salario->update($validated);

        return redirect()->route('salarios.index')->with('success', 'Salario actualizado correctamente.');
    }

    public function destroy(Salario $salario)
    {
        
        $this->authorize('delete', $salario);
        $this->autorizarEntidad($salario->id_entidad);

        $salario->delete();

        return redirect()->route('salarios.index')->with('success', 'Salario eliminado correctamente.');
    }
}
