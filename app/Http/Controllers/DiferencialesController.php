<?php

namespace App\Http\Controllers;

use App\Models\Diferenciale;
use App\Models\Lubricante;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiferencialesController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Diferenciale::class);
        $diferenciales = Diferenciale::with('tractivo:id,descripcion,placa', 'lubricante:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('descripcion', 'like', "%{$s}%")
                ->orWhere('codigo', 'like', "%{$s}%"))
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
                'estados' => ['nuevo', 'activo', 'reparado', 'regular', 'baja'],
            ],
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Diferenciale::class);
        $validated = $request->validate($this->reglas());

        $validated['id_entidad'] = (int) session('entidad_activa_id');

        Diferenciale::create($validated);

        return redirect()->route('diferenciales.index')
            ->with('success', 'Diferencial creado correctamente.');
    }

    public function update(Request $request, Diferenciale $diferencial)
    {
        
        $this->authorize('update', $diferencial);
        $this->autorizarEntidad($diferencial->id_entidad);

        $diferencial->update($request->validate($this->reglas()));

        return redirect()->route('diferenciales.index')
            ->with('success', 'Diferencial actualizado correctamente.');
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
