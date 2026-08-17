<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Bolsa;
use App\Models\CombustibleCarga;
use App\Models\DetalleCargaCombustible;
use App\Models\Entidad;
use App\Models\Moneda;
use App\Models\Tarjeta;
use App\Models\TipoCombustible;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CombustibleCargasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $cargas = CombustibleCarga::with(['moneda:id,codigo', 'tipoCombustible:id,nombre', 'responsable:id,nombre,apellidos', 'detalles.tarjeta:id,numero'])
            ->whereYear('fcarga', $anio)->whereMonth('fcarga', $mes)
            ->when($request->search, fn ($q, $s) => $q->where('folio', 'like', "%{$s}%")
                ->orWhereHas('responsable', fn ($q2) => $q2->where('nombre', 'like', "%{$s}%")->orWhere('apellidos', 'like', "%{$s}%"))
                ->orWhereHas('detalles.tarjeta', fn ($q2) => $q2->where('numero', 'like', "%{$s}%")))
            ->when($request->id_tipo_combustible, fn ($q, $v) => $q->where('id_tipo_combustibles', $v))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderByDesc('fcarga')->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('CombustibleCargas/Index', [
            'title' => 'Carga Combustible',
            'cargas' => $cargas,
            'tiposCombustibles' => TipoCombustible::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'preciomn']),
            'monedas' => Moneda::where('activo', true)->orderBy('codigo')->get(['id', 'codigo', 'nombre']),
            'filtros' => [
                'empleados' => Bolsa::select('id', 'nombre', 'apellidos')
                    ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                    ->orderBy('nombre')->get(),
                'tarjetas' => Tarjeta::select('id', 'numero', 'saldo_actual', 'idmonedas', 'idtipocombustibles')
                    ->with('tipoCombustible:id,preciomn')
                    ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                    ->orderBy('numero')->get(),
                'entidades' => Entidad::select('id', 'abreviatura')
                    ->whereIn('id', $this->entidadesPermitidas())->orderBy('abreviatura')->get(),
            ],
            'fechaOperaciones' => $fechaOperaciones,
            'filters' => $request->only(['search', 'id_tipo_combustible']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fcarga' => 'required|date',
            'saldocargado' => 'required|numeric|min:0',
            'saldoxtarjeta' => 'required|numeric|min:0',
            'id_monedas' => 'nullable|exists:monedas,id',
            'id_tipo_combustibles' => 'required|exists:tipos_combustibles,id',
            'id_responsable' => 'nullable|exists:bolsa,id',
            'folio' => 'required|max:20',
            'notas' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_tarjeta' => 'required|exists:tarjetas,id',
            'detalles.*.saldo_mon' => 'required|numeric|min:0',
        ]);

        $detalles = $validated['detalles'];
        unset($validated['detalles']);

        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;
        $validated['id_user'] = auth()->id();
        $validated['estado'] = 'registrada';

        $carga = DB::transaction(function () use ($validated, $detalles, $request) {
            $carga = CombustibleCarga::create($validated);
            $this->crearDetalles($carga, $detalles, $validated['fcarga']);

            return $carga;
        });

        return redirect()->route('combustible-cargas.index')->with('success', 'Carga de combustible creada correctamente.');
    }

    public function update(Request $request, CombustibleCarga $combustibleCarga)
    {
        $this->autorizarEntidad($combustibleCarga->id_entidad);

        $validated = $request->validate([
            'fcarga' => 'required|date',
            'saldocargado' => 'required|numeric|min:0',
            'saldoxtarjeta' => 'required|numeric|min:0',
            'id_monedas' => 'nullable|exists:monedas,id',
            'id_tipo_combustibles' => 'required|exists:tipos_combustibles,id',
            'id_responsable' => 'nullable|exists:bolsa,id',
            'folio' => 'required|max:20',
            'notas' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.id' => 'nullable|exists:detalles_carga_combustible,id',
            'detalles.*.id_tarjeta' => 'required|exists:tarjetas,id',
            'detalles.*.saldo_mon' => 'required|numeric|min:0',
        ]);

        $detalles = $validated['detalles'];
        unset($validated['detalles']);

        DB::transaction(function () use ($combustibleCarga, $validated, $detalles) {
            $combustibleCarga->update($validated);
            $this->sincronizarDetalles($combustibleCarga, $detalles, $validated['fcarga']);
        });

        return redirect()->route('combustible-cargas.index')->with('success', 'Carga de combustible actualizada correctamente.');
    }

    public function destroy(CombustibleCarga $combustibleCarga)
    {
        $this->autorizarEntidad($combustibleCarga->id_entidad);

        DB::transaction(function () use ($combustibleCarga) {
            $combustibleCarga->detalles()->delete();
            $combustibleCarga->delete();
        });

        return redirect()->route('combustible-cargas.index')->with('success', 'Carga de combustible eliminada correctamente.');
    }

    private function crearDetalles(CombustibleCarga $carga, array $detalles, string $fcarga): void
    {
        foreach ($detalles as $detalle) {
            $litros = $this->calcularLitros($detalle['id_tarjeta'], $detalle['saldo_mon'], $carga->id_tipo_combustibles);
            DetalleCargaCombustible::create([
                'id_carga' => $carga->id,
                'id_tarjeta' => $detalle['id_tarjeta'],
                'fcarga' => $fcarga,
                'folio' => $carga->folio,
                'saldo_mon' => $detalle['saldo_mon'],
                'saldo_lts' => $litros,
            ]);
            Tarjeta::whereKey($detalle['id_tarjeta'])->update(['fmovimiento' => $fcarga]);
        }
    }

    private function sincronizarDetalles(CombustibleCarga $carga, array $detalles, string $fcarga): void
    {
        $enviados = collect($detalles)->pluck('id')->filter();
        $carga->detalles()->whereNotIn('id', $enviados)->delete();

        foreach ($detalles as $detalle) {
            $litros = $this->calcularLitros($detalle['id_tarjeta'], $detalle['saldo_mon'], $carga->id_tipo_combustibles);
            if (empty($detalle['id'])) {
                DetalleCargaCombustible::create([
                    'id_carga' => $carga->id,
                    'id_tarjeta' => $detalle['id_tarjeta'],
                    'fcarga' => $fcarga,
                    'folio' => $carga->folio,
                    'saldo_mon' => $detalle['saldo_mon'],
                    'saldo_lts' => $litros,
                ]);
            } else {
                $carga->detalles()->whereKey($detalle['id'])->update([
                    'id_tarjeta' => $detalle['id_tarjeta'],
                    'fcarga' => $fcarga,
                    'saldo_mon' => $detalle['saldo_mon'],
                    'saldo_lts' => $litros,
                ]);
            }
            Tarjeta::whereKey($detalle['id_tarjeta'])->update(['fmovimiento' => $fcarga]);
        }
    }

    private function calcularLitros(int $idTarjeta, float $saldoMon, ?int $idTipoCombustible): float
    {
        $precio = null;
        $tarjeta = Tarjeta::find($idTarjeta);
        if ($tarjeta?->idmonedas == 1) {
            $precio = $tarjeta->tipoCombustible?->preciomn;
        }
        if ($precio === null && $idTipoCombustible) {
            $precio = TipoCombustible::find($idTipoCombustible)?->preciomn;
        }

        return ($precio > 0) ? round($saldoMon / $precio, 2) : 0;
    }
}