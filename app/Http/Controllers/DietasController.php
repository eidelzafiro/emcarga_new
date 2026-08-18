<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Bolsa;
use App\Models\Dieta;
use App\Models\HojasRuta;
use App\Models\Moneda;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DietasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $dietas = Dieta::with([
            'bolsa:id,nombrecompleto',
            'hojaRuta:id,numero,id_tractivo',
            'hojaRuta.tractivo:id,codigo',
            'tractivo:id,codigo',
            'moneda:id,nombre',
        ])
            ->whereYear('fecha', $anio)->whereMonth('fecha', $mes)
            ->when($request->search, fn ($q, $s) => $q->where('folio', 'like', "%{$s}%")
                ->orWhereHas('bolsa', fn ($q2) => $q2->where('nombrecompleto', 'like', "%{$s}%"))
                ->orWhereHas('hojaRuta', fn ($q2) => $q2->where('numero', 'like', "%{$s}%")))
            ->when($request->canceladas === '1', fn ($q) => $q->where('cancelada', true))
            ->when($request->canceladas === '0', fn ($q) => $q->where('cancelada', false))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Dietas/Index', [
            'title' => 'Dietas',
            'dietas' => $dietas,
            'filtros' => [
                'bolsas' => Bolsa::select('id', 'nombrecompleto')
                    ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                    ->orderBy('nombrecompleto')->get(),
                'hojasRuta' => HojasRuta::select('id', 'numero', 'fecha_emision', 'id_tractivo')
                    ->with('tractivo:id,codigo')
                    ->whereYear('fecha_emision', $anio)->whereMonth('fecha_emision', $mes)
                    ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                    ->orderByDesc('fecha_emision')->limit(100)->get(),
                'tractivos' => Tractivo::select('id', 'codigo')
                    ->where('activo', true)->orderBy('codigo')->limit(500)->get(),
                'monedas' => Moneda::orderBy('nombre')->get(['id', 'nombre']),
            ],
            'fechaOperaciones' => $fechaOperaciones,
            'filters' => $request->only(['search', 'canceladas']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $validated['id_entidad'] = $this->entidadActiva();

        Dieta::create($validated);

        return redirect()->route('dietas.index')->with('success', 'Dieta creada correctamente.');
    }

    public function update(Request $request, Dieta $dieta)
    {
        $this->autorizarEntidad($dieta->id_entidad);
        $validated = $this->validar($request);

        if ($dieta->cancelada) {
            abort(422, 'No se puede editar una dieta cancelada.');
        }

        $dieta->update($validated);

        return redirect()->route('dietas.index')->with('success', 'Dieta actualizada correctamente.');
    }

    public function liquidar(Request $request, Dieta $dieta)
    {
        $this->autorizarEntidad($dieta->id_entidad);

        $validated = $request->validate([
            'f_liquidacion' => 'required|date',
            'folio_caja' => 'nullable|integer|min:0',
        ]);

        if ($dieta->cancelada) {
            abort(422, 'No se puede liquidar una dieta cancelada.');
        }

        $dieta->update([
            'f_liquidacion' => $validated['f_liquidacion'],
            'folio_caja' => $validated['folio_caja'] ?? null,
        ]);

        return redirect()->route('dietas.index')->with('success', 'Dieta liquidada correctamente.');
    }

    public function cancelar(Request $request, Dieta $dieta)
    {
        $this->autorizarEntidad($dieta->id_entidad);

        if ($dieta->f_liquidacion) {
            abort(422, 'No se puede cancelar una dieta ya liquidada.');
        }

        $dieta->update([
            'alimentos' => 0,
            'hospedaje' => 0,
            'otros' => 0,
            'monto' => 0,
            'cancelada' => true,
        ]);

        return redirect()->route('dietas.index')->with('success', 'Dieta cancelada correctamente.');
    }

    public function destroy(Dieta $dieta)
    {
        $this->autorizarEntidad($dieta->id_entidad);
        $dieta->delete();

        return redirect()->route('dietas.index')->with('success', 'Dieta eliminada correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_hoja_ruta' => 'required|exists:hojas_ruta,id',
            'folio' => 'nullable|string|max:10',
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0',
            'anticipo' => 'nullable|numeric|min:0',
            'f_anticipo' => 'nullable|date',
            'alimentos' => 'nullable|numeric|min:0',
            'hospedaje' => 'nullable|numeric|min:0',
            'otros' => 'nullable|numeric|min:0',
            'id_monedas' => 'nullable|exists:monedas,id',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'tipo_dieta' => 'nullable|string|max:50',
            'estado' => 'nullable|string|max:50',
        ]);
    }

    private function entidadActiva(): ?int
    {
        $id = (int) session('entidad_activa_id');

        return $id ?: null;
    }
}
