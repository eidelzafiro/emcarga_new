<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\AmortizacionTaller;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AmortizacionTallerController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $this->authorize('viewAny', AmortizacionTaller::class);

        $amortizaciones = AmortizacionTaller::with(['tractivo:id,codigo', 'entidad:id,nombre'])
            ->when($request->search, fn ($q, $s) => $q->orWhereHas('tractivo', fn ($q2) => $q2->where('codigo', 'like', "%{$s}%")))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('AmortizacionTaller/Index', [
            'title' => 'Amortización Taller',
            'amortizaciones' => $amortizaciones,
            'tractivos' => Tractivo::select('id', 'codigo')
                ->where('activo', true)
                ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                ->orderBy('codigo')->limit(500)->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', AmortizacionTaller::class);

        $validated = $request->validate([
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'amortizacion_mn' => 'required|numeric|min:0',
            'chapa' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;

        AmortizacionTaller::create($validated);

        return redirect()->route('amortizacion-taller.index')->with('success', 'Amortización registrada correctamente.');
    }

    public function update(Request $request, AmortizacionTaller $amortizacionTaller)
    {
        $this->authorize('update', $amortizacionTaller);

        $validated = $request->validate([
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'amortizacion_mn' => 'required|numeric|min:0',
            'chapa' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $amortizacionTaller->update($validated);

        return redirect()->route('amortizacion-taller.index')->with('success', 'Amortización actualizada correctamente.');
    }

    public function destroy(AmortizacionTaller $amortizacionTaller)
    {
        $this->authorize('delete', $amortizacionTaller);
        $amortizacionTaller->delete();

        return redirect()->route('amortizacion-taller.index')->with('success', 'Amortización eliminada correctamente.');
    }
}
