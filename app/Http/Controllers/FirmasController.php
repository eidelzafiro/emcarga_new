<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FirmasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Firma::class);
        $items = Firma::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('Firmas/Index', [
            'title' => 'Firmas Autorizadas',
            'items' => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Firma::class);
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'confecciona_nombre' => 'nullable|string|max:150',
            'confecciona_cargo' => 'nullable|string|max:150',
            'revisa_nombre' => 'nullable|string|max:150',
            'revisa_cargo' => 'nullable|string|max:150',
            'aprueba_nombre' => 'nullable|string|max:150',
            'aprueba_cargo' => 'nullable|string|max:150',
            'activo' => 'boolean',
        ]);
        $validated['id_entidad'] = (int) session('entidad_activa_id');
        Firma::create($validated);

        return redirect()->route('firmas.index')->with('success', 'Firma creada correctamente.');
    }

    public function update(Request $request, Firma $firma)
    {
        
        $this->authorize('update', $firma);
        $this->autorizarEntidad($firma->id_entidad);

        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'confecciona_nombre' => 'nullable|string|max:150',
            'confecciona_cargo' => 'nullable|string|max:150',
            'revisa_nombre' => 'nullable|string|max:150',
            'revisa_cargo' => 'nullable|string|max:150',
            'aprueba_nombre' => 'nullable|string|max:150',
            'aprueba_cargo' => 'nullable|string|max:150',
            'activo' => 'boolean',
        ]);
        $firma->update($validated);

        return redirect()->route('firmas.index')->with('success', 'Firma actualizada correctamente.');
    }

    public function destroy(Firma $firma)
    {
        
        $this->authorize('delete', $firma);
        $this->autorizarEntidad($firma->id_entidad);

        $firma->delete();

        return redirect()->route('firmas.index')->with('success', 'Firma eliminada correctamente.');
    }
}
