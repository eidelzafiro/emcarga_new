<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use App\Models\CartaPorte;
use App\Models\HojasRuta;
use FPDF;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Impresión sobre FORMATO IMPRESO (pre-impreso) de la carta de porte y la hoja de ruta.
 *
 * Replica los reportes legacy de `system/application/controllers/Reportes2.php`:
 *   - `pdf_cp_girado($idcp)`      → emisión de carta de porte (tipo 1)
 *   - `pdf_cp_aforo($idcp)`       → aforo de carta de porte (tipo 2)
 *   - `pdf_hr_emision($idhr)`     → emisión de hoja de ruta (tipo 3)
 *
 * Las coordenadas y el tamaño del papel son configurables por entidad. Las filas de
 * `configuraciones_modelo` se leen ordenadas por `id` (igual que el legacy, que accede
 * por índice posicional `$ptomodelo[N]`). Se deduplican por `nombre` conservando la
 * primera aparición porque en algunas entidades el legacy duplicó el juego completo de
 * coordenadas (p. ej. entidad 17) y el legacy sólo consume el primer juego.
 */
class ImpresionCoordenadasService
{
    public const TIPO_CP_EMISION = 1;

    public const TIPO_CP_AFORO = 2;

    public const TIPO_HR_EMISION = 3;

    /**
     * Filas de configuración de coordenadas para un tipo de modelo y entidad.
     * Ordenadas por `id` y deduplicadas por `nombre` (primera gana).
     *
     * @return array<int, object>
     */
    public function configuracion(int $tipo, ?int $idEntidad): array
    {
        $rows = DB::table('configuraciones_modelo')
            ->where('codigo_tipo_modelo', $tipo)
            ->when($idEntidad, fn ($q) => $q->where('id_entidad', $idEntidad))
            ->orderBy('id')
            ->get();

        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            $nombre = (string) $row->nombre;
            if (isset($seen[$nombre])) {
                continue;
            }
            $seen[$nombre] = true;
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Tamaño de papel [ancho, alto] en mm para un tipo de modelo y entidad.
     * Primero intenta la tabla física `tipos_modelo` (codigo = tipo, id_entidad);
     * si no existe, cae al catálogo unificado `catalogo_items` (codigo = "{tipo}-{id}").
     * Devuelve null si no hay configuración (el llamador usa el tamaño por defecto del legacy).
     *
     * @return array{0: float, 1: float}|null
     */
    public function tamanoPapel(int $tipo, ?int $idEntidad): ?array
    {
        $t = DB::table('tipos_modelo')
            ->where('codigo', (string) $tipo)
            ->when($idEntidad, fn ($q) => $q->where('id_entidad', $idEntidad))
            ->orderBy('id')
            ->first();

        if ($t && $t->ancho && $t->alto) {
            return [(float) $t->ancho, (float) $t->alto];
        }

        if ($idEntidad) {
            $ci = DB::table('catalogo_items')
                ->where('tipo', 'tipos_modelo')
                ->where('codigo', $tipo.'-'.$idEntidad)
                ->first();

            if ($ci) {
                $extra = json_decode((string) $ci->extra, true) ?: [];
                $ancho = $extra['ancho'] ?? null;
                $alto = $extra['alto'] ?? null;
                if ($ancho && $alto) {
                    return [(float) $ancho, (float) $alto];
                }
            }
        }

        return null;
    }

    // =====================================================================
    // Emisión de carta de porte (tipo 1) — legacy pdf_cp_girado()
    // =====================================================================

    public function pdfCpEmision(CartaPorte $carta): Response
    {
        $carta->load([
            'cliente',
            'hojaRuta:id,numero,fecha_emision,id_entidad,id_tractivo,id_arrastre',
            'hojaRuta.entidad',
            'chofer',
            'chofer2',
            'tractivo.tipoVehiculo.tipoEquipo',
            'tractivo.tipoVehiculo.marca',
            'tractivo.tipoServicio',
            'arrastre.tipoVehiculo.tipoEquipo',
            'arrastre.tipoVehiculo.marca',
            'lugarOrigen',
            'lugarDestino',
            'producto',
            'producto2',
            'tipoCarga',
            'tipoCarga2',
        ]);

        $idEntidad = $carta->hojaRuta?->id_entidad ?? $carta->tractivo?->id_entidad;
        $cfg = $this->configuracion(self::TIPO_CP_EMISION, $idEntidad ? (int) $idEntidad : null);
        $papel = $this->tamanoPapel(self::TIPO_CP_EMISION, $idEntidad ? (int) $idEntidad : null) ?? [215.9, 279.4];

        $pdf = new FPDF('P', 'mm', [$papel[0], $papel[1]]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();

        if (empty($cfg)) {
            $this->paginaSinConfiguracion($pdf);

            return $this->salida($pdf, 'CP-EMISION.pdf');
        }

        $entidad = $carta->hojaRuta?->entidad ?? $carta->tractivo?->entidad;
        $origen = $carta->lugarOrigen;
        $destino = $carta->lugarDestino;
        $cliente = $carta->cliente;
        $tractivo = $carta->tractivo;
        $arrastre = $carta->arrastre;
        $chofer = $carta->chofer;
        $chofer2 = $carta->chofer2;
        $femision = $carta->fecha_emision?->format('Y-m-d') ?? '';

        // 0 — REMITENTE / ORIGEN (personalidad, lugar, dirección, provincia)
        $this->bloque($pdf, $this->cfgAt($cfg, 0), 10, 4, 'L', [
            $origen?->personalidad, $origen?->nombre, $origen?->direccion, $origen?->provincia,
        ]);

        // 1 — DESTINATARIO / DESTINO
        $this->bloque($pdf, $this->cfgAt($cfg, 1), 90, 4, 'L', [
            $destino?->personalidad, $destino?->nombre, $destino?->direccion, $destino?->provincia,
        ]);

        // 2 — CARGADOR / CLIENTE (nombre (código), dirección, MN-cta)
        $this->bloque($pdf, $this->cfgAt($cfg, 2), 80, 4, 'L', [
            $cliente ? mb_substr((string) $cliente->nombre, 0, 25).' ('.$cliente->codigo.')' : '',
            $cliente ? mb_substr((string) $cliente->direccion, 0, 35) : '',
            $cliente ? 'MN-'.$cliente->ctamn : '',
        ]);

        // 3 — TIPO CARGA 1 (tipo de carga + producto) y 4 — PESO 1
        if ($this->cfgAt($cfg, 3)) {
            $this->bloque($pdf, $this->cfgAt($cfg, 3), 10, 5, 'L', [
                $carta->tipoCarga?->nombre,
                'PRODUCTO:'.$carta->producto?->nombre,
            ]);
        }
        if ($carta->peso1 > 0) {
            $this->campo($pdf, $this->cfgAt($cfg, 4), $this->num($carta->peso1 * 1000, 0), 'R', 10, 5);
        }

        // 5 — TIPO CARGA 2 y 6 — PESO 2 (sólo si la segunda línea es de carga)
        if ((int) $carta->tipoCarga?->id > 2 || (int) $carta->tipoCarga2?->id > 2) {
            $this->bloque($pdf, $this->cfgAt($cfg, 5), 10, 5, 'L', [
                $carta->tipoCarga2?->nombre,
                'PRODUCTO:'.$carta->producto2?->nombre,
            ]);
            if ($carta->peso1 > 0) {
                $this->campo($pdf, $this->cfgAt($cfg, 6), $this->num($carta->peso2 * 1000, 0), 'R', 10, 5);
            }
        }

        // 7 — PORTEADOR (nombre entidad, código, dirección + cuenta única)
        $this->bloque($pdf, $this->cfgAt($cfg, 7), 80, 4, 'L', [
            $entidad?->nombre, $entidad?->codigo, trim(($entidad?->direccion ?? '').($entidad?->cta_unica ?? '')),
        ]);

        // 8 — LUGAR EMISIÓN / AGENCIA
        if ($entidad?->agencia) {
            $this->campo($pdf, $this->cfgAt($cfg, 8), $entidad->agencia, 'L', 10, 8);
        }

        // 9 — FECHA EMISIÓN
        $this->campo($pdf, $this->cfgAt($cfg, 9), $femision, 'L', 10, 8);

        // 10 — CHAPA TRACTIVO (placa + código)
        $this->campo($pdf, $this->cfgAt($cfg, 10), trim(($tractivo?->placa ?? '').' ('.($tractivo?->codigo ?? '').')'), 'L', 10, 8);

        // 11 — PESO TRACTIVO (capacidad)
        $this->campo($pdf, $this->cfgAt($cfg, 11), (string) $tractivo?->capacidad_toneladas, 'L', 10, 8);

        // 12 — BASE (nombre entidad)
        $this->campo($pdf, $this->cfgAt($cfg, 12), (string) $entidad?->nombre, 'L', 10, 8);

        // 13 — CHAPA ARRASTRE
        $this->campo($pdf, $this->cfgAt($cfg, 13), (string) $arrastre?->placa, 'L', 10, 8);

        // 14 — HOJA DE RUTA (número)
        $this->campo($pdf, $this->cfgAt($cfg, 14), (string) $carta->hojaRuta?->numero, 'L', 10, 8);

        // 15 — OBSERVACIONES (sólo si la carta está marcada para imprimir)
        if ($carta->imprimir) {
            $this->campo($pdf, $this->cfgAt($cfg, 15), (string) $carta->notas, 'L', 10, 5);
        }

        // 16 — CHOFER (nombre, CI, licencia, fecha emisión; segundo chofer debajo)
        if ($c = $this->cfgAt($cfg, 16)) {
            $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
            $pdf->SetXY($c->set_x, $c->set_y);
            $pdf->Cell(10, 5, $this->txt(ucwords(mb_strtolower(mb_substr((string) $this->nombreBolsa($chofer), 0, 25)))), 0, 2, 'L', 0);
            if ($chofer2) {
                $pdf->SetXY($c->set_x, $c->set_y + 5);
                $pdf->Cell(10, 5, $this->txt((string) $this->nombreBolsa($chofer2)), 0, 2, 'L', 0);
            }
            // La tabla `bolsa` de Zafiro no tiene columna `licencia` (el legacy la leía
            // de rh_bolsa.licencia); se imprime el CI y se omite la licencia.
            $pdf->Cell(10, 5, $this->txt((string) $chofer?->ci), 0, 2, 'L', 0);
            $pdf->Cell(10, 5, '', 0, 2, 'L', 0);
            $pdf->Cell(10, 5, $femision, 0, 2, 'L', 0);
            if ($chofer2) {
                $pdf->SetXY($c->set_x + 30, $c->set_y + 10);
                $pdf->Cell(10, 5, $this->txt((string) $chofer2->ci), 0, 2, 'L', 0);
            }
        }

        // 17 — TIPO SERVICIO / SERVICIO PÚBLICO NACIONAL
        if ($c = $this->cfgAt($cfg, 17)) {
            $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
            $pdf->SetXY($c->set_x, $c->set_y);
            $pdf->Cell(10, 5, 'SERV. PUB NACIONAL', 0, 2, 'L', 0);
            $pdf->SetXY($c->set_x, $c->set_y + 10);
            $pdf->Cell(10, 5, 'CAP: '.$tractivo?->capacidad_toneladas.' TNS', 0, 2, 'L', 0);
            $pdf->SetFont('Arial', 'B', 9);
            // La tabla `tractivos` de Zafiro no expone `lot` ni `circulacion`
            // (el legacy los leía de tec_tractivos); se omiten por no existir el dato.
            $pdf->Cell(10, 5, 'CIR.EQUIPO:  CIR.ARRASTRE:', 0, 2, 'L', 0);
        }

        return $this->salida($pdf, 'CP-EMISION.pdf');
    }

    // =====================================================================
    // Aforo de carta de porte (tipo 2) — legacy pdf_cp_aforo()
    // =====================================================================

    public function pdfCpAforo(Aforo $aforo): Response
    {
        $aforo->load([
            'cartaPorte.cliente',
            'cartaPorte.tractivo.tipoVehiculo.tipoEquipo',
            'cartaPorte.hojaRuta:id,numero,id_entidad',
            'lineas.tipoCarga:id,nombre',
        ]);

        $carta = $aforo->cartaPorte;
        $idEntidad = $carta?->hojaRuta?->id_entidad ?? $carta?->tractivo?->id_entidad;
        $cfg = $this->configuracion(self::TIPO_CP_AFORO, $idEntidad ? (int) $idEntidad : null);
        $papel = $this->tamanoPapel(self::TIPO_CP_AFORO, $idEntidad ? (int) $idEntidad : null) ?? [215.9, 279.4];

        $pdf = new FPDF('P', 'mm', [$papel[0], $papel[1]]);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        if (empty($cfg)) {
            $this->paginaSinConfiguracion($pdf);

            return $this->salida($pdf, 'CP-AFORO.pdf');
        }

        $lineas = $aforo->lineas->keyBy('posicion');
        $l1 = $lineas->get(1);
        $l2 = $lineas->get(2);
        $l3 = $lineas->get(3);

        // Número de CP en posición fija (legacy SetXY(120,10)).
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(120, 10);
        $pdf->Cell(10, 5, $this->txt((string) $carta?->numero), 0, 2, 'C', 0);

        // Línea 1: horas (tc10) o peso a cobrar; tarifa y flete.
        if ((int) $l1?->id_tipo_carga === 10) {
            $this->campo($pdf, $this->cfgAt($cfg, 0), 'HORAS:', 'R', 10, 5);
            $this->campo($pdf, $this->cfgAt($cfg, 1), $this->num($l1?->peso_cobrar, 2), 'R', 10, 5);
        } else {
            $this->campo($pdf, $this->cfgAt($cfg, 1), $this->num($l1?->peso_cobrar, 2), 'R', 10, 5);
        }
        if ((float) $l1?->flete_mt > 0) {
            if ((float) $l1?->descuento > 0) {
                $this->campo($pdf, $this->cfgAt($cfg, 2), 'D- '.$this->num($l1->descuento, 2).'%', 'R', 10, 5, -15);
            }
            $this->campo($pdf, $this->cfgAt($cfg, 2), $this->num($l1?->tarifa_mt, 2), 'R', 10, 5);
            $this->campo($pdf, $this->cfgAt($cfg, 3), $this->num($l1?->flete_mt, 2), 'R', 10, 5);
        }

        $sety = (float) ($this->cfgAt($cfg, 4)?->set_y ?? 0);

        // Línea 2 y 3 (peso/kms vacíos + tarifa + flete).
        foreach ([$l2, $l3] as $linea) {
            if (! $linea || (float) $linea->peso_cobrar <= 0) {
                continue;
            }
            if ((int) $linea->id_tipo_carga === 7) {
                $this->campo($pdf, $this->cfgAt($cfg, 5), 'KMS VACIOS:', 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 5)?->set_y ?? 0));
                $this->campo($pdf, $this->cfgAt($cfg, 6), $this->num($linea->peso_cobrar, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 6)?->set_y ?? 0));
                $this->campo($pdf, $this->cfgAt($cfg, 6), $this->num($linea->flete_mt, 2), 'R', 10, 5, 56, $sety - (float) ($this->cfgAt($cfg, 6)?->set_y ?? 0));
            } else {
                $this->campo($pdf, $this->cfgAt($cfg, 6), $this->num($linea->peso_cobrar, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 6)?->set_y ?? 0));
            }
            if ((float) $linea->flete_mt > 0) {
                if ((float) $linea->descuento > 0) {
                    $this->campo($pdf, $this->cfgAt($cfg, 7), 'D- '.$this->num($linea->descuento, 2).'%', 'R', 10, 5, -15, $sety - (float) ($this->cfgAt($cfg, 7)?->set_y ?? 0));
                }
                $this->campo($pdf, $this->cfgAt($cfg, 7), $this->num($linea->tarifa_mt, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 7)?->set_y ?? 0));
                $this->campo($pdf, $this->cfgAt($cfg, 8), $this->num($linea->flete_mt, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 8)?->set_y ?? 0));
                $sety += 10;
            }
        }

        // Demora de carga.
        if ((float) $aforo->dem_carga > 0) {
            $this->campo($pdf, $this->cfgAt($cfg, 9), 'DC '.$this->num($aforo->dem_carga, 0), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 9)?->set_y ?? 0));
            $this->campo($pdf, $this->cfgAt($cfg, 10), $this->num($aforo->tar_dem_1, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 10)?->set_y ?? 0));
            $this->campo($pdf, $this->cfgAt($cfg, 11), $this->num($aforo->flete_dem_1, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 11)?->set_y ?? 0));
            $sety += 6;
        }
        // Demora de descarga.
        if ((float) $aforo->dem_descarga > 0) {
            $this->campo($pdf, $this->cfgAt($cfg, 12), 'DD '.$this->num($aforo->dem_descarga, 0), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 12)?->set_y ?? 0));
            $this->campo($pdf, $this->cfgAt($cfg, 13), $this->num($aforo->tar_dem_2, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 13)?->set_y ?? 0));
            $this->campo($pdf, $this->cfgAt($cfg, 14), $this->num($aforo->flete_dem_2, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 14)?->set_y ?? 0));
            $sety += 6;
        }
        // Recargo de almacenaje.
        if ((float) $aforo->almacenaje_flete > 0) {
            $this->campo($pdf, $this->cfgAt($cfg, 15), 'RECARGO ALMACENAJE:', 'L', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 15)?->set_y ?? 0));
            $this->campo($pdf, $this->cfgAt($cfg, 16), $this->num($aforo->almacenaje_flete, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 16)?->set_y ?? 0));
            $sety += 6;
        }

        // Recargos (1-5).
        $recargos = [
            1 => 'INCUMPLIMIENTO CARGA:',
            2 => 'ENTREGA DOCUMENTOS:',
            3 => 'ERROR EN DOCUMENTOS:',
            4 => 'LIMPIO Y LIBRE:',
            5 => 'PROTECCION A LA CARGA:',
        ];
        foreach ($recargos as $i => $label) {
            $valor = (float) $aforo->{'recargo_'.$i};
            if ($valor > 0) {
                $this->campo($pdf, $this->cfgAt($cfg, 17), $label, 'L', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 17)?->set_y ?? 0));
                $this->campo($pdf, $this->cfgAt($cfg, 18), $this->num($valor, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 18)?->set_y ?? 0));
                $sety += 5;
            }
        }

        // Otros ingresos (OPC).
        if ((float) $aforo->otros_mt > 0) {
            $this->campo($pdf, $this->cfgAt($cfg, 19), 'OPC:', 'L', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 19)?->set_y ?? 0));
            $this->campo($pdf, $this->cfgAt($cfg, 18), $this->num($aforo->otros_mt, 2), 'R', 10, 5, 0, $sety - (float) ($this->cfgAt($cfg, 18)?->set_y ?? 0));
            $sety += 5;
        }

        // Totales (etiquetas y valores apilados).
        if ($c = $this->cfgAt($cfg, 19)) {
            $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
            $pdf->SetXY($c->set_x, $sety);
            if ((float) $aforo->flete_mt > 0) {
                $pdf->Cell(10, 5, 'FLETE MT  ', 0, 2, 'L', 0);
            }
            if ((float) $aforo->flete_mlc > 0) {
                $pdf->Cell(10, 5, 'FLETE CL  ', 0, 2, 'L', 0);
            }
            if ((float) $aforo->flete_demora > 0) {
                $pdf->Cell(10, 5, 'DEMORA    ', 0, 2, 'L', 0);
            }
            if ((float) $aforo->flete_mt !== (float) $aforo->ingreso_mt) {
                $pdf->Cell(10, 5, 'INGRESO', 0, 2, 'L', 0);
            }
        }
        if ($c = $this->cfgAt($cfg, 20)) {
            $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
            $pdf->SetXY($c->set_x, $sety);
            if ((float) $aforo->flete_mt > 0) {
                $pdf->Cell(10, 5, $this->num($aforo->flete_mt, 2), 0, 2, 'R', 0);
            }
            if ((float) $aforo->flete_mlc > 0) {
                $pdf->Cell(10, 5, $this->num($aforo->flete_mlc, 2), 0, 2, 'R', 0);
            }
            if ((float) $aforo->flete_demora > 0) {
                $pdf->Cell(10, 5, $this->num($aforo->flete_demora, 2), 0, 2, 'R', 0);
            }
            if ((float) $aforo->flete_mt !== (float) $aforo->ingreso_mt) {
                $pdf->Cell(10, 5, $this->num($aforo->ingreso_mt, 2), 0, 2, 'R', 0);
            }
        }

        // Distancia cobrada (legacy $ptomodelo[23]).
        if ((float) $carta?->distancia > 0 && ($c = $this->cfgAt($cfg, 23))) {
            $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
            $pdf->SetXY($c->set_x, $c->set_y);
            $pdf->Cell(60, 5, $carta->distancia.' KMS ', 0, 2, 'L', 0);
        }

        return $this->salida($pdf, 'CP-AFORO.pdf');
    }

    // =====================================================================
    // Emisión de hoja de ruta (tipo 3) — legacy pdf_hr_emision()
    // =====================================================================

    public function pdfHrEmision(HojasRuta $hoja): Response
    {
        $hoja->load([
            'entidad',
            'tractivo.tipoVehiculo.tipoEquipo',
            'tractivo.tipoVehiculo.marca',
            'tractivo.tipoServicio',
            'arrastre.tipoVehiculo.tipoEquipo',
            'arrastre.tipoVehiculo.marca',
            'chofer',
            'chofer2',
            'parqueo',
            'user',
        ]);

        $idEntidad = $hoja->id_entidad;
        $cfg = $this->configuracion(self::TIPO_HR_EMISION, $idEntidad ? (int) $idEntidad : null);
        $papel = $this->tamanoPapel(self::TIPO_HR_EMISION, $idEntidad ? (int) $idEntidad : null) ?? [355.6, 215.9];

        $pdf = new FPDF('L', 'mm', [$papel[0], $papel[1]]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();

        if (empty($cfg)) {
            $this->paginaSinConfiguracion($pdf);

            return $this->salida($pdf, 'HR-EMISION.pdf');
        }

        $entidad = $hoja->entidad;
        $tractivo = $hoja->tractivo;
        $arrastre = $hoja->arrastre;
        $chofer = $hoja->chofer;
        $chofer2 = $hoja->chofer2;

        // 0-4 — Tractivo: tipo equipo, marca, capacidad, código, chapa.
        $this->campo($pdf, $this->cfgAt($cfg, 0), (string) $tractivo?->tipoVehiculo?->tipoEquipo?->nombre, 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 1), (string) $tractivo?->tipoVehiculo?->marca?->nombre, 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 2), (string) $tractivo?->capacidad_toneladas, 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 3), (string) $tractivo?->codigo, 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 4), (string) $tractivo?->placa, 'L');

        // 5-9 — Arrastre: tipo equipo, marca, capacidad, código, chapa.
        $this->campo($pdf, $this->cfgAt($cfg, 5), (string) $arrastre?->tipoVehiculo?->tipoEquipo?->nombre, 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 6), (string) $arrastre?->tipoVehiculo?->marca?->nombre, 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 7), (string) $arrastre?->capacidad_toneladas, 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 8), (string) $arrastre?->codigo, 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 9), (string) $arrastre?->placa, 'L');

        // 10-12 — Fecha de emisión (día, mes, año).
        $femision = $hoja->fecha_emision?->format('Y-m-d') ?? '';
        $this->campo($pdf, $this->cfgAt($cfg, 10), mb_substr($femision, 8, 2), 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 11), mb_substr($femision, 5, 2), 'L');
        $this->campo($pdf, $this->cfgAt($cfg, 12), mb_substr($femision, 2, 2), 'L');

        // 13 — Entidad (abreviatura).
        $this->campo($pdf, $this->cfgAt($cfg, 13), (string) $entidad?->abreviatura, 'L');
        // 14 — MITRANS (texto fijo del formato pre-impreso).
        $this->campo($pdf, $this->cfgAt($cfg, 14), 'MITRANS', 'L');

        // 15 — Nombre del chofer (y segundo chofer debajo).
        if ($c = $this->cfgAt($cfg, 15)) {
            $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
            $pdf->SetXY($c->set_x, $c->set_y);
            $nombre = mb_substr((string) $this->nombreBolsa($chofer), 0, 35);
            $pdf->Cell(10, 5, $this->txt(ucwords(mb_strtolower($nombre))), 0, 2, 'L', 0);
            if ($chofer2) {
                $pdf->SetXY($c->set_x, $c->set_y + 5);
                $pdf->Cell(10, 5, $this->txt((string) $this->nombreBolsa($chofer2)), 0, 2, 'L', 0);
            }
        }
        // 16 — Licencia (la tabla bolsa de Zafiro no tiene columna licencia; se omite).
        $this->campo($pdf, $this->cfgAt($cfg, 16), '', 'L');

        // 17 — Parqueo.
        $this->campo($pdf, $this->cfgAt($cfg, 17), (string) $hoja->parqueo?->nombre, 'L');
        // 18 — Tipo de servicios.
        $this->campo($pdf, $this->cfgAt($cfg, 18), (string) $tractivo?->tipoServicio?->nombre, 'L');
        // 19 — Kms disponibles (tractivo y arrastre).
        $this->campo($pdf, $this->cfgAt($cfg, 19), $this->num($tractivo?->kms_disp, 0), 'L');
        if ((string) $arrastre?->codigo !== '' && ($c = $this->cfgAt($cfg, 19))) {
            $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
            $pdf->SetXY($c->set_x + 15, $c->set_y);
            $pdf->Cell(10, 5, $this->num($arrastre->kms_disp, 0), 0, 2, 'L', 0);
        }
        // 20 — CI del chofer (y segundo chofer).
        if ($c = $this->cfgAt($cfg, 20)) {
            $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
            $pdf->SetXY($c->set_x, $c->set_y);
            $pdf->Cell(10, 5, 'CI:'.$chofer?->ci, 0, 2, 'L', 0);
            if ($chofer2) {
                $pdf->SetXY($c->set_x + 50, $c->set_y);
                $pdf->Cell(10, 5, 'CI2:'.$chofer2->ci, 0, 2, 'L', 0);
            }
        }
        // 21 — Operativo (usuario que emitió la HR).
        $this->campo($pdf, $this->cfgAt($cfg, 21), mb_substr((string) $hoja->user?->name, 0, 20), 'L');

        return $this->salida($pdf, 'HR-EMISION.pdf');
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function cfgAt(array $cfg, int $i): ?object
    {
        return $cfg[$i] ?? null;
    }

    /**
     * Escribe un campo en su coordenada. `$dx`/`$dy` son desplazamientos relativos
     * (equivalentes a los `setx+56`, `sety+10`, etc. del legacy).
     */
    private function campo(FPDF $pdf, ?object $c, string $texto, string $align = 'L', float $w = 10, float $h = 5, float $dx = 0, float $dy = 0): void
    {
        if (! $c) {
            return;
        }
        $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
        $pdf->SetXY((float) $c->set_x + $dx, (float) $c->set_y + $dy);
        $pdf->Cell($w, $h, $this->txt($texto), 0, 2, $align, 0);
    }

    /**
     * Escribe varias líneas apiladas a partir de una misma coordenada
     * (el legacy hace `SetXY(...)` una vez y luego varios `Cell(...)`).
     */
    private function bloque(FPDF $pdf, ?object $c, float $w, float $h, string $align, array $lineas): void
    {
        if (! $c) {
            return;
        }
        $pdf->SetFont('Arial', 'B', (int) ($c->letra ?? 10));
        $pdf->SetXY((float) $c->set_x, (float) $c->set_y);
        foreach ($lineas as $texto) {
            $pdf->Cell($w, $h, $this->txt((string) $texto), 0, 2, $align, 0);
        }
    }

    private function paginaSinConfiguracion(FPDF $pdf): void
    {
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetXY(50, 65);
        $pdf->Cell(150, 10, 'NO ESTA DEFINIDA LA IMPRESION', 0, 1, 'L');
        $pdf->SetXY(50, 75);
        $pdf->Cell(150, 10, 'CONTACTE A SU ADMINISTRADOR', 0, 1, 'L');
    }

    private function salida(FPDF $pdf, string $nombre): Response
    {
        return response($pdf->Output('S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$nombre.'"');
    }

    /**
     * Replica `Reportes_lib::cambiarVariable()`: vacío/0 → ''; el resto
     * `number_format` con coma de miles y punto decimal.
     */
    private function num($valor, int $decimales = 0): string
    {
        if ($valor === null || $valor === '' || (float) $valor == 0.0) {
            return '';
        }

        return number_format((float) $valor, $decimales, '.', ',');
    }

    private function nombreBolsa($bolsa): string
    {
        if (! $bolsa) {
            return '';
        }

        return trim(((string) $bolsa->nombre).' '.((string) $bolsa->apellidos));
    }

    private function txt(string $texto): string
    {
        return mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
    }
}
