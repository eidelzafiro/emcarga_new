<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Caja;
use App\Models\Lubricante;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CajasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {

        $this->authorize('viewAny', \App\Models\Caja::class);
        $cajas = Caja::with('tractivo:id,codigo,placa', 'lubricante:id,nombre', 'pais:id,nombre')
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

        return Inertia::render('Cajas/Index', [
            'title' => 'Cajas',
            'cajas' => $cajas,
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

        $this->authorize('create', \App\Models\Caja::class);
        $validated = $request->validate($this->reglas());
        $validated['id_entidad'] = (int) entidadActivaId() ?: null;

        // Sin tractivo asignado el estado es obligatoriamente DISPONIBLE.
        if (empty($validated['id_tractivo'])) {
            $validated['id_tractivo'] = null;
            $validated['estado'] = 'disponible';
        }

        DB::transaction(function () use ($validated) {
            $caja = Caja::create($validated);

            if ($caja->id_tractivo) {
                \App\Models\Tractivo::where('id', $caja->id_tractivo)->update(['id_caja' => $caja->id]);
            }
        });

        return redirect()->route('cajas.index')->with('success', 'Caja creada correctamente.');
    }

    public function update(Request $request, Caja $caja)
    {

        $this->authorize('update', $caja);
        $this->autorizarEntidad($caja->id_entidad);

        $validated = $request->validate($this->reglas());

        if (empty($validated['id_tractivo'])) {
            $validated['id_tractivo'] = null;
            if ($caja->estado !== 'baja') {
                $validated['estado'] = 'disponible';
            }
        } else {
            if ($validated['estado'] === 'disponible') {
                $validated['estado'] = 'trabajando';
            }
        }

        DB::transaction(function () use ($caja, $validated) {
            $idAnterior = $caja->id_tractivo;
            $caja->update($validated);

            if ($idAnterior && $idAnterior !== $caja->id_tractivo) {
                \App\Models\Tractivo::where('id', $idAnterior)->where('id_caja', $caja->id)->update(['id_caja' => null]);
            }
            if ($caja->id_tractivo) {
                \App\Models\Tractivo::where('id', $caja->id_tractivo)->update(['id_caja' => $caja->id]);
            }
        });

        return redirect()->route('cajas.index')->with('success', 'Caja actualizada correctamente.');
    }

    /**
     * Baja SIN cambio: la caja queda fuera de servicio y el tractivo
     * queda INACTIVO (no puede operar sin sus tres agregados).
     */
    public function baja(Request $request, Caja $caja)
    {

        $this->authorize('update', $caja);
        $this->autorizarEntidad($caja->id_entidad);

        app(\App\Services\AgregadosTractivoService::class)->darBaja($caja);

        return redirect()->route('cajas.index')->with('success', 'Caja dada de baja. El tractivo quedó inactivo.');
    }

    public function destroy(Caja $caja)
    {

        $this->authorize('delete', $caja);
        $this->autorizarEntidad($caja->id_entidad);

        $caja->delete();

        return redirect()->route('cajas.index')->with('success', 'Caja eliminada correctamente.');
    }

    private function reglas(): array
    {
        return [
            'codigo' => 'nullable|string|max:100',
            'numero_serie' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'durabilidad' => 'nullable|integer',
            'velocidades' => 'nullable|integer',
            'cantidad_lubricante' => 'nullable|integer',
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
