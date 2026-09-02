<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\GastoMaterial;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class GastoMaterialController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $this->authorize('viewAny', GastoMaterial::class);

        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $gastos = GastoMaterial::with(['tractivo:id,codigo', 'entidad:id,nombre'])
            ->whereYear('fecha', $anio)->whereMonth('fecha', $mes)
            ->when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")
                ->orWhere('elemento', 'like', "%{$s}%")
                ->orWhereHas('tractivo', fn ($q2) => $q2->where('codigo', 'like', "%{$s}%")))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('GastoMaterial/Index', [
            'title' => 'Gasto Material',
            'gastos' => $gastos,
            'tractivos' => Tractivo::select('id', 'codigo')
                ->where('activo', true)
                ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                ->orderBy('codigo')->limit(500)->get(),
            'fechaOperaciones' => $fechaOperaciones,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', GastoMaterial::class);

        $validated = $request->validate([
            'fecha' => 'required|date',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'nombre' => 'required|string|max:50',
            'elemento' => 'required|string|max:70',
            'cantidad' => 'required|numeric|min:0',
            'valor_mn' => 'required|numeric|min:0',
            'el_gas_mn' => 'nullable|string|max:10',
            'cup' => 'nullable|string|max:15',
            'nodoc' => 'nullable|integer',
            'observaciones' => 'nullable|string',
        ]);

        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;
        $validated['num_mov'] = GastoMaterial::max('num_mov') + 1;
        $validated['tipo_mov'] = 'E';

        GastoMaterial::create($validated);

        return redirect()->route('gasto-material.index')->with('success', 'Gasto registrado correctamente.');
    }

    public function update(Request $request, GastoMaterial $gastoMaterial)
    {
        $this->authorize('update', $gastoMaterial);

        $validated = $request->validate([
            'fecha' => 'required|date',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'nombre' => 'required|string|max:50',
            'elemento' => 'required|string|max:70',
            'cantidad' => 'required|numeric|min:0',
            'valor_mn' => 'required|numeric|min:0',
            'el_gas_mn' => 'nullable|string|max:10',
            'cup' => 'nullable|string|max:15',
            'nodoc' => 'nullable|integer',
            'observaciones' => 'nullable|string',
        ]);

        $gastoMaterial->update($validated);

        return redirect()->route('gasto-material.index')->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(GastoMaterial $gastoMaterial)
    {
        $this->authorize('delete', $gastoMaterial);
        $gastoMaterial->delete();

        return redirect()->route('gasto-material.index')->with('success', 'Gasto eliminado correctamente.');
    }
}
