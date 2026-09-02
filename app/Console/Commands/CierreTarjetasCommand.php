<?php

namespace App\Console\Commands;

use App\Models\CierreTarjeta;
use App\Models\Tarjeta;
use App\Models\TipoCombustible;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CierreTarjetasCommand extends Command
{
    protected $signature = 'zafiro:cierre-tarjetas {--mes= : Mes a cerrar en formato YYYY-MM (default: mes anterior)}';

    protected $description = 'Realiza el cierre mensual de tarjetas de combustible';

    public function handle(): int
    {
        $mes = $this->option('mes')
            ? Carbon::parse($this->option('mes') . '-01')
            : Carbon::now()->subMonth()->startOfMonth();

        $mes->startOfMonth();
        $finMes = $mes->copy()->endOfMonth();

        $this->info("Cerrando mes: {$mes->format('Y-m')}");

        $tarjetas = Tarjeta::where('estado', 'activa')->get();
        $bar = $this->output->createProgressBar($tarjetas->count());
        $bar->start();

        $creados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($tarjetas, $mes, $finMes, &$creados, &$omitidos, $bar) {
            foreach ($tarjetas as $tarjeta) {
                // Verificar si ya existe cierre para este mes
                $existeCierre = CierreTarjeta::where('id_tarjeta', $tarjeta->id)
                    ->where('ftrabajo', '>=', $mes->toDateString())
                    ->where('ftrabajo', '<=', $finMes->toDateString())
                    ->exists();

                if ($existeCierre) {
                    $omitidos++;
                    $bar->advance();
                    continue;
                }

                // Saldo inicial: del último cierre o de la tarjeta
                $ultimoCierre = CierreTarjeta::where('id_tarjeta', $tarjeta->id)
                    ->where('ftrabajo', '<', $mes->toDateString())
                    ->orderByDesc('ftrabajo')
                    ->first();

                if ($ultimoCierre) {
                    $saldoInicialMon = (float) $ultimoCierre->saldoactualmon;
                    $saldoInicialLts = (float) $ultimoCierre->saldoactuallts;
                } else {
                    $saldoInicialMon = (float) ($tarjeta->saldoinicialmon ?? $tarjeta->saldo_actual);
                    $saldoInicialLts = (float) ($tarjeta->saldoiniciallts ?? $tarjeta->saldoactuallts);
                }

                // Suma de cargas del mes
                $cargas = DB::table('detalles_carga_combustible')
                    ->join('combustible_cargas', 'combustible_cargas.id', '=', 'detalles_carga_combustible.id_carga')
                    ->where('detalles_carga_combustible.id_tarjeta', $tarjeta->id)
                    ->where('combustible_cargas.fcarga', '>=', $mes->toDateString())
                    ->where('combustible_cargas.fcarga', '<=', $finMes->toDateString())
                    ->whereNull('combustible_cargas.deleted_at')
                    ->selectRaw('COALESCE(SUM(saldo_mon), 0) as total_mon, COALESCE(SUM(saldo_lts), 0) as total_lts')
                    ->first();

                $cargaMon = (float) $cargas->total_mon;
                $cargaLts = (float) $cargas->total_lts;

                // Suma de descargas del mes
                $descargas = DB::table('combustible_descargas')
                    ->where('id_tarjeta', $tarjeta->id)
                    ->where('fdescarga', '>=', $mes->toDateString())
                    ->where('fdescarga', '<=', $finMes->toDateString())
                    ->whereNull('deleted_at')
                    ->selectRaw('COALESCE(SUM(saldo_mon), 0) as total_mon, COALESCE(SUM(saldo_lts), 0) as total_lts')
                    ->first();

                $descargaMon = (float) $descargas->total_mon;
                $descargaLts = (float) $descargas->total_lts;

                // Saldo final
                $saldoActualMon = $saldoInicialMon + $cargaMon - $descargaMon;
                $saldoActualLts = $saldoInicialLts + $cargaLts - $descargaLts;

                // Precio del tipo de combustible
                $tipoCombustible = TipoCombustible::find($tarjeta->idtipocombustibles);
                $precioMn = $tipoCombustible ? (float) $tipoCombustible->preciomn : 0;

                CierreTarjeta::create([
                    'ftrabajo' => $finMes->toDateString(),
                    'id_tarjeta' => $tarjeta->id,
                    'codtm' => $tarjeta->numero,
                    'saldoinicialmon' => $saldoInicialMon,
                    'saldoiniciallts' => $saldoInicialLts,
                    'id_monedas' => $tarjeta->idmonedas,
                    'id_tipo_combustibles' => $tarjeta->idtipocombustibles,
                    'preciomn' => $precioMn,
                    'saldocargadomon' => $cargaMon,
                    'saldocargadolts' => $cargaLts,
                    'saldodescargadomon' => $descargaMon,
                    'saldodescargadolts' => $descargaLts,
                    'saldotransferenciamon' => 0,
                    'saldotransferencialts' => 0,
                    'saldoactualmon' => $saldoActualMon,
                    'saldoactuallts' => $saldoActualLts,
                    'id_entidad' => $tarjeta->id_entidad,
                ]);

                // Actualizar tarjeta con saldos finales
                $tarjeta->update([
                    'saldo_actual' => $saldoActualMon,
                    'saldoactuallts' => $saldoActualLts,
                    'fcierre' => $finMes->toDateString(),
                ]);

                $creados++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Cierre completado: {$creados} tarjetas cerradas, {$omitidos} omitidas (ya cerradas).");

        return self::SUCCESS;
    }
}
