<?php

namespace App\Http\Controllers;

use App\Models\CartaPorte;
use App\Models\OtrosIngresosPre;
use App\Models\TipoIngreso;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OtrosIngresosPreController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\OtrosIngresosPre::class);
        $items = OtrosIngresosPre::with(['cartaPorte', 'tipoIngreso'])
            ->when($request->id_carta_porte, fn ($q, $v) => $q->where('id_carta_porte', $v))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('cartaPorte', fn ($c) => $c->where(function ($c2) {
                $c2->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $this->entidadesPermitidas()))
                    ->orWhereHas('solicitud', fn ($s) => $s->whereIn('id_entidad', $this->entidadesPermitidas()));
            })))
            ->orderBy('id', 'desc')
            ->paginate(20);

        $cartasPorte = CartaPorte::select('id', 'numero')->orderBy('numero')->get();
        $tiposIngreso = TipoIngreso::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('OtrosIngresosPre/Index', [
            'title' => 'Otros Ingresos',
            'items' => $items,
            'cartasPorte' => $cartasPorte,
            'tiposIngreso' => $tiposIngreso,
            'filters' => $request->only(['id_carta_porte']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\OtrosIngresosPre::class);
        $validated = $request->validate([
            'id_carta_porte' => 'required|exists:cartas_porte,id',
            'id_tipo_ingreso' => 'required|exists:tipo_ingresos,id',
            'cantidad' => 'required|integer|min:0',
            'importe_mn' => 'required|numeric|min:0',
        ]);
        $this->autorizarCartaPorte($validated['id_carta_porte']);
        OtrosIngresosPre::create($validated);

        return redirect()->route('otros-ingresos-pre.index')->with('success', 'Ingreso creado correctamente.');
    }

    public function update(Request $request, OtrosIngresosPre $otrosIngresosPre)
    {
        
        $this->authorize('update', $otrosIngresosPre);
        $this->autorizarEntidad($this->entidadCarta($otrosIngresosPre->id_carta_porte));

        $validated = $request->validate([
            'id_carta_porte' => 'required|exists:cartas_porte,id',
            'id_tipo_ingreso' => 'required|exists:tipo_ingresos,id',
            'cantidad' => 'required|integer|min:0',
            'importe_mn' => 'required|numeric|min:0',
        ]);
        $this->autorizarCartaPorte($validated['id_carta_porte']);
        $otrosIngresosPre->update($validated);

        return redirect()->route('otros-ingresos-pre.index')->with('success', 'Ingreso actualizado correctamente.');
    }

    public function destroy(OtrosIngresosPre $otrosIngresosPre)
    {
        
        $this->authorize('delete', $otrosIngresosPre);
        $this->autorizarEntidad($this->entidadCarta($otrosIngresosPre->id_carta_porte));

        $otrosIngresosPre->delete();

        return redirect()->route('otros-ingresos-pre.index')->with('success', 'Ingreso eliminado correctamente.');
    }

    /**
     * Entidad de la que deriva una carta de porte: su hoja de ruta o, en su
     * defecto, su solicitud de servicio.
     */
    private function entidadCarta(?int $idCartaPorte): ?int
    {
        $carta = CartaPorte::with('hojaRuta:id,id_entidad', 'solicitud:id,id_entidad')->find($idCartaPorte);

        return $carta?->hojaRuta?->id_entidad ?? $carta?->solicitud?->id_entidad;
    }

    private function autorizarCartaPorte(int $idCartaPorte): void
    {
        $this->autorizarEntidad($this->entidadCarta($idCartaPorte), 'No tiene permiso para operar con esta carta de porte.');
    }
}
