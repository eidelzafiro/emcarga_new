<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\CombustibleDescarga;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\Servicentro;
use App\Models\Tarjeta;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CombustibleDescargasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $descargas = CombustibleDescarga::with([
            'tarjeta:id,numero',
            'hojaRuta:id,numero,id_tractivo',
            'hojaRuta.tractivo:id,codigo',
            'servicentro:id,nombre',
        ])
            ->whereYear('fdescarga', $anio)->whereMonth('fdescarga', $mes)
            ->when($request->search, fn ($q, $s) => $q->where('folio', 'like', "%{$s}%")
                ->orWhereHas('tarjeta', fn ($q2) => $q2->where('numero', 'like', "%{$s}%"))
                ->orWhereHas('hojaRuta', fn ($q2) => $q2->where('numero', 'like', "%{$s}%"))
                ->orWhereHas('hojaRuta.tractivo', fn ($q2) => $q2->where('codigo', 'like', "%{$s}%")))
            ->when($request->id_tarjeta, fn ($q, $v) => $q->where('id_tarjeta', $v))
            ->when($request->id_servicentro, fn ($q, $v) => $q->where('id_servicentro', $v))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderByDesc('fdescarga')->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('CombustibleDescargas/Index', [
            'title' => 'Descarga Combustible',
            'descargas' => $descargas,
            'filtros' => [
                'tarjetas' => Tarjeta::select('id', 'numero')
                    ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                    ->orderBy('numero')->get(),
                'servicentros' => Servicentro::select('id', 'nombre')
                    ->where('activo', true)
                    ->orderBy('nombre')->get(),
                'hojasRuta' => HojasRuta::select('id', 'numero', 'fecha_emision', 'id_tractivo')
                    ->with('tractivo:id,codigo')
                    ->whereYear('fecha_emision', $anio)->whereMonth('fecha_emision', $mes)
                    ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                    ->orderByDesc('fecha_emision')
                    ->limit(100)
                    ->get(),
                'entidades' => Entidad::select('id', 'abreviatura')
                    ->whereIn('id', $this->entidadesPermitidas())->orderBy('abreviatura')->get(),
            ],
            'fechaOperaciones' => $fechaOperaciones,
            'filters' => $request->only(['search', 'id_tarjeta', 'id_servicentro']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_tarjeta' => 'required|exists:tarjetas,id',
            'fdescarga' => 'required|date',
            'folio' => 'required|max:10',
            'saldo_mon' => 'required|numeric|min:0',
            'id_hoja_ruta' => 'required|exists:hojas_ruta,id',
            'hora_descarga' => 'nullable|max:10',
            'id_servicentro' => 'nullable|exists:servicentros,id',
            'f_chip' => 'nullable|date',
            'kms' => 'nullable|numeric|min:0',
        ]);

        $validated['saldo_lts'] = $this->calcularLitros($validated['id_tarjeta'], $validated['saldo_mon']);
        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;
        $validated['id_user'] = auth()->id();
        $validated['estado'] = 'registrada';

        $descarga = CombustibleDescarga::create($validated);

        Tarjeta::whereKey($descarga->id_tarjeta)->update(['fmovimiento' => $descarga->fdescarga]);

        return redirect()->route('combustible-descargas.index')->with('success', 'Descarga de combustible creada correctamente.');
    }

    public function update(Request $request, CombustibleDescarga $combustibleDescarga)
    {
        $this->autorizarEntidad($combustibleDescarga->id_entidad);

        $validated = $request->validate([
            'id_tarjeta' => 'required|exists:tarjetas,id',
            'fdescarga' => 'required|date',
            'folio' => 'required|max:10',
            'saldo_mon' => 'required|numeric|min:0',
            'id_hoja_ruta' => 'required|exists:hojas_ruta,id',
            'hora_descarga' => 'nullable|max:10',
            'id_servicentro' => 'nullable|exists:servicentros,id',
            'f_chip' => 'nullable|date',
            'kms' => 'nullable|numeric|min:0',
        ]);

        $validated['saldo_lts'] = $this->calcularLitros($validated['id_tarjeta'], $validated['saldo_mon']);

        $combustibleDescarga->update($validated);

        Tarjeta::whereKey($combustibleDescarga->id_tarjeta)->update(['fmovimiento' => $combustibleDescarga->fdescarga]);

        return redirect()->route('combustible-descargas.index')->with('success', 'Descarga de combustible actualizada correctamente.');
    }

    public function destroy(CombustibleDescarga $combustibleDescarga)
    {
        $this->autorizarEntidad($combustibleDescarga->id_entidad);

        $combustibleDescarga->delete();

        return redirect()->route('combustible-descargas.index')->with('success', 'Descarga de combustible eliminada correctamente.');
    }

    public function hojasRuta()
    {
        $entidadId = (int) session('entidad_activa_id');
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $ids = $this->entidadesPermitidas();

        return HojasRuta::with(['tractivo:id,codigo'])
            ->whereYear('fecha_emision', $anio)->whereMonth('fecha_emision', $mes)
            ->when(! empty($ids), fn ($q) => $q->whereIn('id_entidad', $ids))
            ->orderByDesc('fecha_emision')
            ->limit(50)
            ->get(['id', 'numero', 'fecha_emision', 'id_tractivo']);
    }

    private function calcularLitros(int $idTarjeta, float $saldoMon): float
    {
        $tarjeta = Tarjeta::find($idTarjeta);
        $precio = $tarjeta?->tipoCombustible?->preciomn;

        return ($precio > 0) ? round($saldoMon / $precio, 2) : 0;
    }
}