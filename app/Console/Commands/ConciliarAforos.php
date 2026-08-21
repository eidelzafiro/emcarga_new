<?php

namespace App\Console\Commands;

use App\Models\Aforo;
use App\Models\AforoLinea;
use App\Models\ConfiguracionTarifa;
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
        {--solo-discrepancias : Mostrar únicamente los aforos con diferencias}
        {--anio= : Año de la config de tarifas a usar (p. ej. 2026 = config histórica com_tarconfigcarga46; por defecto la vigente)}
        {--tasa-guardada : Usar la tasa de salario guardada en com_aforo en lugar de la vigente de rh_tipotasas}';

    protected $description = 'Recalcula aforos con AforoCotizadorService y compara campo a campo contra los valores migrados (legacy).';

    public function __construct(
        private AforoCotizadorService $cotizador,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('anio')) {
            $this->cotizador->setAnio((int) $this->option('anio'));
        }

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
        $query = DB::connection('legacy')->table('com_aforo')
            ->join('com_girado', 'com_girado.idcartaporte', '=', 'com_aforo.idcartaporte')
            ->whereYear('com_aforo.fparte', 2026)
            ->where('com_girado.idtipocarga1', '>', 0);

        // --limite=0 ⇒ barrido completo (todos los aforos 2026).
        if ($limite === 0) {
            return $query->orderBy('com_girado.idtipocarga1')
                ->get(['com_girado.idtipocarga1', 'com_aforo.idcartaporte as id'])
                ->pluck('id')
                ->filter()
                ->values()
                ->all();
        }

        return $query->groupBy('com_girado.idtipocarga1')
            ->orderBy('com_girado.idtipocarga1')
            ->get(['com_girado.idtipocarga1', DB::raw('MIN(com_aforo.idcartaporte) as id')])
            ->pluck('id')
            ->filter()
            ->values()
            ->all();
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
        $tieneAcuerdoManual = false;

        // ── Líneas 1-5 ──────────────────────────────────────────────
        $fletemtSum = 0.0;
        $fletemlcSum = 0.0;

        // Config de tarifas del año (para inferir capacidad en kms vacíos).
        // Preferir la fila histórica (anio concreto) antes que la vigente (NULL).
        $anioOpt = $this->option('anio');
        $configTarifa = ConfiguracionTarifa::query()
            ->when($anioOpt, fn ($q) => $q->where('anio', (int) $anioOpt))
            ->orderByRaw('anio IS NULL ASC, id ASC')
            ->first();

        foreach (range(1, 5) as $pos) {
            $pesoCobrar = (float) ($aforoLegacy->{"pesocobrar{$pos}"} ?? 0);
            $tipocarga = (int) ($girado->{"idtipocarga{$pos}"} ?? 0);
            $distancia = $pos === 1
                ? (int) ($girado->distancia ?? 0)
                : (int) ($girado->{"distancia{$pos}"} ?? 0);

            // 7/14 (kms vacíos) y 8/116 (kms adicionales): el formulario legacy
            // SIEMPRE envía el peso de la línea como km (aforoCalcularKmsVacios y
            // aforoCalcularKmsAdicionales: kms = cpapesoN = pesocobrarN). El campo
            // `kmvacioN` guarda el km persistido y puede diferir del peso de la
            // línea; usarlo solo como respaldo cuando pesocobrar es 0. No castear
            // a int: el peso de la línea puede tener decimales (ej. 2380.95).
            if (in_array($tipocarga, [7, 8, 14, 116])) {
                $kmvacioPos = (float) ($aforoLegacy->{"kmvacio{$pos}"} ?? 0);
                $distancia = $pesoCobrar > 0 ? (float) $pesoCobrar : $kmvacioPos;
            }

            // Tipos tarifario (tc2/tc6/tc9...): si la distancia del formulario no
            // se persistió (com_girado.distancia=0) pero la tarifa guardada existe,
            // inferir el kms del tarifario que la produce (residuo de datos: el
            // input original no está disponible, pero el resultado lo revela).
            if ($distancia <= 0 && ! in_array($tipocarga, [7, 8, 14, 116, 111, 112, 10, 5, 114, 115, 15, 17, 16, 22, 23, 113, 109, 110])) {
                $tarMigLineaTar = (float) ($aforoLegacy->{"tarmn{$pos}"} ?? 0);
                $distInferida = $tarMigLineaTar > 0
                    ? $this->cotizador->inferirDistanciaDesdeTarifa($tipocarga, $tarMigLineaTar)
                    : null;
                if ($distInferida !== null) {
                    $distancia = $distInferida;
                }
            }

            $descuentoLinea = (float) ($aforoLegacy->{"desc{$pos}"} ?? 0);

            // Tipos de acuerdo/manuales: importe se teclea, no es calculable
            // automáticamente. Se evalúa ANTES del chequeo de peso (un acuerdo
            // puede tener pesocobrar=0).
            if (in_array($tipocarga, [16, 22, 23, 113])) {
                $tieneAcuerdoManual = true;
                continue;
            }

            if ($tipocarga <= 0 || ($pesoCobrar <= 0 && ! in_array($tipocarga, [111]))) {
                continue;
            }

            // 7/14 (kms vacíos) y 8/116 (kms adicionales): la tarifa depende de la
            // capacidad del tractivo (<=15 → tarifa baja, >15 → tarifa alta). Si
            // `tec_tractivos.capacidad` cambió después (en cualquier dirección),
            // el tarmt migrado revela la capacidad usada en el legacy: comparar
            // contra ambas tarifas y setear la capacidad que lo reproduzca.
            $capacidadLinea = $capacidad;
            if (in_array($tipocarga, [7, 8, 14, 116])) {
                $tarMigLinea = (float) ($aforoLegacy->{"tarmn{$pos}"} ?? 0);
                if ($configTarifa && $tarMigLinea > 0) {
                    $tarifaCapBaja = in_array($tipocarga, [8, 116])
                        ? (in_array($tipocarga, [116]) ? 36.96 : (float) $configTarifa->kms_adicionales_1)
                        : (float) $configTarifa->kms_vacio_1;
                    $tarifaCapAlta = in_array($tipocarga, [8, 116])
                        ? (in_array($tipocarga, [116]) ? 56.54 : (float) $configTarifa->kms_adicionales_2)
                        : (float) $configTarifa->kms_vacio_2;
                    if (abs($tarMigLinea - $tarifaCapAlta) <= 0.01) {
                        $capacidadLinea = 16; // > 15 → tarifa alta
                    } elseif (abs($tarMigLinea - $tarifaCapBaja) <= 0.01) {
                        $capacidadLinea = 15; // <= 15 → tarifa baja
                    }
                }
            }

            // Tarifa horaria (10/114/115): la tarifa depende de la capacidad
            // (o exige capacidad > 0 para calcular). El tarmt migrado revela la
            // capacidad usada en el legacy.
            if (in_array($tipocarga, [10, 114, 115])) {
                $tarMigLinea = (float) ($aforoLegacy->{"tarmn{$pos}"} ?? 0);
                if ($tarMigLinea > 0) {
                    if ($tipocarga === 115) {
                        $capacidadLinea = max($capacidadLinea, 16); // 1665 fija, exige capacidad > 0
                    } elseif ($tipocarga === 114) {
                        $capacidadLinea = abs($tarMigLinea - 2471.60) <= 0.01 ? 16 : 15;
                    } elseif ($configTarifa) {
                        if (abs($tarMigLinea - (float) $configTarifa->tarifa_horaria_2) <= 0.01) {
                            $capacidadLinea = 16;
                        } elseif (abs($tarMigLinea - (float) $configTarifa->tarifa_horaria_1) <= 0.01) {
                            $capacidadLinea = 15;
                        }
                    }
                }
            }

            try {
                $calculado = $this->cotizador->calcularLinea(
                    moneda: $moneda,
                    tipocarga: $tipocarga,
                    distancia: $distancia,
                    peso: $pesoCobrar,
                    capacidad: $capacidadLinea,
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
        if ($aforoNuevo && ! $tieneAcuerdoManual) {
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
            // El contenedor (tc3/4) se calculó con `cpaconttipo` del formulario
            // (1 o 2); `com_girado.idconttipo` es 0 y no lo refleja. El resultado
            // migrado `tardem1`/`tardem2` revela el valor usado: si coincide con
            // demora_cont_2 se usó conttipo=2, si no demora_cont_1.
            $conttipoDem = $tipocont;
            if (in_array((int) ($girado->idtipocarga1 ?? 0), [3, 4])) {
                $tardemMigrado = max(
                    (float) ($aforoLegacy->tardem1 ?? 0),
                    (float) ($aforoLegacy->tardem2 ?? 0),
                );
                if ($tardemMigrado > 0) {
                    $conttipoDem = $this->inferirConttipoDesdeDemora($tardemMigrado);
                }
            }
            // Aforos no-contenedor: la tarifa de demora depende de la capacidad
            // del tractivo (demora_1 si <=15, demora_2 si >15). Si la capacidad
            // cambió después (en cualquier dirección), el tardem migrado revela
            // la capacidad usada en el legacy.
            $capacidadDemora = $capacidad;
            if (! in_array((int) ($girado->idtipocarga1 ?? 0), [3, 4])) {
                $tardemMigrado = max(
                    (float) ($aforoLegacy->tardem1 ?? 0),
                    (float) ($aforoLegacy->tardem2 ?? 0),
                );
                if ($configTarifa && $tardemMigrado > 0) {
                    if (abs($tardemMigrado - (float) $configTarifa->demora_2) <= 0.01) {
                        $capacidadDemora = 16; // > 15 → demora_2
                    } elseif (abs($tardemMigrado - (float) $configTarifa->demora_1) <= 0.01) {
                        $capacidadDemora = 15; // <= 15 → demora_1
                    }
                }
            }
            $calcDem = $this->cotizador->calcularDemora(
                tipocarga1: (int) ($girado->idtipocarga1 ?? 0),
                capacidad: $capacidadDemora,
                demcarga: $demcarga,
                demdescarga: $demdescarga,
                descuento1: (float) ($aforoLegacy->desc7 ?? 0),
                descuento2: (float) ($aforoLegacy->desc8 ?? 0),
                horas: $demtotal,
                conttipo: $conttipoDem,
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
            // Igual que en demora: para tc3/4 el legacy usa 175 (conttipo=1) o
            // 210 (conttipo=2). Se infiere desde el resultado migrado `almflete`.
            $conttipoAlm = $tipocont;
            if (in_array((int) ($girado->idtipocarga1 ?? 0), [3, 4])) {
                $tarifaAlm = $almPeso > 0 ? round((float) $aforoLegacy->almflete / $almPeso, 2) : 0;
                $conttipoAlm = $tarifaAlm == 175.0 ? 1 : 2;
            }
            $calcAlm = $this->cotizador->calcularAlmacenaje(
                alm_peso: $almPeso,
                alm_horas: (float) ($aforoLegacy->almhoras ?? 0),
                descuento: (float) ($aforoLegacy->desc6 ?? 0),
                tipocarga: (int) ($girado->idtipocarga1 ?? 0),
                tipocont: $conttipoAlm,
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
                tasaFija: $this->option('tasa-guardada')
                    ? (float) ($aforoLegacy->tasa ?? 0)
                    : null,
                tasa2Fija: $this->option('tasa-guardada')
                    ? (float) ($aforoLegacy->tasa2 ?? 0)
                    : 0,
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
     * Infiere el conttipo (1/2) usado por el formulario legacy para la demora de
     * contenedores (tc3/4). El resultado migrado `tardem1` coincide con
     * `demora_cont_2` (→ conttipo=2) o con `demora_cont_1` (→ conttipo=1).
     */
    private function inferirConttipoDesdeDemora(float $tardemMigrado): int
    {
        $anio = $this->option('anio');
        $config = \App\Models\ConfiguracionTarifa::query()
            ->when($anio, fn ($q) => $q->where('anio', (int) $anio), fn ($q) => $q->whereNull('anio'))
            ->first();

        if ($config && abs($tardemMigrado - (float) $config->demora_cont_2) <= 0.01) {
            return 2;
        }

        return 1;
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