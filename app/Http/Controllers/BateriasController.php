<?php

namespace App\Http\Controllers;

use App\Models\Bateria;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BateriasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $baterias = Bateria::with('tractivo:id,descripcion,placa')
            ->when($request->search, fn ($q, $s) => $q->where('folio', 'like', "%{$s}%"))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->paginate(20);

        return Inertia::render('Baterias/Index', [
            'title' => 'Baterías',
            'baterias' => $baterias,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'folio' => 'required|unique:baterias,folio',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_instalacion' => 'nullable|date',
            'fecha_retiro' => 'nullable|date',
            'estado' => 'nullable|string|max:50',
        ]);

        $validated['id_entidad'] = (int) session('entidad_activa_id');

        Bateria::create($validated);

        return redirect()->route('baterias.index')
            ->with('success', 'Batería creada correctamente.');
    }

    public function update(Request $request, Bateria $bateria)
    {
        $this->autorizarEntidad($bateria->id_entidad);

        $validated = $request->validate([
            'folio' => 'required|unique:baterias,folio,'.$bateria->id,
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_instalacion' => 'nullable|date',
            'fecha_retiro' => 'nullable|date',
            'estado' => 'nullable|string|max:50',
        ]);

        $bateria->update($validated);

        return redirect()->route('baterias.index')
            ->with('success', 'Batería actualizada correctamente.');
    }

    public function destroy(Bateria $bateria)
    {
        $this->autorizarEntidad($bateria->id_entidad);

        $bateria->delete();

        return redirect()->route('baterias.index')
            ->with('success', 'Batería eliminada correctamente.');
    }
}
