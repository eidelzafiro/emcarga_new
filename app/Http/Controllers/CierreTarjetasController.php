<?php

namespace App\Http\Controllers;

use App\Models\Tarjeta;
use App\Models\CierreTarjeta;
use App\Models\TipoCombustible;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class CierreTarjetasController extends Controller
{
    public function index()
    {
        $cierres = CierreTarjeta::query()
            ->selectRaw('ftrabajo, COUNT(*) as tarjetas_cerradas, SUM(saldoactualmon) as total_mn, SUM(saldoactuallts) as total_lts')
            ->groupBy('ftrabajo')
            ->orderBy('ftrabajo', 'desc')
            ->limit(12)
            ->get();

        $tarjetasActivas = Tarjeta::where('estado', 'activa')->count();

        return Inertia::render('Contabilidad/CierreTarjetas', [
            'cierres' => $cierres,
            'tarjetasActivas' => $tarjetasActivas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['mes' => 'required|date_format:Y-m']);

        $anio = (int) substr($request->mes, 0, 4);
        $mes = (int) substr($request->mes, 5, 2);
        $fechaInicio = "{$anio}-{$mes}-01";
        $fechaFin = date('Y-m-t', strtotime($fechaInicio));

        $yaCerrado = CierreTarjeta::where('ftrabajo', $fechaFin)->exists();
        if ($yaCerrado) {
            return back()->withErrors(['mes' => 'Ya existe un cierre para este mes.']);
        }

        $tarjetas = Tarjeta::where('estado', 'activa')->get();
        $cerradas = 0;
        $omitidas = 0;

        DB::transaction(function () use ($tarjetas, $fechaInicio, $fechaFin, &$cerradas, &$omitidas) {
            foreach ($tarjetas as $tarjeta) {
                $existe = CierreTarjeta::where('id_tarjeta', $tarjeta->id)
                    ->where('ftrabajo', $fechaFin)
                    ->exists();

                if ($existe) {
                    $omitidas++;
                    continue;
                }

                $ultimoCierre = CierreTarjeta::where('id_tarjeta', $tarjeta->id)
                    ->where('ftrabajo', '<', $fechaFin)
                    ->orderBy('ftrabajo', 'desc')
                    ->first();

                if ($ultimoCierre) {
                    $saldoInicialMN = (float) $ultimoCierre->saldoactualmon;
                    $saldoInicialLTS = (float) $ultimoCierre->saldoactuallts;
                } else {
                    $saldoInicialMN = (float) ($tarjeta->saldoinicialmon ?? $tarjeta->saldo_actual);
                    $saldoInicialLTS = (float) ($tarjeta->saldoiniciallts ?? $tarjeta->saldoactuallts);
                }

                $cargas = DB::table('detalles_carga_combustible')
                    ->join('combustible_cargas', 'combustible_cargas.id', '=', 'detalles_carga_combustible.id_carga')
                    ->where('detalles_carga_combustible.id_tarjeta', $tarjeta->id)
                    ->whereBetween('combustible_cargas.fcarga', [$fechaInicio, $fechaFin])
                    ->whereNull('combustible_cargas.deleted_at')
                    ->selectRaw('COALESCE(SUM(detalles_carga_combustible.saldo_mon), 0) as monto, COALESCE(SUM(detalles_carga_combustible.saldo_lts), 0) as litros')
                    ->first();

                $descargas = DB::table('combustible_descargas')
                    ->where('id_tarjeta', $tarjeta->id)
                    ->whereBetween('fdescarga', [$fechaInicio, $fechaFin])
                    ->whereNull('deleted_at')
                    ->selectRaw('COALESCE(SUM(saldo_mon), 0) as monto, COALESCE(SUM(saldo_lts), 0) as litros')
                    ->first();

                $saldoFinalMN = $saldoInicialMN + $cargas->monto - $descargas->monto;
                $saldoFinalLTS = $saldoInicialLTS + $cargas->litros - $descargas->litros;

                $tipoCombustible = TipoCombustible::find($tarjeta->idtipocombustibles);
                $precioMn = $tipoCombustible ? (float) $tipoCombustible->preciomn : 0;

                CierreTarjeta::create([
                    'ftrabajo' => $fechaFin,
                    'id_tarjeta' => $tarjeta->id,
                    'codtm' => $tarjeta->numero,
                    'saldoinicialmon' => $saldoInicialMN,
                    'saldoiniciallts' => $saldoInicialLTS,
                    'id_monedas' => $tarjeta->idmonedas,
                    'id_tipo_combustibles' => $tarjeta->idtipocombustibles,
                    'preciomn' => $precioMn,
                    'saldocargadomon' => $cargas->monto,
                    'saldocargadolts' => $cargas->litros,
                    'saldodescargadomon' => $descargas->monto,
                    'saldodescargadolts' => $descargas->litros,
                    'saldotransferenciamon' => 0,
                    'saldotransferencialts' => 0,
                    'saldoactualmon' => $saldoFinalMN,
                    'saldoactuallts' => $saldoFinalLTS,
                    'id_entidad' => $tarjeta->id_entidad,
                ]);

                $tarjeta->update([
                    'saldo_actual' => $saldoFinalMN,
                    'saldoactuallts' => $saldoFinalLTS,
                    'fcierre' => $fechaFin,
                ]);

                $cerradas++;
            }
        });

        return back()->with('success', "Cierre completado: {$cerradas} tarjetas cerradas, {$omitidas} omitidas.");
    }
}
