<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Bolsa;
use App\Models\Reembolso;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ReembolsosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Reembolso::class);

        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $reembolsos = Reembolso::with(['bolsa:id,nombrecompleto'])
            ->whereYear('fecha', $anio)->whereMonth('fecha', $mes)
            ->when($request->search, fn ($q, $s) => $q->where('concepto', 'like', "%{$s}%")
                ->orWhereHas('bolsa', fn ($q2) => $q2->where('nombre', 'like', "%{$s}%")->orWhere('apellidos', 'like', "%{$s}%")))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Reembolsos/Index', [
            'title' => 'Reembolsos',
            'reembolsos' => $reembolsos,
            'bolsas' => Bolsa::select('id', 'nombre', 'apellidos')
                ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                ->orderBy('nombre')->get(),
            'fechaOperaciones' => $fechaOperaciones,
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Reembolso::class);

        $validated = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0',
            'concepto' => 'required|string',
            'documentos' => 'nullable|string',
        ]);

        $validated['estado'] = 'pendiente';

        Reembolso::create($validated);

        return redirect()->route('reembolsos.index')->with('success', 'Reembolso creado correctamente.');
    }

    public function update(Request $request, Reembolso $reembolso)
    {
        $this->authorize('update', $reembolso);

        $validated = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0',
            'concepto' => 'required|string',
            'documentos' => 'nullable|string',
        ]);

        $reembolso->update($validated);

        return redirect()->route('reembolsos.index')->with('success', 'Reembolso actualizado correctamente.');
    }

    public function aprobar(Reembolso $reembolso)
    {
        $this->authorize('update', $reembolso);

        $reembolso->update(['estado' => 'aprobado']);

        return redirect()->route('reembolsos.index')->with('success', 'Reembolso aprobado.');
    }

    public function rechazar(Reembolso $reembolso)
    {
        $this->authorize('update', $reembolso);

        $reembolso->update(['estado' => 'rechazado']);

        return redirect()->route('reembolsos.index')->with('success', 'Reembolso rechazado.');
    }

    public function destroy(Reembolso $reembolso)
    {
        $this->authorize('delete', $reembolso);
        $reembolso->delete();

        return redirect()->route('reembolsos.index')->with('success', 'Reembolso eliminado correctamente.');
    }
}
