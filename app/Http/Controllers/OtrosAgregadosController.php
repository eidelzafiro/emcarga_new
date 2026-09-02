<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\EstadoComponente;
use App\Models\OtrosAgregado;
use App\Models\Tractivo;
use App\Support\Catalogos;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OtrosAgregadosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\OtrosAgregado::class);
        $agregados = OtrosAgregado::with('marca:id,nombre', 'estado:id,nombre', 'tractivo:id,codigo,placa')
            ->when($request->search, fn ($q, $s) => $q->where('descripcion', 'like', "%{$s}%"))
            ->whereIn('id_entidad', $this->entidadesPermitidas())
            ->paginate(2000);

        return Inertia::render('OtrosAgregados/Index', [
            'title' => 'Otros Agregados',
            'agregados' => $agregados,
            'filters' => $request->only(['search']),
            'catalogos' => [
                'marcas' => Catalogos::opciones('marcas'),
            ],
            'estados' => EstadoComponente::orderBy('nombre')->get(['id', 'nombre']),
            'tractivos' => Tractivo::orderBy('codigo')
                ->get(['id', 'codigo', 'placa'])
                ->map(fn ($t) => ['id' => $t->id, 'descripcion' => $t->descripcion]),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\OtrosAgregado::class);
        $validated = $request->validate([
            'codigo' => 'required|unique:otros_agregados,codigo',
            'descripcion' => 'required|string|max:255',
            'numero_serie' => 'nullable|string|max:100',
            'id_marca' => 'nullable|exists:catalogo_items,id',
            'id_estado' => 'nullable|exists:estados_componentes,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'fecha_instalado' => 'nullable|date',
            'km_acumulados' => 'nullable|integer',
            'km_retirarse' => 'nullable|integer',
            'notas' => 'nullable|string',
            'fecha_baja' => 'nullable|date',
        ]);

        // Regla 2026-08-28: la entidad se deriva del tractivo asignado.
        $validated['id_entidad'] = Tractivo::where('id', $validated['id_tractivo'])->value('id_entidad');

        OtrosAgregado::create($validated);

        return redirect()->route('otros-agregados.index')
            ->with('success', 'Agregado creado correctamente.');
    }

    public function update(Request $request, OtrosAgregado $otrosAgregado)
    {
        
        $this->authorize('update', $otrosAgregado);
        $this->autorizarEntidad($otrosAgregado->id_entidad);
        $validated = $request->validate([
            'codigo' => 'required|unique:otros_agregados,codigo,'.$otrosAgregado->id,
            'descripcion' => 'required|string|max:255',
            'numero_serie' => 'nullable|string|max:100',
            'id_marca' => 'nullable|exists:catalogo_items,id',
            'id_estado' => 'nullable|exists:estados_componentes,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'fecha_instalado' => 'nullable|date',
            'km_acumulados' => 'nullable|integer',
            'km_retirarse' => 'nullable|integer',
            'notas' => 'nullable|string',
            'fecha_baja' => 'nullable|date',
        ]);

        // Regla 2026-08-28: la entidad se deriva del tractivo asignado.
        $validated['id_entidad'] = Tractivo::where('id', $validated['id_tractivo'])->value('id_entidad');

        $otrosAgregado->update($validated);

        return redirect()->route('otros-agregados.index')
            ->with('success', 'Agregado actualizado correctamente.');
    }

    public function destroy(OtrosAgregado $otrosAgregado)
    {
        
        $this->authorize('delete', $otrosAgregado);
        $this->autorizarEntidad($otrosAgregado->id_entidad);
        $otrosAgregado->delete();

        return redirect()->route('otros-agregados.index')
            ->with('success', 'Agregado eliminado correctamente.');
    }
}
