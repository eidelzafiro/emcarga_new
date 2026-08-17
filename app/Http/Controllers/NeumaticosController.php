<?php

namespace App\Http\Controllers;

use App\Models\Neumatico;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NeumaticosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $neumaticos = Neumatico::with('tractivo:id,descripcion,placa')
            ->when($request->search, fn ($q, $s) => $q->where('folio', 'like', "%{$s}%"))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->paginate(20);

        return Inertia::render('Neumaticos/Index', [
            'title' => 'Neumáticos',
            'neumaticos' => $neumaticos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'folio' => 'required|unique:neumaticos,folio',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'medida' => 'nullable|string|max:50',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_instalacion' => 'nullable|date',
            'fecha_retiro' => 'nullable|date',
            'kilometraje' => 'nullable|numeric',
            'estado' => 'nullable|string|max:50',
        ]);

        $validated['id_entidad'] = (int) session('entidad_activa_id');

        Neumatico::create($validated);

        return redirect()->route('neumaticos.index')
            ->with('success', 'Neumático creado correctamente.');
    }

    public function update(Request $request, Neumatico $neumatico)
    {
        $this->autorizarEntidad($neumatico->id_entidad);

        $validated = $request->validate([
            'folio' => 'required|unique:neumaticos,folio,'.$neumatico->id,
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'medida' => 'nullable|string|max:50',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_instalacion' => 'nullable|date',
            'fecha_retiro' => 'nullable|date',
            'kilometraje' => 'nullable|numeric',
            'estado' => 'nullable|string|max:50',
        ]);

        $neumatico->update($validated);

        return redirect()->route('neumaticos.index')
            ->with('success', 'Neumático actualizado correctamente.');
    }

    public function destroy(Neumatico $neumatico)
    {
        $this->autorizarEntidad($neumatico->id_entidad);

        $neumatico->delete();

        return redirect()->route('neumaticos.index')
            ->with('success', 'Neumático eliminado correctamente.');
    }
}
