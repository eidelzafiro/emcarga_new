<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Penalizacion;
use App\Models\TipoPenalizacione;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PenalizacionesController extends Controller
{
    public function index(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');

        $query = Penalizacion::with(['bolsa', 'tipoPenalizacion'])
            ->when($entidadId, fn ($q) => $q->whereHas('bolsa', fn ($sq) => $sq->where('id_entidad', $entidadId)))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->whereHas('bolsa', fn ($sq) => $sq->where('nombre', 'like', "%{$s}%")->orWhere('apellidos', 'like', "%{$s}%"))
                    ->orWhereHas('tipoPenalizacion', fn ($sq) => $sq->where('nombre', 'like', "%{$s}%"));
            }))
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc');

        $items = $query->paginate(20);
        $empleados = Bolsa::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->orderBy('nombre')
            ->get();
        $tipos = TipoPenalizacione::where('activo', true)->select('id', 'nombre', 'porcentaje')->orderBy('nombre')->get();

        return Inertia::render('Penalizaciones/Index', [
            'title' => 'Penalizaciones',
            'items' => $items,
            'empleados' => $empleados,
            'tiposPenalizaciones' => $tipos,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tipo_penalizacion' => 'required|exists:tipos_penalizaciones,id',
            'fecha' => 'required|date',
            'importe' => 'required|numeric|min:0|max:100',
        ]);

        Penalizacion::create($data);

        return redirect()->back()->with('success', 'Penalización registrada correctamente.');
    }

    public function update(Request $request, Penalizacion $penalizacion)
    {
        $data = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tipo_penalizacion' => 'required|exists:tipos_penalizaciones,id',
            'fecha' => 'required|date',
            'importe' => 'required|numeric|min:0|max:100',
        ]);

        $penalizacion->update($data);

        return redirect()->back()->with('success', 'Penalización actualizada correctamente.');
    }

    public function destroy(Penalizacion $penalizacion)
    {
        $penalizacion->delete();

        return redirect()->back()->with('success', 'Penalización eliminada correctamente.');
    }
}
