<?php

namespace App\Services;

use App\Models\Bolsa;
use App\Models\Aforo;
use Carbon\Carbon;

class ReportePrenominaService
{
    public function __construct(
        private SalarioChoferCalcService $choferCalc,
        private SalarioAdminCalcService $adminCalc,
    ) {}

    /**
     * Prenomina de choferes transportación.
     */
    public function prenominaChoferes(int $mes, int $ano, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);

        $choferes = Bolsa::where('activo', true)
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja'))
            ->when(!empty($entidadesPermitidas), fn ($q) => $q->whereIn('id_entidad', $entidadesPermitidas))
            ->where(function ($q) use ($mes, $ano) {
                $q->whereHas('hojasRuta.cartasPorte.aforos', function ($aq) use ($mes, $ano) {
                    $aq->whereYear('fecha_parte', $ano)
                       ->whereMonth('fecha_parte', $mes)
                       ->whereHas('cartaPorte', fn ($cp) => $cp->where('cancelada', false));
                });
            })
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->with(['cargo:id,nombre,tarifa,cla', 'area:id,nombre'])
            ->get();

        $registros = [];
        $totales = [
            'regular' => 0, 'irregular' => 0, 'ingresos' => 0, 'salario_cp' => 0,
            'imp_cla' => 0, 'imp_nocturnidad_1' => 0, 'imp_nocturnidad_2' => 0,
            'imp_feriados' => 0, 'salario_final' => 0, 'toneladas' => 0, 'km_total' => 0,
        ];

        foreach ($choferes as $chofer) {
            $calculado = $this->choferCalc->calcularSalarioChofer($chofer->id, $mes, $ano);
            if (!$calculado) continue;

            $registros[] = $calculado;

            foreach ($totales as $key => &$val) {
                $val += $calculado[$key] ?? 0;
            }
        }

        return [
            'titulo' => 'PRENOMINA CHOFERES TRANSPORTE',
            'periodo' => Carbon::createFromDate($ano, $mes, 1)->format('F Y'),
            'registros' => $registros,
            'totales' => $totales,
        ];
    }

    /**
     * Prenomina personal administrativo.
     */
    public function prenominaAdministrativo(int $mes, int $ano, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);

        // Buscar área de transporte para excluir
        $areaTransporteId = \App\Models\Area::whereRaw("UPPER(nombre) LIKE '%TRANSPOR%'")->value('id');

        $empleados = Bolsa::where('activo', true)
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja'))
            ->when(!empty($entidadesPermitidas), fn ($q) => $q->whereIn('id_entidad', $entidadesPermitidas))
            ->when($areaTransporteId, fn ($q) => $q->where('id_area', '!=', $areaTransporteId))
            ->orderByRaw('id_area ASC, nombre ASC, apellidos ASC')
            ->with(['cargo:id,nombre,tarifa,cla,id_grupo_horario', 'area:id,nombre'])
            ->get();

        $registros = [];
        $totales = [
            'regular' => 0, 'irregular' => 0, 'h_extra' => 0, 'imp_h_extra' => 0,
            'dias_taller' => 0, 'feriados_editados' => 0,
            'tarifa' => 0, 'tarifa_mes' => 0, 'tiempo_mes' => 0, 'fondo_tiempo' => 0,
            'salario_base' => 0, 'bono_alimentacion' => 0,
            'salario_final' => 0,
        ];

        foreach ($empleados as $empleado) {
            $calculado = $this->adminCalc->calcularSalarioEmpleado($empleado, $mes, $ano);
            if (!$calculado) continue;

            $registros[] = $calculado;

            foreach ($totales as $key => &$val) {
                $val += $calculado[$key] ?? 0;
            }
        }

        // Agrupar por área
        $porArea = [];
        foreach ($registros as $reg) {
            $area = $reg['area'] ?? 'Sin área';
            $porArea[$area][] = $reg;
        }

        return [
            'titulo' => 'PRENOMINA PERSONAL ADMINISTRATIVO',
            'periodo' => Carbon::createFromDate($ano, $mes, 1)->format('F Y'),
            'registros' => $registros,
            'por_area' => $porArea,
            'totales' => $totales,
        ];
    }

    /**
     * Modelo 1 — Control de transportaciones por chofer.
     * Detalle de cartas de porte del mes, con tiempos, costos y salarios.
     */
    public function modelo1(int $mes, int $ano, ?int $idBolsa = null): array
    {
        $query = Aforo::whereYear('fecha_parte', $ano)
            ->whereMonth('fecha_parte', $mes)
            ->whereHas('cartaPorte', fn ($cp) => $cp->where('cancelada', false))
            ->with([
                'cartaPorte:id,cancelada,numero,id_hoja_ruta,id_solicitud,fecha_emision,distancia',
                'cartaPorte.hojaRuta:id,numero,id_tractivo,id_arrastre,id_chofer,id_chofer2',
                'cartaPorte.hojaRuta.tractivo:id,codigo,placa',
                'cartaPorte.hojaRuta.arrastre:id,codigo,placa',
                'cartaPorte.hojaRuta.chofer:id,nombre,apellidos',
                'cartaPorte.hojaRuta.chofer2:id,nombre,apellidos',
                'cartaPorte.solicitudServicio:id,fk_destino,fk_origen',
                'cartaPorte.solicitudServicio.lugarOrigen:id,nombre',
                'cartaPorte.solicitudServicio.lugarDestino:id,nombre',
                'cartaPorte.tipoCarga:id,nombre',
                'cartaPorte.producto:id,nombre',
                'hojaRuta:id,numero,fecha_emision',
                'tasa:id,nombre,tasa,tasa2',
            ]);

        if ($idBolsa) {
            $query->where(function ($q) use ($idBolsa) {
                $q->whereHas('cartaPorte.hojaRuta', fn ($hr) => $hr->where('id_chofer', $idBolsa))
                  ->orWhereHas('cartaPorte.hojaRuta', fn ($hr) => $hr->where('id_chofer2', $idBolsa));
            });
        }

        $aforos = $query->orderBy('fecha_parte')
            ->orderBy('hoja_ruta_id')
            ->get();

        // Agrupar por chofer
        $porChofer = [];
        foreach ($aforos as $aforo) {
            $cp = $aforo->cartaPorte;
            if (!$cp) continue;

            $hr = $cp->hojaRuta;
            $choferId = $hr?->id_chofer;
            $chofer = $hr?->chofer;
            $nombreChofer = $chofer ? trim($chofer->nombre . ' ' . $chofer->apellidos) : 'Desconocido';

            if (!isset($porChofer[$choferId])) {
                $bolsa = Bolsa::find($choferId);
                $porChofer[$choferId] = [
                    'id_bolsa' => $choferId,
                    'nombre' => $nombreChofer,
                    'carnet' => $bolsa?->ci ?? '',
                    'registros' => [],
                    'totales' => [
                        'ingresos' => 0, 'salario_cp' => 0, 'km_total' => 0,
                        'toneladas' => 0, 'tiempo_total' => 0,
                    ],
                ];
            }

            $registro = [
                'fecha' => $aforo->fecha_parte,
                'nro_cp' => $cp->numero ?? '',
                'nro_hr' => $hr?->numero ?? '',
                'tractivo' => $hr?->tractivo?->codigo ?? '',
                'arrastre' => $hr?->arrastre?->codigo ?? '',
                'equipo' => trim(($hr?->tractivo?->codigo ?? '') . ' / ' . ($hr?->arrastre?->codigo ?? ''), ' /'),
                'origen' => $cp->solicitudServicio?->lugarOrigen?->nombre ?? '',
                'destino' => $cp->solicitudServicio?->lugarDestino?->nombre ?? '',
                'producto' => $cp->producto?->nombre ?? '',
                'tipo_carga' => $cp->tipoCarga?->nombre ?? '',
                'distancia' => $cp->distancia ?? 0,
                'km_total' => $aforo->km_total_total ?? $aforo->km_total ?? 0,
                'tn_real' => $aforo->tn_real_total ?? $aforo->tn_real ?? 0,
                'tiempo_total' => $aforo->tiempo_total ?? 0,
                'ingreso' => $aforo->ingreso_mt ?? 0,
                'tasa_nombre' => $aforo->tasa?->nombre ?? '',
                'tasa_valor' => $aforo->tasa?->tasa ?? 0,
                'salario' => $aforo->salario ?? 0,
                'almacenaje' => $aforo->almacenaje ?? 0,
                'es_feriado' => (bool) ($aforo->tiempo_feriado ?? 0),
                'doble_chofer' => ($hr?->id_chofer2 && $hr->id_chofer2 != $hr->id_chofer),
            ];

            $porChofer[$choferId]['registros'][] = $registro;

            $porChofer[$choferId]['totales']['ingresos'] += $registro['ingreso'];
            $porChofer[$choferId]['totales']['salario_cp'] += $registro['salario'];
            $porChofer[$choferId]['totales']['km_total'] += $registro['km_total'];
            $porChofer[$choferId]['totales']['toneladas'] += $registro['tn_real'];
            $porChofer[$choferId]['totales']['tiempo_total'] += $registro['tiempo_total'];
        }

        // Ordenar por nombre
        uasort($porChofer, fn ($a, $b) => strcmp($a['nombre'], $b['nombre']));

        return [
            'titulo' => 'MODELO 1 - CONTROL TRANSPORTACIONES CHOFERES',
            'periodo' => Carbon::createFromDate($ano, $mes, 1)->format('F Y'),
            'por_chofer' => array_values($porChofer),
        ];
    }

    private function entidadActivaId(): ?int
    {
        return (int) session('entidad_activa_id') ?: null;
    }

    private function entidadesPermitidas(?int $entidadId = null): array
    {
        $id = $entidadId ?? $this->entidadActivaId();
        if (!$id) return [];

        $entidad = \App\Models\Entidad::find($id);
        if (!$entidad) return [$id];

        $hijas = \App\Models\Entidad::where('parent_id', $id)->pluck('id')->toArray();
        return array_merge([$id], $hijas);
    }
}
