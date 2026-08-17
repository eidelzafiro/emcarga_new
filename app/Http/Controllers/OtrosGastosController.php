<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\OtrosGasto;
use App\Models\Tractivo;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OtrosGastosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $entidades = $this->entidadesPermitidas();

        $gastos = OtrosGasto::with(['bolsa', 'tractivo', 'tipoConcepto'])
            ->when($request->search, fn ($q, $s) => $q->where('concepto', 'like', "%{$s}%")->orWhere('descripcion', 'like', "%{$s}%"))
            ->when(! empty($entidades), fn ($q) => $q->where(function ($q) use ($entidades) {
                $q->whereHas('tractivo', fn ($t) => $t->whereIn('id_entidad', $entidades))
                    ->orWhereHas('bolsa', fn ($b) => $b->whereIn('id_entidad', $entidades));
            }))
            ->orderBy('fecha', 'desc')
            ->paginate(20);

        $bolsa = Bolsa::select('id', 'nombre')->orderBy('nombre')->get();
        $tractivos = Tractivo::select('id', 'codigo')->orderBy('codigo')->get();

        return Inertia::render('OtrosGastos/Index', [
            'title' => 'Otros Gastos',
            'gastos' => $gastos,
            'bolsa' => $bolsa,
            'tractivos' => $tractivos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_concepto' => 'required|exists:tipos_conceptos,id',
            'fecha' => 'required|date',
            'concepto' => 'required|max:255',
            'monto_mn' => 'required|numeric|min:0',
            'monto_mlc' => 'required|numeric|min:0',
            'descripcion' => 'nullable|max:500',
        ]);
        $this->autorizarEntidad($this->entidadTractivo($validated['id_tractivo']));
        OtrosGasto::create($validated);

        return redirect()->route('otros-gastos.index')->with('success', 'Gasto creado correctamente.');
    }

    public function update(Request $request, OtrosGasto $otrosGasto)
    {
        $this->autorizarEntidad($this->entidadDelGasto($otrosGasto));

        $validated = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_concepto' => 'required|exists:tipos_conceptos,id',
            'fecha' => 'required|date',
            'concepto' => 'required|max:255',
            'monto_mn' => 'required|numeric|min:0',
            'monto_mlc' => 'required|numeric|min:0',
            'descripcion' => 'nullable|max:500',
        ]);
        $this->autorizarEntidad($this->entidadTractivo($validated['id_tractivo']));
        $otrosGasto->update($validated);

        return redirect()->route('otros-gastos.index')->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(OtrosGasto $otrosGasto)
    {
        $this->autorizarEntidad($this->entidadDelGasto($otrosGasto));

        $otrosGasto->delete();

        return redirect()->route('otros-gastos.index')->with('success', 'Gasto eliminado correctamente.');
    }

    private function entidadTractivo(int $idTractivo): ?int
    {
        return Tractivo::find($idTractivo)?->id_entidad;
    }

    private function entidadDelGasto(OtrosGasto $gasto): ?int
    {
        return $gasto->tractivo?->id_entidad ?? $gasto->bolsa?->id_entidad;
    }
}
