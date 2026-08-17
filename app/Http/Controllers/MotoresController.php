<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Lubricante;
use App\Models\Motore;
use App\Models\Pais;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MotoresController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $motores = Motore::with('tractivo:id,descripcion,placa', 'lubricante:id,nombre', 'pais:id,nombre')
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

        return Inertia::render('Motores/Index', [
            'title' => 'Motores',
            'motores' => $motores,
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
        $validated = $request->validate($this->reglas());
        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;

        Motore::create($validated);

        return redirect()->route('motores.index')->with('success', 'Motor creado correctamente.');
    }

    public function update(Request $request, Motore $motore)
    {
        $this->autorizarEntidad($motore->id_entidad);

        $motore->update($request->validate($this->reglas()));

        return redirect()->route('motores.index')->with('success', 'Motor actualizado correctamente.');
    }

    public function destroy(Motore $motore)
    {
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
            'id_pais' => 'nullable|exists:paises,id',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ];
    }
}
