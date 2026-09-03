<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\CatalogoItem;
use App\Models\Penalizacion;
use App\Http\Controllers\Traits\EntidadScoping;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PenalizacionesController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Penalizacion::class);
        $entidades = $this->entidadesPermitidas();

        $query = Penalizacion::with(['bolsa', 'tipoPenalizacion'])
            ->when(!empty($entidades), fn ($q) => $q->whereHas('bolsa', fn ($sq) => $sq->whereIn('id_entidad', $entidades)))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->whereHas('bolsa', fn ($sq) => $sq->where('nombre', 'like', "%{$s}%")->orWhere('apellidos', 'like', "%{$s}%"))
                    ->orWhereHas('tipoPenalizacion', fn ($sq) => $sq->where('nombre', 'like', "%{$s}%"));
            }))
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc');

        $items = $query->paginate(20);
        $empleados = Bolsa::when(!empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->orderBy('nombre')
            ->get();
        $tipos = CatalogoItem::where('tipo', 'tipos_penalizaciones')
            ->where('activo', true)
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $fechaOps = session('fecha_operaciones');
        $fechaOperaciones = $fechaOps ? Carbon::parse($fechaOps) : Carbon::now();

        return Inertia::render('Penalizaciones/Index', [
            'title' => 'Penalizaciones',
            'items' => $items,
            'empleados' => $empleados,
            'tiposPenalizaciones' => $tipos,
            'filters' => $request->only('search'),
            'fechaOperaciones' => $fechaOperaciones->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Penalizacion::class);
        $data = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tipo_penalizacion' => 'required|exists:catalogo_items,id',
            'fecha' => 'required|date',
            'importe' => 'required|numeric|min:0|max:100',
        ]);

        $this->autorizarEntidad(Bolsa::find($data['id_bolsa'])?->id_entidad);

        Penalizacion::create($data);

        return redirect()->back()->with('success', 'Penalización registrada correctamente.');
    }

    public function update(Request $request, Penalizacion $penalizacion)
    {
        $this->authorize('update', $penalizacion);
        $this->autorizarEntidad($penalizacion->bolsa?->id_entidad);

        $data = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tipo_penalizacion' => 'required|exists:catalogo_items,id',
            'fecha' => 'required|date',
            'importe' => 'required|numeric|min:0|max:100',
        ]);

        $penalizacion->update($data);

        return redirect()->back()->with('success', 'Penalización actualizada correctamente.');
    }

    public function destroy(Penalizacion $penalizacion)
    {
        $this->authorize('delete', $penalizacion);
        $this->autorizarEntidad($penalizacion->bolsa?->id_entidad);

        $penalizacion->delete();

        return redirect()->back()->with('success', 'Penalización eliminada correctamente.');
    }
}
