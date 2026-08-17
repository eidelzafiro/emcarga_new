<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Incidencia;
use App\Models\TipoIncidencia;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IncidenciasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $entidades = $this->entidadesPermitidas();

        $query = Incidencia::with(['bolsa', 'tipoIncidencia'])
            ->when(! empty($entidades), fn ($q) => $q->whereHas('bolsa', fn ($sq) => $sq->whereIn('id_entidad', $entidades)))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->whereHas('bolsa', fn ($sq) => $sq->where('nombre', 'like', "%{$s}%")->orWhere('apellidos', 'like', "%{$s}%"))
                    ->orWhereHas('tipoIncidencia', fn ($sq) => $sq->where('nombre', 'like', "%{$s}%"));
            }))
            ->orderBy('fecha_inicio', 'desc')
            ->orderBy('id', 'desc');

        $items = $query->paginate(20);
        $empleados = Bolsa::when(! empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->orderBy('nombre')
            ->get();
        $tipos = TipoIncidencia::where('activo', true)->select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('Incidencias/Index', [
            'title' => 'Incidencias',
            'items' => $items,
            'empleados' => $empleados,
            'tiposIncidencias' => $tipos,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tipo_incidencia' => 'required|exists:tipos_incidencias,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'periodo_actual' => 'required|numeric|min:0',
            'importe' => 'required|numeric|min:0',
        ]);

        $this->autorizarEntidad(Bolsa::find($data['id_bolsa'])?->id_entidad);

        Incidencia::create($data);

        return redirect()->back()->with('success', 'Incidencia registrada correctamente.');
    }

    public function update(Request $request, Incidencia $incidencia)
    {
        $this->autorizarEntidad($incidencia->bolsa?->id_entidad);

        $data = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tipo_incidencia' => 'required|exists:tipos_incidencias,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'periodo_actual' => 'required|numeric|min:0',
            'importe' => 'required|numeric|min:0',
        ]);

        $incidencia->update($data);

        return redirect()->back()->with('success', 'Incidencia actualizada correctamente.');
    }

    public function destroy(Incidencia $incidencia)
    {
        $this->autorizarEntidad($incidencia->bolsa?->id_entidad);

        $incidencia->delete();

        return redirect()->back()->with('success', 'Incidencia eliminada correctamente.');
    }
}
