<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Lubricante;
use App\Models\Motore;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MotoresController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {

        $this->authorize('viewAny', \App\Models\Motore::class);
        $motores = Motore::with('tractivo:id,codigo,placa', 'lubricante:id,nombre', 'pais:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('codigo', 'like', "%{$s}%")
                ->orWhere('numero_serie', 'like', "%{$s}%")
                ->orWhere('marca', 'like', "%{$s}%"))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return Inertia::render('Motores/Index', [
            'title' => 'Motores',
            'motores' => $motores,
            'filtros' => [
                'lubricantes' => Lubricante::orderBy('nombre')->get(['id', 'nombre']),
                'paises' => \App\Support\Catalogos::opciones('paises'),
                'tractivos' => Tractivo::orderBy('codigo')->get(['id', 'codigo', 'placa']),
                'estados' => ['disponible', 'nuevo', 'trabajando', 'reparado', 'regular', 'baja'],
            ],
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {

        $this->authorize('create', \App\Models\Motore::class);
        $validated = $request->validate($this->reglas());
        $validated['id_entidad'] = (int) entidadActivaId() ?: null;

        // Sin tractivo asignado el estado es obligatoriamente DISPONIBLE.
        if (empty($validated['id_tractivo'])) {
            $validated['id_tractivo'] = null;
            $validated['estado'] = 'disponible';
        }

        DB::transaction(function () use ($validated) {
            $motore = Motore::create($validated);

            if ($motore->id_tractivo) {
                \App\Models\Tractivo::where('id', $motore->id_tractivo)->update(['id_motor' => $motore->id]);
            }
        });

        return redirect()->route('motores.index')->with('success', 'Motor creado correctamente.');
    }

    public function update(Request $request, Motore $motore)
    {

        $this->authorize('update', $motore);
        $this->autorizarEntidad($motore->id_entidad);

        $validated = $request->validate($this->reglas());

        if (empty($validated['id_tractivo'])) {
            $validated['id_tractivo'] = null;
            // Solo un agregado sin tractivo es disponible; uno ya dado de baja
            // conserva su estado.
            if ($motore->estado !== 'baja') {
                $validated['estado'] = 'disponible';
            }
        } else {
            // Asignado a tractivo deja de estar disponible.
            if ($validated['estado'] === 'disponible') {
                $validated['estado'] = 'trabajando';
            }
        }

        DB::transaction(function () use ($motore, $validated) {
            $idAnterior = $motore->id_tractivo;
            $motore->update($validated);

            if ($idAnterior && $idAnterior !== $motore->id_tractivo) {
                \App\Models\Tractivo::where('id', $idAnterior)->where('id_motor', $motore->id)->update(['id_motor' => null]);
            }
            if ($motore->id_tractivo) {
                \App\Models\Tractivo::where('id', $motore->id_tractivo)->update(['id_motor' => $motore->id]);
            }
        });

        return redirect()->route('motores.index')->with('success', 'Motor actualizado correctamente.');
    }

    /**
     * Baja SIN cambio: el motor queda fuera de servicio y el tractivo
     * queda INACTIVO (no puede operar sin sus tres agregados).
     */
    public function baja(Request $request, Motore $motore)
    {

        $this->authorize('update', $motore);
        $this->autorizarEntidad($motore->id_entidad);

        app(\App\Services\AgregadosTractivoService::class)->darBaja($motore);

        return redirect()->route('motores.index')->with('success', 'Motor dado de baja. El tractivo quedó inactivo.');
    }

    public function destroy(Motore $motore)
    {

        $this->authorize('delete', $motore);
        $this->autorizarEntidad($motore->id_entidad);

        $motore->delete();

        return redirect()->route('motores.index')->with('success', 'Motor eliminado correctamente.');
    }

    private function reglas(): array
    {
        return [
            'codigo' => 'nullable|string|max:100',
            'numero_serie' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'cpl' => 'nullable|string|max:100',
            'caballaje' => 'nullable|integer',
            'cantidad_lubricante' => 'nullable|integer',
            'numero_tiempos' => 'nullable|integer',
            'numero_cilindros' => 'nullable|integer',
            'kms_acumulados' => 'nullable|integer',
            'capacidad_carter' => 'nullable|integer',
            'fecha_instalacion' => 'nullable|date',
            'fecha_baja' => 'nullable|date',
            'id_lubricante' => 'nullable|exists:lubricantes,id',
            'id_pais' => 'nullable|exists:catalogo_items,id',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ];
    }
}
