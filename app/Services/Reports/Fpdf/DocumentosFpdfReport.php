<?php

namespace App\Services\Reports\Fpdf;

use App\Models\CartaPorte;
use App\Models\Entidad;
use App\Models\HojasRuta;
use Carbon\Carbon;

/**
 * Reportes de DOCUMENTOS (cartas de porte y hojas de ruta) replicando el layout
 * exacto del legacy `Reportes2.php` con FPDF. Las coordenadas, anchos de columna,
 * saltos de página, bloques de firma y pie de página son idénticos al legacy.
 */
class DocumentosFpdfReport extends DocumentosFpdfBase
{
    /** @var int[] */
    protected array $entidadIds;

    public function __construct(
        string $orientation,
        string $paper,
        ?object $entidad,
        bool $multiEntidad,
        string $fechaOperaciones,
        array $entidadIds = [],
    ) {
        parent::__construct($orientation, $paper, $entidad, $multiEntidad, $fechaOperaciones);
        $this->entidadIds = $entidadIds ?: [23];
    }

    // =====================================================================
    // CARTAS DE PORTE
    // =====================================================================

    /** id 3 — CARTA PORTE CANCELADAS. */
    public function pdfCpCancelada(string $mes = ''): \Illuminate\Http\Response
    {
        $titulo = 'CARTA DE PORTES CANCELADAS';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'NRO', 'campo' => '$nro', 'ancho' => 15, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'NRO CP', 'campo' => 'nrocp', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'NOTAS A LA CANCELACION', 'campo' => 'notas', 'ancho' => 160, 'direccion' => 'L', 'bordes' => '1', 'letra2' => 11],
        ];

        $data = $this->cartasDelMes($anio, $mesNum, true)->map(fn ($c) => (object) [
            'nrocp' => $c->numero,
            'notas' => (string) $c->notas,
        ])->all();

        $posY = 41;
        $i = 0;
        $nro = 1;
        $max = 29;

        if ($data) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(6, 10, 35, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 9);
                $this->SetFillColor(255, 255, 255);
                $this->SetXY(10, $posY);
                $this->Cell(15, 6, (string) $nro, 1, 0, 'C', 1);
                $this->Cell(20, 6, (string) $arr->nrocp, 1, 0, 'C', 1);
                $this->Cell(160, 6, $this->txt(substr((string) $arr->notas, 0, 76)), 1, 0, 'L', 1);
                $posY += 6;
                $nro++;
                $i++;
                if ($i >= $max) {
                    $this->firmasRevisadoAprobado(225);
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(6, 10, 35, $campos);
                    $posY = 41;
                    $i = 0;
                }
            }
            $this->firmasRevisadoAprobado(225);
        } else {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    /** id 4 — CARTA PORTE AUTOMOTOR CONSECUTIVO. */
    public function pdfCpConsecutivo(int $inicio, int $fin): \Illuminate\Http\Response
    {
        $titulo = 'CONSECUTIVO CARTA DE PORTE DESDE FOLIO '.$inicio.' HASTA '.$fin;
        $campos = $this->camposConsecutivoCarta();
        $anio = $this->anioActual();

        $data = $this->cartasRango($inicio, $fin, $anio);
        $mapa = [];
        foreach ($data as $row) {
            $mapa[(string) $row->nrocp] = $row;
        }

        $posY = 45;
        $i = 0;
        $max = 26;

        $this->inicio($titulo, '', 50, 5);
        $this->titulos(10, 10, 35, $campos);

        for ($n = $inicio; $n <= $fin; $n++) {
            if ($i === $max) {
                $this->inicio($titulo, '', 50, 5);
                $this->titulos(10, 10, 35, $campos);
                $posY = 45;
                $i = 0;
            }
            $arr = $mapa[(string) $n] ?? null;
            $this->SetXY(10, $posY);
            if (! $arr) {
                $this->SetFont('Arial', 'B', 12);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(15, 6, (string) $n, 1, 0, 'C', 1);
                $this->Cell(321, 6, 'FOLIO PENDIENTE', 1, 0, 'L', 0);
            } elseif ((int) $arr->cancelada === 1) {
                $this->SetFont('Arial', 'B', 12);
                $this->SetFillColor(200, 200, 200);
                $this->Cell(15, 6, (string) $arr->nrocp, 1, 0, 'C', 1);
                $this->SetFont('Arial', '', 8);
                $this->Cell(25, 6, substr((string) $arr->femision, 0, 16), 1, 0, 'C', 1);
                $this->Cell(25, 6, $this->medios($arr), 1, 0, 'L', 1);
                $this->Cell(45, 6, ucwords(mb_strtolower($this->txt($arr->nombrecompleto))), 1, 0, 'L', 1);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(226, 6, substr($this->txt((string) $arr->notas), 0, 25), 1, 0, 'L', 1);
            } else {
                $this->SetFillColor(255, 255, 255);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(15, 6, (string) $arr->nrocp, 1, 0, 'C', 1);
                $this->SetFont('Arial', '', 8);
                $this->Cell(25, 6, substr((string) $arr->femision, 0, 16), 1, 0, 'C', 1);
                $this->SetFont('Arial', 'b', 7);
                $this->Cell(25, 6, $this->medios($arr), 1, 0, 'L', 1);
                $this->SetFont('Arial', '', 10);
                $this->Cell(45, 6, ucwords(mb_strtolower($this->txt(substr((string) $arr->nombrecompleto, 0, 30)))), 1, 0, 'L', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->tnreal, 2), 1, 0, 'C', 1);
                $this->Cell(18, 6, (string) $arr->conduce, 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->pesocobrar1, 2), 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->kmcarga, 2), 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->kmvacio, 2), 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->ingresomt, 2), 1, 0, 'C', 1);
                $this->Cell(25, 6, $arr->fcarga ? '('.substr((string) $arr->fcarga, 8, 2).')-'.$arr->hcarga1 : '', 1, 0, 'C', 1);
                $this->Cell(25, 6, $arr->fdescarga ? '('.substr((string) $arr->fdescarga, 8, 2).')-'.$arr->hdescarga2 : '', 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->ttotal, 2), 1, 0, 'C', 1);
                $this->SetFont('Arial', '', 8);
                $this->Cell(50, 6, substr($this->txt((string) $arr->notas), 0, 25), 1, 0, 'L', 1);
            }
            $posY += 6;
            $i++;
        }

        return $this->salida($titulo.'.pdf');
    }

    /** id 157 — CARTA PORTE (REGISTRO RES 213-2019). */
    public function pdfCpFolios(string $mes = ''): \Illuminate\Http\Response
    {
        $titulo = 'REGISTRO DE CARTAS DE PORTES (RES 213-2019)';
        [$anio, $mesNum] = $this->anioMes($mes);
        $campos = $this->camposConsecutivoCarta();

        $data = $this->cartasDelMes($anio, $mesNum, false)
            ->map(fn ($c) => $this->filaConsecutivoCarta($c))
            ->all();

        $posY = 45;
        $i = 0;
        $max = 22;

        if ($data) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(10, 10, 35, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 10);
                $this->SetXY(10, $posY);
                $this->SetFillColor((int) $arr->cancelada === 1 ? 200 : 255, (int) $arr->cancelada === 1 ? 200 : 255, (int) $arr->cancelada === 1 ? 200 : 255);
                $this->Cell(15, 6, (string) $arr->nrocp, 1, 0, 'C', 1);
                $this->SetFont('Arial', '', 8);
                $this->Cell(25, 6, substr((string) $arr->femision, 0, 16), 1, 0, 'C', 1);
                $this->SetFont('Arial', 'b', 7);
                $this->Cell(25, 6, $this->medios($arr), 1, 0, 'L', 1);
                $this->SetFont('Arial', '', 10);
                $this->Cell(45, 6, ucwords(mb_strtolower($this->txt(substr((string) $arr->nombrecompleto, 0, 30)))), 1, 0, 'L', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->tnreal, 2), 1, 0, 'C', 1);
                $this->Cell(18, 6, (string) $arr->conduce, 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->pesocobrar1, 2), 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->kmcarga, 2), 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->kmvacio, 2), 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->ingresomt, 2), 1, 0, 'C', 1);
                $this->Cell(25, 6, $arr->fcarga ? '('.substr((string) $arr->fcarga, 8, 2).')-'.$arr->hcarga1 : '', 1, 0, 'C', 1);
                $this->Cell(25, 6, $arr->fdescarga ? '('.substr((string) $arr->fdescarga, 8, 2).')-'.$arr->hdescarga2 : '', 1, 0, 'C', 1);
                $this->Cell(18, 6, $this->cambiarVariable($arr->ttotal, 2), 1, 0, 'C', 1);
                $this->SetFont('Arial', '', 8);
                $this->Cell(50, 6, substr($this->txt((string) $arr->notas), 0, 25), 1, 0, 'L', 1);
                $posY += 6;
                $i++;
                if ($i === $max) {
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(10, 10, 35, $campos);
                    $posY = 45;
                    $i = 0;
                }
            }
        } else {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    /**
     * id 5 — CARTA PORTE CONTROL ESTADO.
     *
     * Réplica de `Reportes2::pdf_cp_estado()`: el reporte legacy tiene DOS partes:
     *   1. Resumen agrupado por tractivo (solo CP sin parte / sin aforo), con el
     *      total de viajes. Equivale a `modCartaPorte::mostrar_estado_agrupado()`.
     *   2. Detalle por carta de porte, una rejilla multicolumna que visualiza las
     *      cartas según su estado: CANCELADA, sin parte (pendiente de aforo, con
     *      resaltado si lleva 5+ días) y con parte/aforada (fondo gris). Equivale
     *      a `modCartaPorte::mostrar_estado()`.
     */
    public function pdfCpEstado(string $mes = ''): \Illuminate\Http\Response
    {
        $titulo = 'CONTROL EMISION Y RECEPCION CARTA PORTE AUTOMOTOR';
        [$anio, $mesNum] = $this->anioMes($mes);

        $cartas = $this->cartasDelMes($anio, $mesNum, false);

        // --- Parte 1: resumen agrupado por tractivo (solo sin parte / sin aforo) ---
        $data = $cartas
            ->filter(fn ($c) => ! $c->aforos->contains(fn ($a) => $a->fecha_parte !== null))
            ->groupBy(fn ($c) => $c->tractivo?->codigo ?? 'SIN EQUIPO')
            ->map(fn ($g, $cod) => (object) ['codtractivo' => $cod, 'viajes' => $g->count()])
            ->sortBy('codtractivo')
            ->values()
            ->all();

        // --- Parte 2: detalle por carta de porte (todos los estados) ---
        $detalle = $cartas
            ->sortBy([['fecha_emision', 'asc'], ['numero', 'asc']])
            ->values()
            ->map(function ($c) {
                $fparte = $c->aforos
                    ->first(fn ($a) => $a->fecha_parte !== null)?->fecha_parte;
                $femision = $c->fecha_emision?->format('Y-m-d') ?? '';

                return (object) [
                    'nrocp' => $c->numero,
                    'codtractivo' => $c->tractivo?->codigo,
                    'femision' => $femision,
                    'cancelada' => $c->cancelada,
                    'fparte' => $fparte,
                    'nrohr' => $c->hojaRuta?->numero,
                    'dias' => $this->restaFechas($this->fechaOperaciones, $femision),
                ];
            })
            ->all();

        if (! $data && ! $detalle) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->salida($titulo.'.pdf');
        }

        // ===== Parte 1: agrupado por tractivo =====
        if ($data) {
            $posX = 15;
            $posY = 45;
            $i = 0;
            $x = 0;
            $max = 90;
            $col = 15;
            $viajes = 0;

            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(15, 35);
            $this->SetFillColor(200, 200, 200);
            $this->Cell(25, 10, 'TRACTIVO', 1, 0, 'C', 1);
            $this->Cell(15, 10, 'VIAJES', 1, 0, 'C', 1);
            foreach ($data as $arr) {
                $this->SetFont('Arial', 'BU', 14);
                $this->SetXY($posX, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->SetTextColor(0, 0, 0);
                $this->Cell(25, 10, (string) $arr->codtractivo, 1, 0, 'C', 1);
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(15, 10, (string) $arr->viajes, 1, 0, 'C', 1);
                $posY += 10;
                $i++;
                $x++;
                $viajes += $arr->viajes;
                if ($i === $col && $x !== $max) {
                    $posX += 42;
                    $posY = 35;
                    $i = 0;
                    $this->SetFont('Arial', 'B', 10);
                    $this->SetXY($posX, $posY);
                    $this->SetFillColor(200, 200, 200);
                    $this->Cell(25, 10, 'TRACTIVO', 1, 0, 'C', 1);
                    $this->Cell(15, 10, 'VIAJES', 1, 0, 'C', 1);
                    $posY += 10;
                }
                if ($x === $max) {
                    $posX = 15;
                    $posY = 45;
                    $i = 0;
                    $x = 0;
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->SetFont('Arial', 'B', 10);
                    $this->SetXY(15, 35);
                    $this->SetFillColor(200, 200, 200);
                    $this->Cell(25, 10, 'TRACTIVO', 1, 0, 'C', 1);
                    $this->Cell(15, 10, 'VIAJES', 1, 0, 'C', 1);
                }
            }
            $this->SetFont('Arial', 'B', 14);
            $this->SetXY($posX, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(25, 10, 'TOTAL', 1, 0, 'C', 1);
            $this->Cell(15, 10, (string) $viajes, 1, 0, 'C', 1);
        }

        // ===== Parte 2: detalle por carta de porte (rejilla multicolumna) =====
        if ($detalle) {
            $posX = 10;
            $posY = 35;
            $i = 0;
            $x = 0;
            $max = 63;
            $col = 9;

            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            foreach ($detalle as $arr) {
                $this->SetTextColor(0, 0, 0);

                if ((int) $arr->cancelada === 1) {
                    // Cancelada: recuadro de 35 de ancho por 18 de alto.
                    $this->SetXY($posX, $posY);
                    $this->SetFont('Arial', 'U', 16);
                    $this->SetFillColor(255, 255, 255);
                    $this->Cell(35, 6, $this->txt((string) $arr->nrocp), 'LRT', 0, 'C', 1);
                    $this->SetXY($posX, $posY + 6);
                    $this->Cell(35, 12, 'CANCELADA', 'LRB', 0, 'C', 1);
                    $posY += 18;
                } elseif ($arr->fparte === null) {
                    // Sin parte (pendiente de aforo): resaltado si lleva 5+ días.
                    $this->SetFillColor($arr->dias >= 5 ? 255 : 255, $arr->dias >= 5 ? 200 : 255, $arr->dias >= 5 ? 200 : 255);
                    $this->SetXY($posX, $posY);
                    $this->SetFont('Arial', 'B', 16);
                    $this->Cell(20, 6, $this->txt((string) $arr->nrocp), 'LT', 0, 'L', 1);
                    $this->SetFont('Arial', 'BU', 25);
                    $this->Cell(15, 12, substr((string) $arr->femision, 8, 2), 'TR', 0, 'C', 1);
                    $posY += 6;
                    $this->SetFont('Arial', 'BU', 18);
                    $this->SetXY($posX, $posY);
                    $this->Cell(20, 6, $this->txt((string) $arr->codtractivo), 'L', 0, 'L', 1);
                    $posY += 6;
                    $this->SetFont('Arial', 'B', 18);
                    $this->SetXY($posX, $posY);
                    $this->Cell(35, 6, 'HR-'.$arr->nrohr, 'LRB', 0, 'L', 1);
                    $posY += 6;
                } else {
                    // Con parte (aforada): fondo gris.
                    $this->SetFillColor(127, 127, 127);
                    $this->SetXY($posX, $posY);
                    $this->SetFont('Arial', 'B', 16);
                    $this->Cell(20, 6, $this->txt((string) $arr->nrocp), 'LT', 0, 'L', 1);
                    $this->SetFont('Arial', 'BU', 25);
                    $this->Cell(15, 12, substr((string) $arr->femision, 8, 2), 'TR', 0, 'C', 1);
                    $posY += 6;
                    $this->SetFont('Arial', 'BU', 12);
                    $this->SetXY($posX, $posY);
                    $this->Cell(20, 6, $this->txt((string) $arr->codtractivo), 'L', 0, 'L', 1);
                    $posY += 6;
                    $this->SetFont('Arial', 'B', 12);
                    $this->SetXY($posX, $posY);
                    $this->Cell(35, 6, 'HR-'.$arr->nrohr, 'LRB', 0, 'L', 1);
                    $posY += 6;
                }

                $i++;
                $x++;
                if ($i === $col && $x !== $max) {
                    $posX += 38;
                    $posY = 35;
                    $i = 0;
                }
                if ($x === $max) {
                    $posX = 10;
                    $posY = 35;
                    $i = 0;
                    $x = 0;
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                }
            }
        }

        return $this->salida($titulo.'.pdf');
    }

    /** Diferencia en días absolutos (paridad con `modGenerales::restaFechas`). */
    private function restaFechas(?string $dFecIni, ?string $dFecFin): float
    {
        if (! $dFecIni || ! $dFecFin) {
            return 0.0;
        }

        return (float) abs(round((strtotime($dFecFin) - strtotime($dFecIni)) / 86400, 2));
    }

    /** id 6 — CARTA PORTE PARTE DIARIO EMISION. */
    public function pdfCpPdEmision(string $fecha): \Illuminate\Http\Response
    {
        $titulo = 'PARTE DIARIO DE EMISION CARTA DE PORTE';
        $entidadSistema = (int) ($this->entidad->id_sistema ?? 0);

        $campos = [
            ['titulo' => 'FOLIO', 'campo' => 'nrocp', 'ancho' => 20, 'direccion' => 'C', 'letra' => 9, 'letra2' => 10],
            ['titulo' => 'NRO HR', 'campo' => 'nrohr', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'TRACTIVO', 'campo' => 'codtractivo', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => $entidadSistema === 0 ? 'CP MM' : 'ARRASTRE', 'campo' => $entidadSistema === 0 ? 'cpmm' : 'codarrastre', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'CLIENTES', 'campo' => 'nombcliente', 'ancho' => 60, 'direccion' => 'L'],
            ['titulo' => 'ORIGEN', 'campo' => 'origen', 'ancho' => 60, 'direccion' => 'L'],
            ['titulo' => 'CHOFER', 'campo' => 'nombrecompleto', 'ancho' => 60, 'direccion' => 'L'],
        ];

        $data = CartaPorte::with($this->relacionesCarta())
            ->whereHas('hojaRuta', fn ($h) => $h->whereIn('hojas_ruta.id_entidad', $this->entidadIds))
            ->where('fecha_emision', $fecha)
            ->orderBy('numero')
            ->get()
            ->map(fn ($c) => (object) [
                'nrocp' => $c->numero,
                'nrohr' => $c->hojaRuta?->numero,
                'codtractivo' => $c->tractivo?->codigo,
                'codarrastre' => $c->arrastre?->codigo,
                'cpmm' => '',
                'nombcliente' => $this->titulo($c->solicitud?->cliente?->nombre),
                'origen' => $this->titulo($c->solicitud?->lugarOrigen?->nombre),
                'nombrecompleto' => $this->titulo($this->nombreBolsa($c->chofer)),
            ])->all();

        $this->pdfCodificadores('L', 6, $titulo, $fecha, $data, $campos);

        return $this->salida($titulo.'.pdf');
    }

    /** id 7 — CARTA PORTE PARTE DIARIO RECEPCION. */
    public function pdfCpRecepcion(string $fecha): \Illuminate\Http\Response
    {
        $titulo = 'PARTE DIARIO DE RECEPCION DE DOCUMENTOS';
        $campos = [
            ['titulo' => 'NRO', 'campo' => '$nro', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'TRACTIVO', 'campo' => 'codtractivo', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'HOJA RUTA', 'campo' => 'nrohr', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'CARTA PORTE', 'campo' => 'nrocp', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'CHOFER', 'campo' => 'nombrecompleto', 'ancho' => 90, 'direccion' => 'L'],
        ];

        $data = CartaPorte::with($this->relacionesCarta())
            ->whereHas('hojaRuta', fn ($h) => $h->whereIn('hojas_ruta.id_entidad', $this->entidadIds))
            ->where('fecha_recepcion', $fecha)
            ->where('cancelada', false)
            ->orderBy('numero')
            ->get()
            ->map(fn ($c) => (object) [
                'codtractivo' => $c->tractivo?->codigo,
                'nrohr' => $c->hojaRuta?->numero,
                'nrocp' => $c->numero,
                'nombrecompleto' => $this->titulo($this->nombreBolsa($c->chofer)),
            ])->all();

        $this->pdfCodificadores('P', 6, $titulo, $fecha, $data, $campos);

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // HOJAS DE RUTA
    // =====================================================================

    /** id 8 — HOJA RUTA CANCELADAS. */
    public function pdfHrCancelada(string $mes = ''): \Illuminate\Http\Response
    {
        $titulo = 'HOJAS DE RUTA CANCELADAS';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'NRO', 'ancho' => 15, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'NRO HR', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'NOTAS A LA CANCELACION', 'ancho' => 160, 'direccion' => 'L', 'bordes' => '1'],
        ];

        $data = $this->hojasDelMes($anio, $mesNum, true)->map(fn ($h) => (object) [
            'nrohr' => $h->numero,
            'notas' => (string) $h->notas,
        ])->all();

        $posY = 41;
        $i = 1;
        $nro = 1;
        $max = 30;

        if ($data) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(6, 15, 35, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 12);
                $this->SetXY(15, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(15, 6, (string) $nro, 1, 0, 'C', 1);
                $this->Cell(20, 6, (string) $arr->nrohr, 1, 0, 'C', 1);
                $this->Cell(160, 6, $this->txt((string) $arr->notas), 1, 0, 'L', 0);
                $posY += 6;
                $i++;
                $nro++;
                if ($i === $max) {
                    $this->firmasRevisadoAprobado(220);
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(6, 15, 35, $campos);
                    $posY = 41;
                    $i = 1;
                }
            }
            $this->firmasRevisadoAprobado(220);
        } else {
            $this->inicio($titulo, '', 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    /** id 1014 — HOJA RUTA ANALISIS DE LA DOCUMENTACION. */
    public function pdfHrAnalisis(string $mes = ''): \Illuminate\Http\Response
    {
        $titulo = 'HOJAS DE RUTA ANALISIS DE LA DOCUMENTACION';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'NRO', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'NRO HR', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'DEFICIENCIAS DETECTADA', 'ancho' => 75, 'direccion' => 'L'],
            ['titulo' => 'RESPONSABLE', 'ancho' => 90, 'direccion' => 'L'],
        ];

        $data = $this->hojasDelMes($anio, $mesNum, false)
            ->filter(fn ($h) => (string) $h->analisis !== '')
            ->map(fn ($h) => (object) [
                'nrohr' => $h->numero,
                'analisis' => (string) $h->analisis,
                'respanalisis' => '',
            ])->values()->all();

        $posY = 41;
        $i = 1;
        $nro = 1;
        $max = 30;

        if ($data) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(6, 15, 35, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 12);
                $this->SetXY(15, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(10, 6, (string) $nro, 1, 0, 'C', 1);
                $this->Cell(20, 6, (string) $arr->nrohr, 1, 0, 'C', 1);
                $this->SetFont('Arial', '', 10);
                $this->Cell(75, 6, $this->txt((string) $arr->analisis), 1, 0, 'L', 0);
                $this->Cell(90, 6, $this->txt((string) $arr->respanalisis), 1, 0, 'L', 0);
                $posY += 6;
                $i++;
                $nro++;
                if ($i === $max) {
                    $this->firmasRevisadoAprobado(220);
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(6, 15, 35, $campos);
                    $posY = 41;
                    $i = 1;
                }
            }
            $this->firmasRevisadoAprobado(220);
        } else {
            $this->inicio($titulo, '', 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    /** id 9 — HOJA RUTA CONSECUTIVO. */
    public function pdfHrConsecutivo(int $inicio, int $fin): \Illuminate\Http\Response
    {
        $titulo = 'CONSECUTIVO HOJA DE RUTA DESDE FOLIO '.$inicio.' HASTA '.$fin;
        $anio = $this->anioActual();

        $campos1 = [
            ['titulo' => 'NRO HOJA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FECHA ENTREGA', 'ancho' => 45, 'direccion' => 'C'],
            ['titulo' => 'NOMBRE DEL CHOFER', 'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TRACTIVO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'ARRASTRE', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FECHA RECEPCION', 'ancho' => 45, 'direccion' => 'C'],
            ['titulo' => 'KMS TOT', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
        ];
        $campos = [
            ['titulo' => 'RUTA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'D', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'M', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'A', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => '', 'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'D', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'M', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'A', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        $hojas = $this->hojasRango($inicio, $fin, $anio)->keyBy(fn ($h) => (string) $h->nrohr);

        $posY = 47;
        $i = 0;
        $max = 25;

        $this->inicio($titulo, '', 50, 5);
        $this->titulos(6, 15, 35, $campos, $campos1);

        for ($n = $inicio; $n <= $fin; $n++) {
            if ($i === $max) {
                $this->inicio($titulo, '', 50, 5);
                $this->titulos(6, 15, 35, $campos, $campos1);
                $posY = 47;
                $i = 0;
            }
            $arr = $hojas->get((string) $n);
            $this->SetXY(15, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0, 0, 0);
            if (! $arr) {
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(25, 6, (string) $n, 1, 0, 'C', 1);
                $this->Cell(230, 6, 'FOLIO PENDIENTE', 1, 0, 'C', 0);
            } else {
                $this->SetFont('Arial', '', 11);
                $fe = (string) $arr->femision;
                $fc = (string) $arr->fcierre;
                $this->Cell(25, 6, (string) $arr->nrohr, 1, 0, 'C', 1);
                $this->Cell(15, 6, substr($fe, 8, 2), 1, 0, 'C', 1);
                $this->Cell(15, 6, substr($fe, 5, 2), 1, 0, 'C', 1);
                $this->Cell(15, 6, substr($fe, 2, 2), 1, 0, 'C', 1);
                $this->Cell(70, 6, ucwords(mb_strtolower($this->txt(substr((string) $arr->nombrecompleto, 0, 25)))), 1, 0, 'L', 0);
                $this->Cell(25, 6, (string) $arr->codtractivo, 1, 0, 'C', 0);
                $this->Cell(25, 6, (string) $arr->codarrastre, 1, 0, 'C', 0);
                $this->Cell(15, 6, substr($fc, 8, 2), 1, 0, 'C', 1);
                $this->Cell(15, 6, substr($fc, 5, 2), 1, 0, 'C', 1);
                $this->Cell(15, 6, substr($fc, 2, 2), 1, 0, 'C', 1);
                $this->Cell(20, 6, $this->cambiarVariable($arr->kms_total, 2), 1, 0, 'R', 0);
            }
            $posY += 6;
            $i++;
        }

        return $this->salida($titulo.'.pdf');
    }

    /** id 158 — HOJA RUTA (REGISTRO RES 184). */
    public function pdfHrFolios(string $mes = ''): \Illuminate\Http\Response
    {
        $titulo = 'REGISTRO DE HOJAS DE RUTA EMITIDAS RES-184';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos1 = [
            ['titulo' => 'NRO HOJA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'FECHA ENTREGA', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'NOMBRE DEL CHOFER', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TRACTIVO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'ARRASTRE', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FECHA RECEPCION', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'KMS TOT', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'OBSERVACIONES', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 'LTR'],
        ];
        $campos = [
            ['titulo' => 'RUTA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => 'D', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'M', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'A', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => '', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'D', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'M', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'A', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        $data = $this->hojasDelMes($anio, $mesNum, false)->map(fn ($h) => (object) [
            'nrohr' => $h->numero,
            'femision' => $h->fecha_emision?->format('Y-m-d'),
            'fcierre' => $h->fecha_cierre?->format('Y-m-d'),
            'nombrecompleto' => $this->nombreBolsa($h->chofer),
            'codtractivo' => $h->tractivo?->codigo,
            'codarrastre' => $h->arrastre?->codigo,
            'kms_total' => $h->kms_totales,
            'notas' => (string) $h->notas,
            'cancelada' => $h->cancelada,
        ])->all();

        $posY = 47;
        $i = 0;
        $max = 25;

        if ($data) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(6, 15, 35, $campos, $campos1);
            foreach ($data as $arr) {
                $this->SetXY(15, $posY);
                $this->SetFillColor((int) $arr->cancelada === 1 ? 200 : 255, (int) $arr->cancelada === 1 ? 200 : 255, (int) $arr->cancelada === 1 ? 200 : 255);
                $this->SetFont('Arial', '', 11);
                $fe = (string) $arr->femision;
                $fc = (string) $arr->fcierre;
                $this->Cell(20, 6, (string) $arr->nrohr, 1, 0, 'C', 1);
                $this->Cell(10, 6, substr($fe, 8, 2), 1, 0, 'C', 1);
                $this->Cell(10, 6, substr($fe, 5, 2), 1, 0, 'C', 1);
                $this->Cell(10, 6, substr($fe, 2, 2), 1, 0, 'C', 1);
                $this->SetFont('Arial', '', 8);
                $this->Cell(50, 6, ucwords(mb_strtolower($this->txt(substr((string) $arr->nombrecompleto, 0, 25)))), 1, 0, 'L', 1);
                $this->SetFont('Arial', '', 11);
                $this->Cell(20, 6, (string) $arr->codtractivo, 1, 0, 'C', 1);
                $this->Cell(20, 6, (string) $arr->codarrastre, 1, 0, 'C', 1);
                $this->Cell(10, 6, substr($fc, 8, 2), 1, 0, 'C', 1);
                $this->Cell(10, 6, substr($fc, 5, 2), 1, 0, 'C', 1);
                $this->Cell(10, 6, substr($fc, 2, 2), 1, 0, 'C', 1);
                $this->Cell(20, 6, $this->cambiarVariable($arr->kms_total, 2), 1, 0, 'R', 1);
                $this->SetFont('Arial', '', 8);
                $this->Cell(60, 6, substr($this->txt((string) $arr->notas), 0, 30), 1, 0, 'L', 1);
                $posY += 6;
                $i++;
                if ($i === $max) {
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(6, 15, 35, $campos, $campos1);
                    $posY = 47;
                    $i = 0;
                }
            }
        } else {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    /** id 10 — HOJA RUTA CONTROL ESTADO (rejilla HR/DIA multicolumna). */
    public function pdfHrEstado(string $mes = ''): \Illuminate\Http\Response
    {
        $titulo = 'PARTE ESTADO DE LA HOJA RUTA';
        [$anio, $mesNum] = $this->anioMes($mes);

        $data = $this->hojasDelMes($anio, $mesNum, false)->map(fn ($h) => (object) [
            'nrohr' => $h->numero,
            'femision' => $h->fecha_emision?->format('Y-m-d'),
            'fcierre' => $h->fecha_cierre,
            'cancelada' => $h->cancelada,
        ])->all();

        if (! $data) {
            $this->inicio($titulo, '', 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->salida($titulo.'.pdf');
        }

        $posX = 10;
        $posY = 41;
        $i = 0;
        $x = 1;
        $max = 209;
        $col = 26;

        $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
        $this->encabezadoEstado($posX);

        foreach ($data as $arr) {
            if ((int) $arr->cancelada === 1) {
                $this->SetXY($posX, $posY);
                $this->SetFont('Arial', 'U', 12);
                $this->SetFillColor(255, 255, 255);
                $this->SetTextColor(255, 0, 0);
                $this->Cell(30, 6, 'CANCELADA', 1, 0, 'C', 1);
            } else {
                $this->SetXY($posX, $posY);
                $this->SetFillColor($arr->fcierre ? 200 : 255, $arr->fcierre ? 200 : 255, $arr->fcierre ? 200 : 255);
                $this->SetFont('Arial', 'BU', 11);
                $this->SetTextColor(0, 0, 0);
                $this->Cell(20, 6, (string) $arr->nrohr, 1, 0, 'C', 1);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(10, 6, substr((string) $arr->femision, 8, 2), 1, 0, 'C', 1);
            }
            $posY += 6;
            $i++;
            $x++;

            if ($i === $col && $x !== $max) {
                $posX += 33;
                $posY = 41;
                $i = 0;
                $this->encabezadoEstado($posX);
            }
            if ($x === $max) {
                $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                $posX = 10;
                $posY = 41;
                $i = 0;
                $x = 1;
                $this->encabezadoEstado($posX);
            }
        }

        return $this->salida($titulo.'.pdf');
    }

    /** id 12 — HOJA RUTA PARTE DIARIO EMISION. */
    public function pdfHrPdEmision(string $fecha): \Illuminate\Http\Response
    {
        $titulo = 'PARTE DIARIO EMISION HOJA RUTA ';
        $campos = [
            ['titulo' => 'FOLIO', 'campo' => 'nrohr', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'TRACTIVO', 'campo' => 'codtractivo', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'ARRASTRE', 'campo' => 'codarrastre', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'NOMBRE DEL CHOFER', 'campo' => 'nombrecompleto', 'ancho' => 90, 'direccion' => 'L'],
            ['titulo' => 'DISPONIBLE', 'campo' => 'kms_disp', 'ancho' => 25, 'direccion' => 'C'],
        ];

        $data = HojasRuta::with(['tractivo:id,codigo', 'arrastre:id,codigo', 'chofer:id,nombre,apellidos'])
            ->whereIn('id_entidad', $this->entidadIds)
            ->where('fecha_emision', $fecha)
            ->get()
            ->map(fn ($h) => (object) [
                'nrohr' => $h->numero,
                'codtractivo' => $h->tractivo?->codigo,
                'codarrastre' => $h->arrastre?->codigo,
                'nombrecompleto' => $this->titulo($this->nombreBolsa($h->chofer)),
                'kms_disp' => $h->kms_disponible,
            ])->all();

        $this->pdfCodificadores('P', 6, $titulo, $fecha, $data, $campos);

        return $this->salida($titulo.'.pdf');
    }

    /** id 11 — HOJA RUTA PARTE DIARIO CIERRE. */
    public function pdfHrPdCierre(string $fecha): \Illuminate\Http\Response
    {
        $titulo = 'PARTE DIARIO CIERRE HOJA RUTAS';
        $campos = [
            ['titulo' => 'NRO', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'NRO HR', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'EQUIPO', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'ARRASTRE', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'NOMBRE DEL CHOFER', 'ancho' => 70, 'direccion' => 'C'],
            ['titulo' => 'KMS TOTAL', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'CONSUMIDO', 'ancho' => 27, 'direccion' => 'C'],
            ['titulo' => 'HABILITADO', 'ancho' => 27, 'direccion' => 'C'],
            ['titulo' => 'DIFERENCIA', 'ancho' => 27, 'direccion' => 'C'],
        ];

        $data = HojasRuta::with(['tractivo:id,codigo', 'arrastre:id,codigo', 'chofer:id,nombre,apellidos'])
            ->whereIn('id_entidad', $this->entidadIds)
            ->where('fecha_cierre', $fecha)
            ->orderBy('numero')
            ->get()
            ->map(fn ($h) => (object) [
                'nrohr' => $h->numero,
                'codtractivo' => $h->tractivo?->codigo,
                'codarrastre' => $h->arrastre?->codigo,
                'nombrecompleto' => $this->nombreBolsa($h->chofer),
                'kms_total' => (float) $h->kms_totales,
                'comb_cons' => (float) $h->combustible_consumido,
                'comb_hab' => (float) $h->combustible_habilitado,
            ])->all();

        $posY = 45;
        $i = 1;
        $nro = 1;
        $max = 25;
        $kmsTotal = 0.0;
        $combHab = 0.0;
        $combCons = 0.0;

        if ($data) {
            $this->inicio($titulo, $fecha, 50, 5);
            $this->titulos(10, 15, 35, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 12);
                $this->SetXY(15, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(10, 6, (string) $nro, 1, 0, 'C', 1);
                $this->Cell(20, 6, (string) $arr->nrohr, 1, 0, 'C', 1);
                $this->Cell(25, 6, (string) $arr->codtractivo, 1, 0, 'C', 1);
                $this->Cell(25, 6, (string) $arr->codarrastre, 1, 0, 'C', 1);
                $this->Cell(70, 6, ucwords(mb_strtolower($this->txt(substr((string) $arr->nombrecompleto, 0, 25)))), 1, 0, 'L', 0);
                $this->Cell(25, 6, $this->cambiarVariable($arr->kms_total, 2), 1, 0, 'R', 1);
                $this->Cell(27, 6, $this->cambiarVariable($arr->comb_cons, 2), 1, 0, 'R', 1);
                $this->Cell(27, 6, $this->cambiarVariable($arr->comb_hab, 2), 1, 0, 'R', 1);
                $this->Cell(27, 6, $this->cambiarVariable($arr->comb_cons - $arr->comb_hab, 2), 1, 0, 'R', 1);
                $posY += 6;
                $i++;
                $nro++;
                $kmsTotal += $arr->kms_total;
                $combHab += $arr->comb_hab;
                $combCons += $arr->comb_cons;
                if ($i === $max) {
                    $this->inicio($titulo, $fecha, 50, 5);
                    $this->titulos(10, 15, 35, $campos);
                    $posY = 45;
                    $i = 1;
                }
            }
            $this->SetFont('Arial', 'B', 14);
            $this->SetXY(15, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->Cell(150, 10, 'TOTALES DEL DIA', 1, 0, 'C', 1);
            $this->Cell(25, 10, $this->cambiarVariable($kmsTotal, 2), 1, 0, 'R', 1);
            $this->Cell(27, 10, $this->cambiarVariable($combCons, 2), 1, 0, 'R', 1);
            $this->Cell(27, 10, $this->cambiarVariable($combHab, 2), 1, 0, 'R', 1);
            $this->Cell(27, 10, $this->cambiarVariable($combCons - $combHab, 2), 1, 0, 'R', 1);
        } else {
            $this->inicio($titulo, $fecha, 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // Datos y utilidades
    // =====================================================================

    private function cartasDelMes(int $anio, int $mes, bool $canceladas)
    {
        return CartaPorte::with($this->relacionesCarta())
            ->whereHas('hojaRuta', fn ($h) => $h->whereIn('hojas_ruta.id_entidad', $this->entidadIds))
            ->whereYear('fecha_emision', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('fecha_emision', $mes))
            ->when($canceladas, fn ($q) => $q->where('cancelada', true))
            ->orderBy('numero')
            ->get();
    }

    private function hojasDelMes(int $anio, int $mes, bool $canceladas)
    {
        return HojasRuta::with($this->relacionesHoja())
            ->whereIn('id_entidad', $this->entidadIds)
            ->whereYear('fecha_emision', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('fecha_emision', $mes))
            ->when($canceladas, fn ($q) => $q->where('cancelada', true))
            ->orderBy('numero')
            ->get();
    }

    private function cartasRango(int $inicio, int $fin, int $anio)
    {
        return CartaPorte::with($this->relacionesCarta())
            ->whereHas('hojaRuta', fn ($h) => $h->whereIn('hojas_ruta.id_entidad', $this->entidadIds))
            ->whereYear('fecha_emision', $anio)
            ->whereBetween('numero', [(string) $inicio, (string) $fin])
            ->orderBy('numero')
            ->get()
            ->map(fn ($c) => $this->filaConsecutivoCarta($c));
    }

    private function hojasRango(int $inicio, int $fin, int $anio)
    {
        return HojasRuta::with($this->relacionesHoja())
            ->whereIn('id_entidad', $this->entidadIds)
            ->whereYear('fecha_emision', $anio)
            ->whereBetween('numero', [(string) $inicio, (string) $fin])
            ->orderBy('numero')
            ->get()
            ->map(fn ($h) => (object) [
                'nrohr' => $h->numero,
                'femision' => $h->fecha_emision?->format('Y-m-d'),
                'fcierre' => $h->fecha_cierre?->format('Y-m-d'),
                'nombrecompleto' => $this->nombreBolsa($h->chofer),
                'codtractivo' => $h->tractivo?->codigo,
                'codarrastre' => $h->arrastre?->codigo,
                'kms_total' => $h->kms_totales,
            ]);
    }

    private function filaConsecutivoCarta(CartaPorte $c): object
    {
        $aforo = $c->aforos->first();
        $lineas = $aforo?->lineas->keyBy('posicion');
        $l1 = $lineas?->get(1);
        $l2 = $lineas?->get(2);
        $l3 = $lineas?->get(3);

        $kvacios = $aforo?->km_vacio_total;
        if ((int) $l2?->id_tipo_carga === 16) {
            $kvacios = $l2->peso_cobrar;
        }
        if ((int) $l3?->id_tipo_carga === 16) {
            $kvacios = $l3->peso_cobrar;
        }

        return (object) [
            'nrocp' => $c->numero,
            'femision' => $c->fecha_emision?->format('Y-m-d'),
            'cancelada' => $c->cancelada,
            'notas' => (string) $c->notas,
            'conduce' => (string) $c->conduce,
            'pesocobrar1' => $l1?->peso_cobrar,
            'tnreal' => $aforo && $aforo->tn_real_total ? round((float) $aforo->tn_real_total * 1000, 2) : round((float) $c->toneladas * 1000, 2),
            'kmcarga' => $aforo?->km_carga_total,
            'kmvacio' => $kvacios,
            'ingresomt' => $aforo?->ingreso_mt,
            'ttotal' => $aforo?->tiempo_total,
            'fcarga' => $aforo?->fecha_carga?->format('Y-m-d'),
            'hcarga1' => $aforo?->hora_carga_1,
            'fdescarga' => $aforo?->fecha_descarga?->format('Y-m-d'),
            'hdescarga2' => $aforo?->hora_descarga_2,
            'codtractivo' => $c->tractivo?->codigo,
            'codarrastre' => $c->arrastre?->codigo,
            'nombrecompleto' => $this->nombreBolsa($c->chofer),
        ];
    }

    private function relacionesCarta(): array
    {
        return [
            'tractivo:tractivos.id,tractivos.codigo,tractivos.placa,tractivos.id_entidad',
            'arrastre:arrastres.id,arrastres.codigo,arrastres.placa,arrastres.id_entidad',
            'chofer:id,nombre,apellidos,ci',
            'hojaRuta:id,numero,id_entidad',
            'solicitud:id,numero,id_cliente,id_lugar_origen,id_lugar_destino',
            'solicitud.cliente:id,nombre',
            'solicitud.lugarOrigen:id,nombre',
            'aforos:id,id_carta_porte,tn_real_total,km_carga_total,km_vacio_total,ingreso_mt,tiempo_total,fecha_carga,hora_carga_1,fecha_descarga,hora_descarga_2,fecha_parte',
            'aforos.lineas:id,id_aforo,posicion,id_tipo_carga,peso_cobrar',
        ];
    }

    private function relacionesHoja(): array
    {
        return [
            'tractivo:id,codigo,placa,id_entidad',
            'arrastre:id,codigo,placa,id_entidad',
            'chofer:id,nombre,apellidos,ci',
        ];
    }

    private function camposConsecutivoCarta(): array
    {
        return [
            ['titulo' => 'FOLIO', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'EMISION', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'MEDIOS', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'CHOFER', 'ancho' => 45, 'direccion' => 'C'],
            ['titulo' => 'T/REAL', 'ancho' => 18, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'CONDUCE', 'ancho' => 18, 'direccion' => 'C'],
            ['titulo' => 'T/COBRADO', 'ancho' => 18, 'direccion' => 'C'],
            ['titulo' => 'KCARGA', 'ancho' => 18, 'direccion' => 'C'],
            ['titulo' => 'KVACIOS', 'ancho' => 18, 'direccion' => 'C'],
            ['titulo' => 'FLETE', 'ancho' => 18, 'direccion' => 'C'],
            ['titulo' => 'F-H/CARGA', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'F-H/DESCARGA', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'T/TOTAL', 'ancho' => 18, 'direccion' => 'C'],
            ['titulo' => 'OBSERVACIONES', 'ancho' => 50, 'direccion' => 'C'],
        ];
    }

    private function encabezadoEstado(float $posX): void
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY($posX, 35);
        $this->SetFillColor(200, 200, 200);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(20, 6, 'HR', 1, 0, 'C', 1);
        $this->Cell(10, 6, 'DIA', 1, 0, 'C', 1);
        $this->Cell(3, 6, '', 0, 0, 'C', 0);
    }

    private function medios(object $arr): string
    {
        $tractivo = (string) $arr->codtractivo;
        $arrastre = (string) $arr->codarrastre;

        return $arrastre !== '' ? $tractivo.'-'.$arrastre : $tractivo;
    }

    private function nombreBolsa($bolsa): string
    {
        if (! $bolsa) {
            return '';
        }

        return trim(((string) $bolsa->nombre).' '.((string) $bolsa->apellidos));
    }

    /** Title Case (paridad con los nombres del legacy). */
    private function titulo(?string $texto): string
    {
        return ucwords(mb_strtolower((string) $texto));
    }

    /** Devuelve [anio, mes] desde 'MM', 'YYYY-MM' o vacío (sesión). */
    private function anioMes(string $mes): array
    {
        $fechaOps = $this->fechaOperaciones ?: now()->toDateString();

        if ($mes === '' || $mes === 'TODOS') {
            return [(int) substr($fechaOps, 0, 4), (int) substr($fechaOps, 5, 2)];
        }
        if (strlen($mes) === 2) {
            return [(int) substr($fechaOps, 0, 4), (int) $mes];
        }
        if (strlen($mes) >= 7) {
            return [(int) substr($mes, 0, 4), (int) substr($mes, 5, 2)];
        }

        return [(int) substr($fechaOps, 0, 4), (int) substr($fechaOps, 5, 2)];
    }

    private function anioActual(): int
    {
        return (int) substr($this->fechaOperaciones ?: now()->toDateString(), 0, 4);
    }
}
