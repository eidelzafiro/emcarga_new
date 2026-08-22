<?php

namespace App\Services\Reports;

use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\Tractivo;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fase 6 / R-1: reportes del grupo DOCUMENTOS del legacy (controles de Cartas de
 * Porte y Hojas de Ruta: estado, parte diario, consecutivo, canceladas, registros
 * oficiales). Todos son listados tabulares sobre las mismas fuentes, filtrados por
 * mes/fecha y acotados a la entidad activa (matriz ve sus filiales).
 */
class DocumentosReportService extends BaseReportService
{
    /** ids de reporte legacy servidos por esta clase (ver ReportesDispatcher). */
    public const CARTAS = [
        3, 4, 5, 6, 7, 157,
    ];

    public const HOJAS = [
        8, 9, 10, 11, 12, 158, 1014,
    ];

    // === Cartas de Porte ===

    public function cartaPorteCanceladas(array $filtros): Response
    {
        return $this->listadoCartas(
            'Cartas de Porte Canceladas',
            $filtros,
            fn ($q) => $q->where('cancelada', true),
            $this->columnasCarta(true),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function cartaPorteConsecutivo(array $filtros): Response
    {
        return $this->listadoCartas(
            'Cartas de Porte por Consecutivo',
            $filtros,
            null,
            $this->columnasCarta(false),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function cartaPorteControlEstado(array $filtros): Response
    {
        return $this->listadoCartas(
            'Cartas de Porte - Control de Estado',
            $filtros,
            null,
            $this->columnasCarta(true),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function cartaPorteParteDiarioEmision(array $filtros): Response
    {
        return $this->listadoCartas(
            'Cartas de Porte - Parte Diario de Emisión',
            $filtros,
            null,
            $this->columnasCartaFecha('fecha_emision'),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function cartaPorteParteDiarioRecepcion(array $filtros): Response
    {
        return $this->listadoCartas(
            'Cartas de Porte - Parte Diario de Recepción',
            $filtros,
            null,
            $this->columnasCartaFecha('fecha_recepcion'),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function cartaPorteRegistroRes2132019(array $filtros): Response
    {
        return $this->listadoCartas(
            'Cartas de Porte (Registro Res. 213-2019)',
            $filtros,
            null,
            $this->columnasCarta(true),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    // === Hojas de Ruta ===

    public function hojaRutaCanceladas(array $filtros): Response
    {
        return $this->listadoHojas(
            'Hojas de Ruta Canceladas',
            $filtros,
            fn ($q) => $q->where('cancelada', true),
            $this->columnasHoja(true),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function hojaRutaConsecutivo(array $filtros): Response
    {
        return $this->listadoHojas(
            'Hojas de Ruta por Consecutivo',
            $filtros,
            null,
            $this->columnasHoja(false),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function hojaRutaControlEstado(array $filtros): Response
    {
        return $this->listadoHojas(
            'Hojas de Ruta - Control de Estado',
            $filtros,
            null,
            $this->columnasHoja(true),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function hojaRutaParteDiarioCierre(array $filtros): Response
    {
        return $this->listadoHojas(
            'Hojas de Ruta - Parte Diario de Cierre',
            $filtros,
            null,
            $this->columnasHojaFecha('fecha_cierre'),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function hojaRutaParteDiarioEmision(array $filtros): Response
    {
        return $this->listadoHojas(
            'Hojas de Ruta - Parte Diario de Emisión',
            $filtros,
            null,
            $this->columnasHojaFecha('fecha_emision'),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function hojaRutaRegistroRes184(array $filtros): Response
    {
        return $this->listadoHojas(
            'Hojas de Ruta (Registro Res. 184)',
            $filtros,
            null,
            $this->columnasHoja(true),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    public function hojaRutaAnalisisDocumentacion(array $filtros): Response
    {
        return $this->listadoHojas(
            'Hojas de Ruta - Análisis de Documentación',
            $filtros,
            null,
            $this->columnasHoja(true),
            ['landscape' => true, 'periodo' => $this->periodoTexto($filtros)],
        );
    }

    // === Núcleo reutilizable ===

    private function listadoCartas(string $titulo, array $filtros, ?callable $extra, array $columnas, array $opts): Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $query = CartaPorte::with([
            'tractivo' => fn ($q) => $q->select('id', 'placa', 'descripcion', 'id_entidad'),
            'cliente'  => fn ($q) => $q->select('id', 'nombre'),
        ])->whereHas('tractivo', fn ($q) => $q->whereIn('tractivos.id_entidad', $ids));

        if ($desde && $hasta) {
            $query->whereBetween('fecha_emision', [$desde, $hasta]);
        }
        if ($extra) {
            $extra($query);
        }

        $filas = $query->orderBy('fecha_emision')->orderBy('numero')
            ->get()->map(fn ($c) => $this->filaCarta($c, $columnas))->all();

        return $this->reporteTablaPdf($titulo, $columnas, $filas, $opts);
    }

    private function listadoHojas(string $titulo, array $filtros, ?callable $extra, array $columnas, array $opts): Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $query = HojasRuta::with([
            'tractivo' => fn ($q) => $q->select('id', 'placa', 'descripcion', 'id_entidad'),
            'cliente'  => fn ($q) => $q->select('id', 'nombre'),
        ])->whereHas('tractivo', fn ($q) => $q->whereIn('tractivos.id_entidad', $ids));

        if ($desde && $hasta) {
            $query->whereBetween('fecha_emision', [$desde, $hasta]);
        }
        if ($extra) {
            $extra($query);
        }

        $filas = $query->orderBy('fecha_emision')->orderBy('id')
            ->get()->map(fn ($h) => $this->filaHoja($h, $columnas))->all();

        return $this->reporteTablaPdf($titulo, $columnas, $filas, $opts);
    }

    private function entidadIds(): array
    {
        $activa = (int) entidadActivaId();
        if (! $activa) {
            return [23];
        }

        return Entidad::subEntidadesIds($activa);
    }

    private function periodoTexto(array $filtros): string
    {
        if (! empty($filtros['mes'])) {
            try {
                $m = \Carbon\Carbon::parse($filtros['mes']);

                return 'Período: '.$this->nombreMes($m->month).' '.$m->year;
            } catch (\Exception) {}
        }
        [$d, $h] = $this->rangoFiltros($filtros);

        return $d && $h ? "Período: $d al $h" : '';
    }

    // === Columnas y filas ===

    private function columnasCarta(bool $conEstado): array
    {
        $cols = [
            ['key' => 'numero', 'label' => 'No.', 'num' => false],
            ['key' => 'fecha_emision', 'label' => 'Emisión', 'num' => false],
            ['key' => 'cliente', 'label' => 'Cliente', 'num' => false],
            ['key' => 'tractivo', 'label' => 'Tractivo', 'num' => false],
            ['key' => 'toneladas', 'label' => 'Ton', 'num' => true],
            ['key' => 'distancia', 'label' => 'Kms', 'num' => true],
        ];
        if ($conEstado) {
            $cols[] = ['key' => 'estado', 'label' => 'Estado', 'num' => false];
            $cols[] = ['key' => 'cancelada', 'label' => 'Cancelada', 'num' => false];
        }

        return $cols;
    }

    private function columnasCartaFecha(string $campo): array
    {
        return array_merge(
            [['key' => $campo, 'label' => 'Fecha', 'num' => false]],
            $this->columnasCarta(false),
        );
    }

    private function columnasHoja(bool $conEstado): array
    {
        $cols = [
            ['key' => 'id', 'label' => 'No.', 'num' => false],
            ['key' => 'fecha_emision', 'label' => 'Emisión', 'num' => false],
            ['key' => 'cliente', 'label' => 'Cliente', 'num' => false],
            ['key' => 'tractivo', 'label' => 'Tractivo', 'num' => false],
        ];
        if ($conEstado) {
            $cols[] = ['key' => 'estado', 'label' => 'Estado', 'num' => false];
            $cols[] = ['key' => 'cancelada', 'label' => 'Cancelada', 'num' => false];
        }

        return $cols;
    }

    private function columnasHojaFecha(string $campo): array
    {
        return array_merge(
            [['key' => $campo, 'label' => 'Fecha', 'num' => false]],
            $this->columnasHoja(false),
        );
    }

    private function filaCarta(CartaPorte $c, array $columnas): array
    {
        $fila = [];
        foreach ($columnas as $col) {
            $k = $col['key'];
            $fila[$k] = match ($k) {
                'numero' => $c->numero,
                'fecha_emision' => $this->cambiarFormatoFecha($c->fecha_emision),
                'cliente' => $c->cliente?->nombre ?? '',
                'tractivo' => ($c->tractivo?->placa ?? '').' '.($c->tractivo?->descripcion ?? ''),
                'toneladas' => $this->formatoNumero($c->toneladas, 2),
                'distancia' => $this->formatoNumero($c->distancia, 0),
                'estado' => $c->estado ?? '',
                'cancelada' => $c->cancelada ? 'SÍ' : '',
                'fecha_recepcion' => $this->cambiarFormatoFecha($c->fecha_recepcion),
                default => '',
            };
        }

        return $fila;
    }

    private function filaHoja(HojasRuta $h, array $columnas): array
    {
        $fila = [];
        foreach ($columnas as $col) {
            $k = $col['key'];
            $fila[$k] = match ($k) {
                'id' => $h->id,
                'fecha_emision' => $this->cambiarFormatoFecha($h->fecha_emision),
                'cliente' => $h->cliente?->nombre ?? '',
                'tractivo' => ($h->tractivo?->placa ?? '').' '.($h->tractivo?->descripcion ?? ''),
                'estado' => $h->estado ?? '',
                'cancelada' => $h->cancelada ? 'SÍ' : '',
                'fecha_cierre' => $this->cambiarFormatoFecha($h->fecha_cierre),
                default => '',
            };
        }

        return $fila;
    }
}
