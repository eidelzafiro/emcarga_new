<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Caja;
use App\Models\Lubricante;
use App\Models\Pais;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CajasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Caja::class);
        $cajas = Caja::with('tractivo:id,descripcion,placa', 'lubricante:id,nombre', 'pais:id,nombre')
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
            ->paginate(20);

        return Inertia::render('Cajas/Index', [
            'title' => 'Cajas',
            'cajas' => $cajas,
            'filtros' => [
                'lubricantes' => Lubricante::orderBy('nombre')->get(['id', 'nombre']),
                'paises' => Pais::orderBy('nombre')->get(['id', 'nombre']),
                'estados' => ['nuevo', 'activo', 'reparado', 'regular', 'baja'],
            ],
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Caja::class);
        $validated = $request->validate($this->reglas());
        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;

        Caja::create($validated);

        return redirect()->route('cajas.index')->with('success', 'Caja creada correctamente.');
    }

    public function update(Request $request, Caja $caja)
    {
        
        $this->authorize('update', $caja);
        $this->autorizarEntidad($caja->id_entidad);

        $caja->update($request->validate($this->reglas()));

        return redirect()->route('cajas.index')->with('success', 'Caja actualizada correctamente.');
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
            'id_pais' => 'nullable|exists:paises,id',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ];
    }
}
