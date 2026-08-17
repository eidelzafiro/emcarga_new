<?php

namespace App\Console\Commands;

use App\Models\Aforo;
use App\Models\AforoLinea;
use App\Services\AforoCotizadorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Conciliación del motor de cálculo de aforo contra el legacy.
 *
 * Para cada aforo real (idcartaporte) recorre las entradas del cálculo en la BD
 * legacy (`com_girado`, `com_aforo`, `tec_tractivos`) y las reproduce con
 * `AforoCotizadorService`. Compara el resultado contra los valores migrados
 * (`aforos` + `aforo_lineas`), que provienen del legacy `com_aforo`.
 *
 * Objetivo (Fase 5 del plan de migración): detectar discrepancias del port
 * campo a campo para corregirlas hasta alcanzar paridad 1:1.
 */
class ConciliarAforos extends Command
{
    protected $signature = 'zafiro:conciliar-aforos
        {--ids= : Lista de idcartaporte separada por comas (por defecto: una muestra representativa)}
        {--limite=200 : Máximo de aforos a procesar}
        {--solo-discrepancias : Mostrar únicamente los aforos con diferencias}';

    protected $description = 'Recalcula aforos con AforoCotizadorService y compara campo a campo contra los valores migrados (legacy).';

    public function __construct(
        private AforoCotizadorService $cotizador,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ids = $this->option('ids')
            ? array_map('intval', explode(',', $this->option('ids')))
            : $this->idsMuestra((int) $this->option('limite'));

        if (empty($ids)) {
            $this->warn('No hay aforos para conciliar.');

            return 0;
        }

        $resumen = [
            'ok' => 0,
            'con_discrepancias' => 0,
            'campos_discrepantes' => [],
        ];

        foreach ($ids as $id) {
            $resultado = $this->conciliarAforo($id);
            $esOk = $resultado['discrepancias'] === [];

            if ($esOk) {
                $resumen['ok']++;
            } else {
                $resumen['con_discrepancias']++;
                foreach ($resultado['discrepancias'] as $campo => $detalle) {
                    $resumen['campos_discrepantes'][$campo] = ($resumen['campos_discrepantes'][$campo] ?? 0) + 1;
                }
            }

            if (! $esOk || ! $this->option('solo-discrepancias')) {
                $this->line($resultado['linea']);
            }
        }

        $this->newLine();
        $this->table(
            ['', 'Cantidad'],
            [
                ['Aforos procesados', count($ids)],
                ['Coinciden 1:1', $resumen['ok']],
                ['Con discrepancias', $resumen['con_discrepancias']],
            ]
        );

        if (! empty($resumen['campos_discrepantes'])) {
            $this->newLine();
            $this->warn('Campos con discrepancias (frecuencia):');
            arsort($resumen['campos_discrepantes']);
            $this->table(['Campo', 'Veces'], array_map(
                fn ($campo, $veces) => [$campo, $veces],
                array_keys($resumen['campos_discrepantes']),
                $resumen['campos_discrepantes']
            ));
        }

        return 0;
    }

    /**
     * Muestra representativa: un aforo por combinación de tipo de carga de la
     * línea 1 (cubre cereales, contenedores, TH, kms, etc.) más un rango variado.
     */
    private function idsMuestra(int $limite): array
    {
        $ids = DB::connection('legacy')->table('com_aforo')
            ->join('com_girado', 'com_girado.idcartaporte', '=', 'com_aforo.idcartaporte')
            ->whereYear('com_aforo.fparte', 2026)
            ->where('com_girado.idtipocarga1', '>', 0)
            ->groupBy('com_girado.idtipocarga1')
            ->orderBy('com_girado.idtipocarga1')
            ->get(['com_girado.idtipocarga1', DB::raw('MIN(com_aforo.idcartaporte) as id')])
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        return array_slice($ids, 0, $limite);
    }

    private function conciliarAforo(int $id): array
    {
        $linea = "Aforo #{$id}";

        // Datos de entrada desde el legacy
        $girado = DB::connection('legacy')->table('com_girado')->where('idcartaporte', $id)->first();
        $aforoLegacy = DB::connection('legacy')->table('com_aforo')->where('idcartaporte', $id)->first();

        if (! $girado || ! $aforoLegacy) {
            return ['discrepancias' => ['?'], 'linea' => $linea.' → sin datos legacy'];
        }

        $capacidad = (float) DB::connection('legacy')->table('tec_tractivos')
            ->where('idtractivos', $girado->idtractivos)
            ->value('capacidad') ?? 0;

        // Moneda: inferida desde el resultado guardado (fletemlc>0 ⇒ componente MLC=2)
        $moneda = $this->inferirMoneda($aforoLegacy);
        $mlc = (float) ($aforoLegacy->descuento ?? 0); // cpadescuento del form = % MLC
        $tipocont = (int) ($girado->idconttipo ?? 0);
        $cliente = (int) ($girado->idcliente ?? 0);
        $origen = (int) ($girado->idorigen ?? 0);
        $destino = (int) ($girado->iddestino ?? 0);
        $producto = (int) ($girado->idproducto1 ?? 0);

        $discrepancias = [];

        // ── Líneas 1-5 ──────────────────────────────────────────────
        $fletemtSum = 0.0;
        $fletemlcSum = 0.0;

        foreach (range(1, 5) as $pos) {
            $pesoCobrar = (float) ($aforoLegacy->{"pesocobrar{$pos}"} ?? 0);
            $tipocarga = (int) ($girado->{"idtipocarga{$pos}"} ?? 0);
            $distancia = $pos === 1
                ? (int) ($girado->distancia ?? 0)
                : (int) ($girado->{"distancia{$pos}"} ?? 0);
            $descuentoLinea = (float) ($aforoLegacy->{"desc{$pos}"} ?? 0);

            if ($tipocarga <= 0 || ($pesoCobrar <= 0 && ! in_array($tipocarga, [111]))) {
                continue;
            }

            // Tipos de acuerdo/manuales: importe se teclea, no es calculable automáticamente
            if (in_array($tipocarga, [16, 22, 23, 113])) {
                continue;
            }

            try {
                $calculado = $this->cotizador->calcularLinea(
                    moneda: $moneda,
                    tipocarga: $tipocarga,
                    distancia: $distancia,
                    peso: $pesoCobrar,
                    capacidad: $capacidad,
                    descuento: $descuentoLinea,
                    mlc: $mlc,
                    tipocont: $tipocont,
                    origen: $origen,
                    destino: $destino,
                    cliente: $cliente,
                    producto: $producto,
                );
            } catch (\Throwable $e) {
                $discrepancias["L{$pos}.error"] = "tc{$tipocarga}: {$e->getMessage()}";
                continue;
            }

            $tarmt = (float) ($calculado['tarmt'] ?? 0);
            $fletemt = (float) ($calculado['fletemt'] ?? 0);
            $fletemlc = (float) ($calculado['fletemlc'] ?? 0);

            $fletemtSum += $fletemt;
            $fletemlcSum += $fletemlc;

            // Valores migrados (legacy)
            $lineaMigrada = AforoLinea::where('id_aforo', $id)->where('posicion', $pos)->first();
            $tarMig = $lineaMigrada ? (float) $lineaMigrada->tarifa_mt : (float) ($aforoLegacy->{"tarmn{$pos}"} ?? 0);
            $fleMig = $lineaMigrada ? (float) $lineaMigrada->flete_mt : (float) ($aforoLegacy->{"fletemt{$pos}"} ?? 0);
            $flcMig = $lineaMigrada ? (float) $lineaMigrada->flete_mlc : (float) ($aforoLegacy->{"fletemlc{$pos}"} ?? 0);

            if (abs($tarmt - $tarMig) > 0.01) {
                $discrepancias["L{$pos}.tar"] = "tc{$tipocarga} calc=$tarmt mig=$tarMig";
            }
            if (abs($fletemt - $fleMig) > 0.01) {
                $discrepancias["L{$pos}.flete_mt"] = "tc{$tipocarga} calc=$fletemt mig=$fleMig";
            }
            if (abs($fletemlc - $flcMig) > 0.01) {
                $discrepancias["L{$pos}.flete_mlc"] = "tc{$tipocarga} calc=$fletemlc mig=$flcMig";
            }
        }

        // ── Totales ─────────────────────────────────────────────────
        $fleteAlm = (float) ($aforoLegacy->almflete ?? 0);
        $fletemttEsperado = round($fletemtSum + $fleteAlm, 2);
        $fletemlcEsperado = round($fletemlcSum, 2);

        $aforoNuevo = Aforo::find($id);
        if ($aforoNuevo) {
            if (abs($fletemttEsperado - (float) $aforoNuevo->flete_mt) > 0.01) {
                $discrepancias['total.flete_mt'] = "calc=$fletemttEsperado mig={$aforoNuevo->flete_mt}";
            }
            if (abs($fletemlcEsperado - (float) $aforoNuevo->flete_mlc) > 0.01) {
                $discrepancias['total.flete_mlc'] = "calc=$fletemlcEsperado mig={$aforoNuevo->flete_mlc}";
            }
        }

        // ── Demora ──────────────────────────────────────────────────
        $demcarga = (float) ($aforoLegacy->demcarga ?? 0);
        $demdescarga = (float) ($aforoLegacy->demdescarga ?? 0);
        $demtotal = $demcarga + $demdescarga;
        if ($demtotal > 0) {
            $calcDem = $this->cotizador->calcularDemora(
                tipocarga1: (int) ($girado->idtipocarga1 ?? 0),
                capacidad: $capacidad,
                demcarga: $demcarga,
                demdescarga: $demdescarga,
                descuento1: (float) ($aforoLegacy->desc7 ?? 0),
                descuento2: (float) ($aforoLegacy->desc8 ?? 0),
                horas: $demtotal,
                conttipo: $tipocont,
            );
            $fdem = (float) ($calcDem['fletedemt'] ?? 0);
            $fleteDemMig = (float) ($aforoLegacy->fletedemt ?? 0);
            if (abs($fdem - $fleteDemMig) > 0.01) {
                $discrepancias['demora.fletedemt'] = "calc=$fdem mig=$fleteDemMig";
            }
        }

        // ── Almacenaje ──────────────────────────────────────────────
        $almPeso = (float) ($aforoLegacy->almpeso ?? 0);
        if ($almPeso > 0) {
            $calcAlm = $this->cotizador->calcularAlmacenaje(
                alm_peso: $almPeso,
                alm_horas: (float) ($aforoLegacy->almhoras ?? 0),
                descuento: (float) ($aforoLegacy->desc6 ?? 0),
                tipocarga: (int) ($girado->idtipocarga1 ?? 0),
                tipocont: $tipocont,
            );
            $almFlete = (float) ($calcAlm['alm_flete'] ?? 0);
            $almFleteMig = (float) ($aforoLegacy->almflete ?? 0);
            if (abs($almFlete - $almFleteMig) > 0.01) {
                $discrepancias['almacenaje.flete'] = "calc=$almFlete mig=$almFleteMig";
            }
        }

        // ── Salario ─────────────────────────────────────────────────
        if ((float) ($aforoLegacy->salario ?? 0) > 0) {
            $ingresos = (float) ($aforoLegacy->ingresomt ?? 0);
            $calcSal = $this->cotizador->calcularSalario(
                tipocarga: (int) ($girado->idtipocarga1 ?? 0),
                capacidad: $capacidad,
                distancia: (int) ($girado->distancia ?? 0),
                ingresos: $ingresos,
                almacenaje: $fleteAlm,
                idchofer2: (int) ($girado->idchofer2 ?? 0),
                idEntidad: $this->entidadDelAforo($id),
            );
            $salario = (float) ($calcSal['salario'] ?? 0);
            $salarioMig = (float) ($aforoLegacy->salario ?? 0);
            if (abs($salario - $salarioMig) > 0.01) {
                $discrepancias['salario'] = "calc=$salario mig=$salarioMig";
            }
        }

        if ($discrepancias === []) {
            $linea .= ' ✅ 1:1';
        } else {
            $linea .= ' ⚠️ '.count($discrepancias).' discrep: '.implode(' | ', $discrepancias);
        }

        return ['discrepancias' => $discrepancias, 'linea' => $linea];
    }

    private function inferirMoneda(object $aforoLegacy): int
    {
        foreach (range(1, 5) as $pos) {
            if ((float) ($aforoLegacy->{"fletemlc{$pos}"} ?? 0) > 0) {
                return 2; // componente MLC
            }
        }

        return 1; // MN
    }

    /**
     * Entidad de la CP del aforo (vía solicitud, hoja de ruta o tractivo).
     */
    private function entidadDelAforo(int $id): int
    {
        $row = DB::table('aforos')
            ->join('cartas_porte', 'cartas_porte.id', '=', 'aforos.id_carta_porte')
            ->leftJoin('solicitudes_servicio', 'solicitudes_servicio.id', '=', 'cartas_porte.id_solicitud')
            ->leftJoin('hojas_ruta', 'hojas_ruta.id', '=', 'cartas_porte.id_hoja_ruta')
            ->where('aforos.id', $id)
            ->first([
                'solicitudes_servicio.id_entidad as ent_solicitud',
                'hojas_ruta.id_entidad as ent_hr',
            ]);

        if (! $row) {
            return 0;
        }

        return (int) ($row->ent_solicitud ?: $row->ent_hr ?: 0);
    }
}