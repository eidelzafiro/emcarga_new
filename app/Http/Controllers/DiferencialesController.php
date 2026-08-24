<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Diferenciale;
use App\Models\Lubricante;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DiferencialesController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {

        $this->authorize('viewAny', \App\Models\Diferenciale::class);
        $diferenciales = Diferenciale::with('tractivo:id,descripcion,placa', 'lubricante:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('descripcion', 'like', "%{$s}%")
                ->orWhere('codigo', 'like', "%{$s}%")
                ->orWhere('numero_serie', 'like', "%{$s}%"))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Diferenciales/Index', [
            'title' => 'Diferenciales',
            'diferenciales' => $diferenciales,
            'filtros' => [
                'lubricantes' => Lubricante::orderBy('nombre')->get(['id', 'nombre']),
                'tractivos' => Tractivo::orderBy('descripcion')->get(['id', 'codigo', 'descripcion', 'placa']),
                'estados' => ['disponible', 'nuevo', 'trabajando', 'reparado', 'regular', 'baja'],
            ],
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {

        $this->authorize('create', \App\Models\Diferenciale::class);
        $validated = $request->validate($this->reglas());

        $validated['id_entidad'] = (int) entidadActivaId() ?: null;

        // Sin tractivo asignado el estado es obligatoriamente DISPONIBLE.
        if (empty($validated['id_tractivo'])) {
            $validated['id_tractivo'] = null;
            $validated['estado'] = 'disponible';
        }

        DB::transaction(function () use ($validated) {
            $diferencial = Diferenciale::create($validated);

            if ($diferencial->id_tractivo) {
                \App\Models\Tractivo::where('id', $diferencial->id_tractivo)->update(['id_diferencial' => $diferencial->id]);
            }
        });

        return redirect()->route('diferenciales.index')
            ->with('success', 'Diferencial creado correctamente.');
    }

    public function update(Request $request, Diferenciale $diferencial)
    {

        $this->authorize('update', $diferencial);
        $this->autorizarEntidad($diferencial->id_entidad);

        $validated = $request->validate($this->reglas());

        if (empty($validated['id_tractivo'])) {
            $validated['id_tractivo'] = null;
            if ($diferencial->estado !== 'baja') {
                $validated['estado'] = 'disponible';
            }
        } else {
            if ($validated['estado'] === 'disponible') {
                $validated['estado'] = 'trabajando';
            }
        }

        DB::transaction(function () use ($diferencial, $validated) {
            $idAnterior = $diferencial->id_tractivo;
            $diferencial->update($validated);

            if ($idAnterior && $idAnterior !== $diferencial->id_tractivo) {
                \App\Models\Tractivo::where('id', $idAnterior)->where('id_diferencial', $diferencial->id)->update(['id_diferencial' => null]);
            }
            if ($diferencial->id_tractivo) {
                \App\Models\Tractivo::where('id', $diferencial->id_tractivo)->update(['id_diferencial' => $diferencial->id]);
            }
        });

        return redirect()->route('diferenciales.index')
            ->with('success', 'Diferencial actualizado correctamente.');
    }

    /**
     * Baja SIN cambio: el diferencial queda fuera de servicio y el tractivo
     * queda INACTIVO (no puede operar sin sus tres agregados).
     */
    public function baja(Request $request, Diferenciale $diferencial)
    {

        $this->authorize('update', $diferencial);
        $this->autorizarEntidad($diferencial->id_entidad);

        app(\App\Services\AgregadosTractivoService::class)->darBaja($diferencial);

        return redirect()->route('diferenciales.index')->with('success', 'Diferencial dado de baja. El tractivo quedó inactivo.');
    }

    public function destroy(Diferenciale $diferencial)
    {

        $this->authorize('delete', $diferencial);
        $this->autorizarEntidad($diferencial->id_entidad);

        $diferencial->delete();

        return redirect()->route('diferenciales.index')
            ->with('success', 'Diferencial eliminado correctamente.');
    }

    private function reglas(): array
    {
        return [
            'codigo' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
            // Ficha técnica
            'durabilidad' => 'nullable|numeric',
            'relacion' => 'nullable|string|max:50',
            'ancho' => 'nullable|numeric',
            'cantidad_lubricante' => 'nullable|numeric',
            'cantidad' => 'nullable|numeric',
            'kms_acumulados' => 'nullable|numeric',
            'capacidad_carter' => 'nullable|numeric',
            'fecha_instalacion' => 'nullable|date',
            'fecha_baja' => 'nullable|date',
            'id_lubricante' => 'nullable|exists:lubricantes,id',
        ];
    }
}
