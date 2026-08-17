<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CajasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $cajas = Caja::with('tractivo:id,descripcion,placa')
            ->when($request->search, fn ($q, $s) => $q->where('descripcion', 'like', "%{$s}%"))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->paginate(20);

        return Inertia::render('Cajas/Index', [
            'title' => 'Cajas',
            'cajas' => $cajas,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:cajas,codigo',
            'descripcion' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ]);

        $validated['id_entidad'] = (int) session('entidad_activa_id');

        Caja::create($validated);

        return redirect()->route('cajas.index')
            ->with('success', 'Caja creada correctamente.');
    }

    public function update(Request $request, Caja $caja)
    {
        $this->autorizarEntidad($caja->id_entidad);

        $validated = $request->validate([
            'codigo' => 'required|unique:cajas,codigo,'.$caja->id,
            'descripcion' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ]);

        $caja->update($validated);

        return redirect()->route('cajas.index')
            ->with('success', 'Caja actualizada correctamente.');
    }

    public function destroy(Caja $caja)
    {
        $this->autorizarEntidad($caja->id_entidad);

        $caja->delete();

        return redirect()->route('cajas.index')
            ->with('success', 'Caja eliminada correctamente.');
    }
}
