<?php

namespace App\Services;

use App\Models\ConfiguracionTarifa;
use App\Models\Tarifa;
use App\Models\TarifaAcuerdo;
use App\Models\Tasa;
use App\Models\TipoCarga;

/**
 * Motor de cálculo de aforo (cotización en vivo).
 *
 * Réplica 1:1 de la lógica del legacy CI3 (`Aforo.php` controller + `modAforo.php`
 * model). Cada método público corresponde a un endpoint AJAX del formulario legacy
 * y mantiene la misma semántica de redondeo y de moneda (1=MN, 2=componente MLC).
 * El MLC puro (moneda 4) fue eliminado por decisión de negocio (2026-08-12).
 *
 * Fuente única de configuración: `ConfiguracionTarifa` (configuraciones_tarifa), que
 * fusiona las tablas legacy `com_tarconfigcarga` + `com_tarconfigcont`.
 */
class AforoCotizadorService
{
    public function redondeado(float $numero, int $decimales): float
    {
        $factor = pow(10, $decimales);

        return round($numero * $factor) / $factor;
    }

    /**
     * Normaliza un array de resultados: convierte valores numéricos (string/int)
     * a float, manteniendo los vacíos ('') como string vacío para paridad con legacy.
     */
    protected function normalizar(array $arr): array
    {
        foreach ($arr as $clave => $valor) {
            if ($valor === '' || $valor === null) {
                $arr[$clave] = '';
            } else {
                $arr[$clave] = (float) $valor;
            }
        }

        return $arr;
    }

    /**
     * Convierte "HH:MM" a minutos (equivalent legacy `cambiarEnteroMinutos`).
     */
    public function cambiarEnteroMinutos(string|int $valor): int
    {
        if (! $valor || $valor == 0) {
            return 0;
        }
        $partes = explode(':', (string) $valor);
        $h = (int) ($partes[0] ?? 0);
        $m = (int) ($partes[1] ?? 0);

        return ($h * 60) + $m;
    }

    /**
     * Convierte minutos a "HH:MM" (equivalente legacy `cambiarMinutosEntero`).
     */
    public function cambiarMinutosEntero(int|float $minutos): string
    {
        $minutos = (int) round($minutos);
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        return str_pad((string) $h, 2, '0', STR_PAD_LEFT).':'.str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Mapea los tipos de carga de distribución (104-108) al tarifario.
     */
    public function mapearTipoCarga(int $tipocarga): int
    {
        return match ($tipocarga) {
            104 => 2,  // DIST.GENERAL
            105 => 6,  // DIST.FURGON
            106 => 1,  // DIST.LIQUIDOS
            107 => 12, // DIST.VAGON SECO
            108 => 11, // DIST.VAGON REFRIGERADO
            default => $tipocarga,
        };
    }

    /**
     * ¿El tipo de carga usa el tarifario por tabla?
     * En el nuevo esquema `tipos_cargas` no conserva la bandera `tabla` del legacy
     * (com_tipocargas.tabla==1). Se asume true salvo que el tipo no exista.
     */
    public function usaTarifario(int $tipocarga): bool
    {
        if ($tipocarga <= 0) {
            return false;
        }

        return TipoCarga::query()->where('id', $tipocarga)->exists();
    }

    /**
     * Lee la fila del tarifario según distancia y tipo de carga.
     * El legacy usa `kms` como clave de fila y `tarifa_mt` como tarifa por tonelada.
     *
     * El tarifario corriente vive en `com_tarifas46` (versión `46`); los tipos
     * especiales 117/118 viven en `com_tarifas` (versión `normal`).
     */
    protected function tarifaRow(int $tipocarga, int $distancia, string $version = '46'): ?object
    {
        return Tarifa::query()
            ->where('id_tipo_carga', $tipocarga)
            ->where('kms', $distancia)
            ->where('version', $version)
            ->first();
    }

    /**
     * Endpoint `tarifa` / `modAforo::mostrar_tarifa`.
     *
     * @return array{tarmt: float|string, fletemt: float|string, fletemlc: float|string}
     */
    public function tarifa(
        int $moneda = 1,
        int $tipocarga = 2,
        int $distancia = 0,
        float $peso = 0,
        float $descuento = 0,
        float $mlc = 0,
    ): array {
        $tipocarga = $this->mapearTipoCarga($tipocarga);
        $arr = ['tarmt' => '', 'fletemt' => '', 'fletemlc' => ''];

        // Contenedores: todo por la tarifa 3 (paridad legacy mostrar_tarifa)
        if ($tipocarga === 3 || $tipocarga === 4) {
            $tipocarga = 3;
        }

        if (! $this->usaTarifario($tipocarga)) {
            return $arr;
        }

        // Cereales (18): tarifa por km
        if ($tipocarga === 18) {
            $row = $this->tarifaRow($tipocarga, $distancia);
            if ($row) {
                $arr['tarmt'] = round((float) $row->tarifa_mt / $distancia, 2);
                $arr['fletemt'] = (float) $row->tarifa_mt;
            }
            if ($descuento > 0) {
                $arr['fletemt'] = round($arr['fletemt'] - ($arr['fletemt'] * ($descuento / 100)), 2);
            }

            return $this->normalizar($arr);
        }

        // 117/118: de la tabla normal (com_tarifas), tar x distancia
        if ($tipocarga === 117 || $tipocarga === 118) {
            $row = $this->tarifaRow($tipocarga, $distancia, 'normal');
            if ($row) {
                $arr['tarmt'] = (float) $row->tarifa_mt;
                $arr['fletemt'] = round((float) $row->tarifa_mt * $distancia, 2);
            }
            if ($descuento > 0) {
                $arr['fletemt'] = round((float) $arr['fletemt'] - ((float) $arr['fletemt'] * ($descuento / 100)), 2);
            }

            return $this->normalizar($arr);
        }

        $row = $this->tarifaRow($tipocarga, $distancia);
        if (! $row) {
            return $this->normalizar($arr);
        }

        switch ($moneda) {
            case 2: // COMPONENTE MLC
                $arr['tarmt'] = (float) $row->tarifa_mt;
                $arr['fletemt'] = round((float) $row->tarifa_mt * $peso, 2);
                $arr['fletemlc'] = round(((float) $arr['fletemt'] / 24) * ($mlc / 100), 2);
                $fdescuento = round((float) $arr['fletemt'] * ($descuento / 100), 2);
                $arr['fletemt'] = round((float) $arr['fletemt'] - $fdescuento, 2);
                break;

            default: // MN puro (MLC puro/moneda 4 eliminado: no existe en el negocio actual)
                $arr['tarmt'] = (float) $row->tarifa_mt;
                $arr['fletemt'] = round((float) $row->tarifa_mt * $peso, 2);
                $fdescuentomn = round((float) $arr['fletemt'] * ($descuento / 100), 2);
                $arr['fletemt'] = round((float) $arr['fletemt'] - $fdescuentomn, 2);
                break;
        }

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `aforoCalcularImportes` (dispatcher por tipo de carga): decide qué
     * cálculo usar según el tipocarga y devuelve tarmt/fletemt/fletemlc.
     * Réplica del dispatcher del formulario legacy (com.js).
     *
     * @return array{tarmt: float|string, fletemt: float|string, fletemlc: float|string}
     */
    public function calcularLinea(
        int $moneda = 1,
        int $tipocarga = 0,
        int $distancia = 0,
        float $peso = 0,
        float $capacidad = 0,
        float $descuento = 0,
        float $mlc = 0,
        int $tipocont = 1,
        bool|string $comb = false,
        int $origen = 0,
        int $destino = 0,
        int $cliente = 0,
        int $producto = 0,
        int $kms_vacio_tarifa_previa = 0,
    ): array {
        $descuento = min($descuento, 100);
        $mlc = min($mlc, 100);

        // 111: KMS COSTO (70/km)
        if ($tipocarga === 111) {
            return $this->calcularKmsCosto($distancia);
        }
        // 112: RES 207 (tons*kms*tarifa)
        if ($tipocarga === 112) {
            return $this->calcularKmsRes207($distancia, $peso, $comb);
        }
        // 109 / 110: fletes fijos por km
        if ($tipocarga === 109) {
            return ['tarmt' => 105, 'fletemt' => $this->redondeado(105 * $distancia, 2), 'fletemlc' => ''];
        }
        if ($tipocarga === 110) {
            return ['tarmt' => 130, 'fletemt' => $this->redondeado(130 * $distancia, 2), 'fletemlc' => ''];
        }
        // 7 / 14: KMS VACIOS
        if ($tipocarga === 7 || $tipocarga === 14) {
            return $this->calcularKmsVacios($tipocarga, $moneda, $distancia, $kms_vacio_tarifa_previa, $peso, $mlc, $descuento);
        }
        // 10 / 5 / 114 / 115: TARIFA HORARIA
        if (in_array($tipocarga, [10, 5, 114, 115])) {
            $th = $this->calcularTh($tipocarga, $capacidad, $descuento, 1, $moneda, $tipocont);
            // Nota: el legacy llama calcularTH con las horas; aquí se usa la tarifa
            // horaria como tarmt y el flete por hora se aplica aparte.
            return ['tarmt' => $th['tarmt'], 'fletemt' => $th['fth'], 'fletemlc' => ''];
        }
        // 8 / 116: KMS ADICIONALES
        if ($tipocarga === 8 || $tipocarga === 116) {
            return $this->calcularKmsAdicionales($tipocarga, $distancia, $mlc, $capacidad, $descuento);
        }
        // 15: TH EFECTOS
        if ($tipocarga === 15) {
            return $this->calcularThEfectos($tipocarga, $capacidad, $descuento, 1);
        }
        // 17: ESTIBADORES (% del flete por piso)
        if ($tipocarga === 17) {
            return $this->calcularEstibadores($distancia, $peso, $descuento);
        }
        // 23: ACUERDOS de cliente
        if ($tipocarga === 23) {
            $ac = $this->tarifaAcuerdos($cliente, $origen, $destino, $producto);
            if (! empty($ac)) {
                $fila = $ac[0];
                $flete = (float) ($fila['flete_mt'] ?? 0);
                $fletemlc = $flete > 0 ? round(($flete / 24) * ($mlc / 100), 2) : 0;

                return ['tarmt' => (float) ($fila['tarifa_mt'] ?? 0), 'fletemt' => $flete, 'fletemlc' => $fletemlc];
            }

            return ['tarmt' => '', 'fletemt' => '', 'fletemlc' => ''];
        }

        // Por defecto: tarifario (tarifa/104-108)
        return $this->tarifa($moneda, $tipocarga, $distancia, $peso, $descuento, $mlc);
    }

    /**
     * Endpoint `dif_horas`: resta horas, redondea hacia arriba si hay minutos y
     * descuenta las horas libres según el peso (config hora_1..3).
     */
    public function difHoras(string $hora1, string $hora2, float $peso): int
    {
        $hor = 0;
        $libre = 0;

        if ($hora2 > $hora1 && $hora1 !== $hora2) {
            $inicio = strtotime($hora1);
            $fin = strtotime($hora2);
            $dif = $fin - $inicio;
            $hor = intdiv($dif, 3600);
            $min = intdiv($dif % 3600, 60);
            if ($min > 0) {
                $hor++;
            }
        }

        if ($hor > 0) {
            $config = $this->config();
            if ($peso >= $config->hora_1 && $peso < $config->hora_2) {
                $libre = 1;
            }
            if ($peso >= $config->hora_2 && $peso < $config->hora_3) {
                $libre = 2;
            }
            if ($peso >= $config->hora_3) {
                $libre = 3;
            }
            $hor = round($hor - $libre);
        }

        if ($hor < 0) {
            $hor = 0;
        }

        return $hor;
    }

    /**
     * Endpoint `calcular_th`: tarifa horaria.
     *
     * @return array{tarmt: float|string, fth: float|string}
     */
    public function calcularTh(
        int $tipocarga = 0,
        float $capacidad = 0,
        float $descuento = 0,
        int $horas = 0,
        int $moneda = 1,
        int $tipocont = 1,
    ): array {
        $arr = ['tarmt' => '', 'fth' => ''];
        if ($horas <= 0 || $capacidad <= 0) {
            return $this->normalizar($arr);
        }

        $config = $this->config();

        if ($tipocarga === 10) {
            $arr['tarmt'] = $capacidad <= 15 ? $config->tarifa_horaria_1 : $config->tarifa_horaria_2;
        }
        if ($tipocarga === 5) {
            $arr['tarmt'] = $tipocont == 1 ? $config->tarifa_horaria_1 : $config->tarifa_horaria_2;
        }
        if ($tipocarga === 114) {
            $arr['tarmt'] = $capacidad <= 15 ? 1646.40 : 2471.60;
        }
        if ($tipocarga === 115) {
            $arr['tarmt'] = 1665;
        }

        $arr['fth'] = round($arr['tarmt'] * $horas, 2);
        if ($descuento > 0) {
            $th_descuento = round($arr['fth'] * ($descuento / 100), 2);
            $arr['fth'] = round($arr['fth'] - $th_descuento, 2);
        }

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_kmscosto`: tarifa fija de 70 por km.
     *
     * @return array{tarmt: float|string, fletemt: float|string}
     */
    public function calcularKmsCosto(int $kms = 0): array
    {
        $arr = ['tarmt' => '', 'fletemt' => ''];
        if ($kms > 0) {
            $arr['tarmt'] = 70;
            $arr['fletemt'] = round($kms * 70, 2);
        }

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_kmsres207`.
     *
     * @return array{tarmt: float|string, fletemt: float|string}
     */
    public function calcularKmsRes207(int $kms = 0, float $tons = 0, bool|string $comb = false): array
    {
        $arr = ['tarmt' => '', 'fletemt' => ''];
        $arr['tarmt'] = $comb === 'false' || $comb === false ? 6.30 : 5;
        $arr['fletemt'] = round($tons * $kms * $arr['tarmt'], 2);

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_kmsadic`: kms adicionales.
     *
     * @return array{tarmt: float|string, fletemt: float|string, fletemlc: float|string}
     */
    public function calcularKmsAdicionales(
        int $tipocarga = 0,
        int $kms = 0,
        float $mlc = 0,
        float $capacidad = 0,
        float $descuento = 0,
    ): array {
        $arr = ['tarmt' => '', 'fletemt' => '', 'fletemlc' => ''];
        if ($kms <= 0) {
            return $this->normalizar($arr);
        }

        $config = $this->config();

        if ($tipocarga === 116) {
            $arr['tarmt'] = $capacidad <= 15 ? 36.96 : 56.54;
        } else {
            $arr['tarmt'] = $capacidad <= 15 ? $config->kms_adicionales_1 : $config->kms_adicionales_2;
        }

        $arr['fletemt'] = round($kms * $arr['tarmt'], 2);
        $arr['fletemlc'] = round(($arr['fletemt'] / 24) * ($mlc / 100), 2);

        if ($descuento > 0) {
            $kma_descuento = round($arr['fletemt'] * ($descuento / 100), 2);
            $arr['fletemt'] = round($arr['fletemt'] - $kma_descuento, 2);
        }

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_th_efectos`: tarifa horaria por efectos (40/45).
     *
     * @return array{tarmt: float|string, fletemt: float|string}
     */
    public function calcularThEfectos(
        int $tipocarga1 = 0,
        float $capacidad = 0,
        float $descuento = 0,
        int $horas = 0,
    ): array {
        $arr = ['tarmt' => '', 'fletemt' => ''];
        if ($horas <= 0 || $capacidad <= 0) {
            return $this->normalizar($arr);
        }

        $arr['tarmt'] = $capacidad <= 15 ? 40 : 45;
        $arr['fletemt'] = round($arr['tarmt'] * $horas, 2);

        if ($descuento > 0) {
            $th_descuento = round($arr['fletemt'] * ($descuento / 100), 2);
            $arr['fletemt'] = round($arr['fletemt'] - $th_descuento, 2);
        }

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_estibadores`: % del flete por piso (5/10/15/20%).
     *
     * @return array{tarmt: float|string, fletemt: float|string}
     */
    public function calcularEstibadores(int $kms = 0, float $flete = 0, float $descuento = 0): array
    {
        $arr = ['tarmt' => '', 'fletemt' => ''];
        if ($kms <= 0 || $flete <= 0) {
            return $this->normalizar($arr);
        }

        $arr['tarmt'] = match ($kms) {
            1 => 0.05,
            2 => 0.10,
            3 => 0.15,
            default => 0.20,
        };
        $arr['fletemt'] = round($arr['tarmt'] * $flete, 2);

        if ($descuento > 0) {
            $flete_descuento = round($arr['fletemt'] * ($descuento / 100), 2);
            $arr['fletemt'] = round($arr['fletemt'] - $flete_descuento, 2);
        }

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_almacenaje`: almacenaje (contenedores 175/210, o escalones 72/144/216).
     *
     * @return array{alm_tarifa: float|string, alm_flete: float|string}
     */
    public function calcularAlmacenaje(
        float $alm_peso = 0,
        float $alm_horas = 0,
        float $descuento = 0,
        int $tipocarga = 0,
        int $tipocont = 0,
    ): array {
        $arr = ['alm_tarifa' => '', 'alm_flete' => ''];
        if ($alm_peso <= 0) {
            return $this->normalizar($arr);
        }

        $config = $this->config();

        if ($tipocarga === 3 || $tipocarga === 4) {
            $tarifa = $tipocont == 1 ? 175 : 210;
            $arr['alm_tarifa'] = $tarifa;
            $arr['alm_flete'] = round($tarifa * $alm_peso, 2);
        } elseif ($alm_horas > 0) {
            if ($alm_horas <= 72) {
                $arr['alm_tarifa'] = $config->almacenaje;
            }
            if ($alm_horas > 72 && $alm_horas <= 144) {
                $arr['alm_tarifa'] = round($config->almacenaje + ($config->almacenaje * 0.5));
            }
            if ($alm_horas > 144 && $alm_horas <= 216) {
                $alm_almacenaje = round($config->almacenaje + ($config->almacenaje * 0.5));
                $arr['alm_tarifa'] = round($alm_almacenaje + ($alm_almacenaje * 0.5));
            }
            $arr['alm_flete'] = round($alm_peso * $alm_horas * $arr['alm_tarifa'], 2);

            if ($descuento > 0) {
                $alm_descuento = round($arr['alm_flete'] * ($descuento / 100), 2);
                $arr['alm_flete'] = round($arr['alm_flete'] - $alm_descuento, 2);
            }
        }

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_kmsvacios`: kms en vacío.
     *
     * @return array{tarmt: float|string, fletemt: float|string, fletemlc: float|string}
     */
    public function calcularKmsVacios(
        int $tipocarga = 7,
        int $moneda = 1,
        int $kms = 0,
        float $tarkvaciosmn1 = 0,
        float $peso = 0,
        float $mlc = 0,
        float $descuento = 0,
    ): array {
        $arr = ['tarmt' => '', 'fletemt' => '', 'fletemlc' => ''];
        $config = $this->config();

        if ($kms > 0) {
            $arr['tarmt'] = $peso <= 15 ? $config->kms_vacio_1 : $config->kms_vacio_2;
        }
        if ($tarkvaciosmn1 < $arr['tarmt'] && $tarkvaciosmn1 > 0) {
            $arr['tarmt'] = $tarkvaciosmn1;
        }

        $arr['fletemt'] = round($kms * $arr['tarmt'], 2);
        $arr['fletemlc'] = round(($arr['fletemt'] / 24) * ($mlc / 100), 2);

        $alm_descuento = round($arr['fletemt'] * ($descuento / 100), 2);
        $arr['fletemt'] = round($arr['fletemt'] - $alm_descuento, 2);

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_demora`: demora por carga y descarga.
     *
     * @return array{tardem1: float|string, tardem2: float|string, fdemcarga: float|string, fdemdescarga: float|string, horas: string, fletedemt: float|string}
     */
    public function calcularDemora(
        int $tipocarga1 = 0,
        int $tipocarga2 = 0,
        float $capacidad = 0,
        float $demcarga = 0,
        float $demdescarga = 0,
        float $descuento1 = 0,
        float $descuento2 = 0,
        float $horas = 0,
        int $conttipo = 0,
    ): array {
        $arr = ['tardem1' => '', 'tardem2' => '', 'fdemcarga' => '', 'fdemdescarga' => '', 'horas' => '', 'fletedemt' => ''];
        if (! $horas) {
            return $this->normalizar($arr);
        }

        $config = $this->config();

        if ($tipocarga1 === 3 || $tipocarga1 === 4) {
            $tardem = $conttipo == 2 ? $config->demora_2 : $config->demora_1;
            $arr['tardem1'] = $tardem;
            $arr['tardem2'] = $tardem;
        } elseif ($tipocarga1 === 18) {
            $tardem = $capacidad <= 25 ? 105 : 140;
            $arr['tardem1'] = $tardem;
            $arr['tardem2'] = $tardem;
        } elseif ($tipocarga1 === 43 || $tipocarga1 === 117 || $tipocarga1 === 118) {
            $tardem = $capacidad <= 15 ? 280 : 315;
            $arr['tardem1'] = $tardem;
            $arr['tardem2'] = $tardem;
        } else {
            if ($tipocarga1 === 114) {
                $tardem = $capacidad <= 15 ? 350 : 385;
                $arr['tardem1'] = $tardem;
                $arr['tardem2'] = $tardem;
            } else {
                $tardem1 = $capacidad <= 15 ? $config->demora_1 : $config->demora_2;
                $arr['tardem1'] = $tardem1;
                $arr['tardem2'] = $tardem1;
            }
        }

        if ($arr['tardem1'] > 0 && $demcarga > 0) {
            $arr['fdemcarga'] = round($arr['tardem1'] * $demcarga, 2);
            if ($descuento1 > 0) {
                $demc_descuento = round($arr['fdemcarga'] * ($descuento1 / 100), 2);
                $arr['fdemcarga'] = round($arr['fdemcarga'] - $demc_descuento, 2);
            }
        }

        if ($arr['tardem2'] > 0 && $demdescarga > 0) {
            $arr['fdemdescarga'] = round($arr['tardem2'] * $demdescarga, 2);
            if ($descuento2 > 0) {
                $demd_descuento = round($arr['fdemdescarga'] * ($descuento2 / 100), 2);
                $arr['fdemdescarga'] = round($arr['fdemdescarga'] - $demd_descuento, 2);
            }
        }

        $arr['fletedemt'] = round((float) $arr['fdemcarga'] + (float) $arr['fdemdescarga'], 2);

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_demora2`: demora de carga local adicional.
     *
     * @return array{tardem: float|string, fletedemt: float|string}
     */
    public function calcularDemora2(
        int $tipocarga = 0,
        float $capacidad = 0,
        float $horas = 0,
    ): array {
        $arr = ['tardem' => '', 'fletedemt' => ''];
        if ($horas <= 0) {
            return $this->normalizar($arr);
        }

        $config = $this->config();

        if ($tipocarga === 43) {
            $arr['tardem'] = $capacidad <= 15 ? 280 : 315;
        } else {
            $arr['tardem'] = $capacidad <= 15 ? $config->demora_1 : $config->demora_2;
        }
        $arr['fletedemt'] = round($arr['tardem'] * $horas, 2);

        return $this->normalizar($arr);
    }

    /**
     * Endpoint `calcular_tiempos`: suma tiempos HH:MM.
     */
    public function calcularTiempos(
        string|int $movimiento = 0,
        string|int $carga = 0,
        string|int $descarga = 0,
        string|int $otros = 0,
    ): string {
        $total = round(
            $this->cambiarEnteroMinutos($movimiento)
            + $this->cambiarEnteroMinutos($carga)
            + $this->cambiarEnteroMinutos($descarga)
            + $this->cambiarEnteroMinutos($otros),
            2
        );

        return $this->cambiarMinutosEntero($total);
    }

    /**
     * Endpoint `tarifa_acuerdos`: busca acuerdos de cliente (origen/destino/producto).
     */
    public function tarifaAcuerdos(int $cliente, int $origen, int $destino, int $producto = 0): array
    {
        $query = TarifaAcuerdo::query()
            ->where('id_cliente', $cliente)
            ->where('id_origen', $origen)
            ->where('id_destino', $destino);

        if ($producto > 0) {
            $query->where('id_producto', $producto);
        }

        return $query->get()->toArray();
    }

    /**
     * Endpoint `calcular_micons`: tarifas por monedas internacionales.
     *
     * @return array{tarmt: float|string, fletemt: float|string}
     */
    public function calcularMicons(float $peso, int $distancia, float $descuento, int $idtarmicons): array
    {
        $arr = ['tarmt' => '', 'fletemt' => ''];

        // No hay tabla nueva equivalente a `com_tarmicons`; se deja el esqueleto
        // para conectar cuando se defina el catálogo de tarifas internacionales.
        return $this->normalizar($arr);
    }

    /**
     * Endpoint `salariochofer/obtener_tasas`: lista de tasas por entidad, ordenada
     * por tipo de carga y rango de capacidad/distancia (paridad mostrar_tasas).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function tasas(int $idEntidad): \Illuminate\Database\Eloquent\Collection
    {
        return Tasa::query()
            ->with('tipoCarga:id,nombre')
            ->where('id_entidad', $idEntidad)
            ->orderBy('distancia_2')
            ->orderBy('capacidad_2')
            ->get();
    }

    /**
     * Cálculo del salario de un aforo por rango (paridad
     * `ModSalarioChofer::obtener_tasas($tipocarga,$capacidad,$distancia,$ingresos,$almacenaje,$idchofer2)`).
     *
     * Reglas:
     * - Si hay almacenaje, se resta del ingreso base y aporta +0.5% (`almacenaje*0.005`).
     * - Busca la tasa cuyo tipo de carga y rango (capacidad dentro de cap1-cap2,
     *   distancia dentro de dist1-dist2) coincide.
     * - Si el tipo de carga es 26 (paquetería) el salario se calcula sobre la
     *   capacidad; en el resto sobre los ingresos.
     * - Si hay chofer 2 y `tasa2>0`, usa tasa2; si no, usa tasa.
     *
     * @return array{idtasa:int|null, nombtasa:string|null, tasa:float, tasa2:float, salario:float}
     */
    public function calcularSalario(
        int $tipocarga = 0,
        float $capacidad = 0,
        int $distancia = 0,
        float $ingresos = 0,
        float $almacenaje = 0,
        int $idchofer2 = 0,
        int $idEntidad = 0,
    ): array {
        $salalm = 0;
        if ($almacenaje > 0) {
            $ingresos = round($ingresos - $almacenaje, 2);
            $salalm = round($almacenaje * 0.005, 2);
        }

        $resultado = [
            'idtasa' => null,
            'nombtasa' => null,
            'tasa' => 0,
            'tasa2' => 0,
            'salario' => 0,
        ];

        $query = Tasa::query()
            ->where('id_tipo_carga', $tipocarga);

        if ($idEntidad) {
            $query->where('id_entidad', $idEntidad);
        }

        foreach ($query->get() as $obj) {
            if ($capacidad >= $obj->capacidad_1 && $capacidad <= $obj->capacidad_2) {
                if ($distancia >= $obj->distancia_1 && $distancia <= $obj->distancia_2) {
                    $resultado['idtasa'] = $obj->id;
                    $resultado['nombtasa'] = $obj->nombre;
                    $resultado['tasa'] = (float) $obj->tasa;
                    $resultado['tasa2'] = (float) $obj->tasa2;

                    $base = $tipocarga == 26 ? $capacidad : $ingresos;
                    if ($idchofer2 > 0 && $obj->tasa2 > 0) {
                        $resultado['salario'] = round($base * (float) $obj->tasa2 + $salalm, 2);
                    } else {
                        $resultado['salario'] = round($base * (float) $obj->tasa + $salalm, 2);
                    }
                    break;
                }
            }
        }

        return $resultado;
    }

    /**
     * Cálculo de indicadores del aforo (paridad `aforoCalcularIndicadores`).
     *
     * Devuelve los totales de tráfico (pos y real) por fila y sus sumas, según el
     * tipo de indicador: 1=NORMAL, 2=/VIAJES, 3=DESAG (usa máximo km de carga),
     * 4=MANUAL (copia fila 1).
     *
     * @return array{trafpos: float, trafreal: float, kmcarga_total: float, kmvacio_total: float, kmtotal_total: float, tnpos_total: float, tnreal_total: float, valores: float}
     */
    public function calcularIndicadores(
        int $tipo = 1,
        int $viajes = 1,
        array $filas = [],
    ): array {
        $filas = collect($filas)->map(fn ($f) => [
            'tn_pos' => (float) ($f['tn_pos'] ?? 0),
            'tn_real' => (float) ($f['tn_real'] ?? 0),
            'km_carga' => (float) ($f['km_carga'] ?? 0),
            'km_vacio' => (float) ($f['km_vacio'] ?? 0),
        ])->values();

        if ($tipo === 4) { // MANUAL: copia la fila 1 a los totales
            $fila1 = $filas[0] ?? [];
            $kmcarga = $fila1['km_carga'] ?? 0;
            $kmvacio = $fila1['km_vacio'] ?? 0;
            $tnpos = $fila1['tn_pos'] ?? 0;
            $tnreal = $fila1['tn_real'] ?? 0;
            $trafpos = $fila1['traf_pos'] ?? 0;
            $trafreal = $fila1['traf_real'] ?? 0;

            return $this->resumenIndicadores($tipo, $viajes, $filas, $kmcarga, $kmvacio, $tnpos, $tnreal, $trafpos, $trafreal);
        }

        if ($tipo === 3) { // DESAGREGADO: usa el máximo km de carga
            $kmcarga = $filas->max('km_carga') ?? 0;
        } else { // NORMAL (1) y /VIAJES (2): suma
            $kmcarga = round($filas->sum('km_carga'), 2);
        }

        $kmvacio = round($filas->sum('km_vacio'), 2);
        $tnpos = round($filas->sum('tn_pos'), 2);
        $tnreal = round($filas->sum('tn_real'), 2);

        $trafpos = 0;
        $trafreal = 0;
        foreach ($filas as $fila) {
            if ($viajes > 0 && $tipo === 2) {
                $trafpos += round(($fila['tn_pos'] / $viajes) * $fila['km_carga'], 2);
                $trafreal += round(($fila['tn_real'] / $viajes) * $fila['km_carga'], 2);
            } else {
                $trafpos += round($fila['tn_pos'] * $fila['km_carga'], 2);
                $trafreal += round($fila['tn_real'] * $fila['km_carga'], 2);
            }
        }

        return $this->resumenIndicadores($tipo, $viajes, $filas, $kmcarga, $kmvacio, $tnpos, $tnreal, round($trafpos, 2), round($trafreal, 2));
    }

    protected function resumenIndicadores(
        int $tipo,
        int $viajes,
        $filas,
        float $kmcarga,
        float $kmvacio,
        float $tnpos,
        float $tnreal,
        float $trafpos,
        float $trafreal,
    ): array {
        // El campo `valores` (paridad legacy) guarda la suma de las líneas 3-5
        // (las líneas 6-7 no se editan en el form de aforo). Si > 0 se persiste
        // en la tabla `indicadores`.
        $valores = 0;
        foreach (range(2, 4) as $i) { // filas 3,4,5 (índice 0-based 2,3,4)
            $fila = $filas[$i] ?? [];
            $valores += ($fila['km_carga'] ?? 0) + ($fila['km_vacio'] ?? 0)
                + ($fila['tn_pos'] ?? 0) + ($fila['tn_real'] ?? 0);
        }
        $valores = round($valores, 2);

        return [
            'tipo' => $tipo,
            'viajes' => $viajes,
            'kmcarga_total' => round($kmcarga, 2),
            'kmvacio_total' => round($kmvacio, 2),
            'kmtotal_total' => round($kmcarga + $kmvacio, 2),
            'tnpos_total' => round($tnpos, 2),
            'tnreal_total' => round($tnreal, 2),
            'trafpos_total' => $trafpos,
            'trafreal_total' => $trafreal,
            'valores' => $valores,
        ];
    }

    /**
     * Configuración unificada de tarifas (fila única).
     */
    protected function config(): ConfiguracionTarifa
    {
        return ConfiguracionTarifa::query()->firstOrCreate([]);
    }
}
