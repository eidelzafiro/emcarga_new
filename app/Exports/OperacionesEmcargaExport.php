<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * REPORTE DE OPERACIONES EMCARGA (legacy Reportes2::excel_resumen_emcarga /
 * ModIndicadores::mostrar_resumen_emcarga_excel, reporte #4060).
 *
 * Excel detallado por aforo del mes (50 columnas): datos de la carta de porte,
 * hoja de ruta, vehículo, choferes, cliente, organismo/ministerio, toneladas,
 * kilómetros, origen/destino, producto, tiempos, combustible, ingreso, flete,
 * demoras, almacenaje, otros y observaciones.
 */
class OperacionesEmcargaExport implements FromQuery, WithHeadings, WithMapping
{
    private int $contador = 0;

    public function __construct(
        private int $mes,
        private int $ano,
        private array $entidades,
    ) {}

    public function headings(): array
    {
        return [
            'NRO', 'NRO CP', 'F-EMISION', 'F-CARGA', 'F-DESCARGA', 'F-PARTE', 'NRO HR',
            'CHAPA', 'VEHICULO', 'CHOFER 1', 'CHOFER 2', 'CLIENTE', 'OSDE', 'MINISTERIO',
            'TN REAL', 'KMS TOTAL', 'KMS CARGA', 'KMS VACIOS', 'CLASF. ORIGEN', 'ORIGEN',
            'MUNIC.ORIGEN', 'PROV.ORIGEN', 'CLASF.DESTINO', 'DESTINO', 'MUNIC.DESTINO',
            'PROV.DESTINO', 'PRODUCTO', 'TIEMPO TOTAL', 'TIEMPO CARGA', 'TIEMPO DESCARGA',
            'TIEMPO MOV', 'COMBUSTIBLE', 'INDICE', 'INGRESO TOTAL', 'FLETE', 'DEMORA CARGA',
            'DEMORA DESCARGA', '$ DEMORA CARGA', '$ DEMORA DESCARGA', '$ KMS VACIOS',
            '$ FALSO RECORRIDO', '$ ALMACENAJE', 'ERROR EN DOC', 'SIN DOC', 'LIMPIO/LIBRE',
            'PROT.CARGA', '$ OTROS', '$ CL', 'OBSERVACIONES',
        ];
    }

    public function query()
    {
        return DB::table('aforos as af')
            ->join('cartas_porte as cp', 'cp.id', '=', 'af.id_carta_porte')
            ->leftJoin('hojas_ruta as hr', 'hr.id', '=', 'cp.id_hoja_ruta')
            ->leftJoin('solicitudes_servicio as sol', 'sol.id', '=', 'cp.id_solicitud')
            ->leftJoin('tractivos as t', 't.id', '=', 'hr.id_tractivo')
            ->leftJoin('bolsa as ch1', 'ch1.id', '=', 'cp.id_chofer')
            ->leftJoin('bolsa as ch2', 'ch2.id', '=', 'cp.id_chofer2')
            ->leftJoin('clientes as cl', 'cl.id', '=', 'sol.id_cliente')
            ->leftJoin('catalogo_items as osde', 'osde.id', '=', 'cl.idosdes')
            ->leftJoin('catalogo_items as min', 'min.id', '=', 'cl.idorganismos')
            ->leftJoin('lugares as lo', 'lo.id', '=', 'sol.id_lugar_origen')
            ->leftJoin('lugares as ld', 'ld.id', '=', 'sol.id_lugar_destino')
            ->leftJoin('catalogo_items as pr', 'pr.id', '=', 'sol.id_producto')
            ->whereYear('af.fecha_parte', $this->ano)
            ->whereMonth('af.fecha_parte', $this->mes)
            ->whereNull('cp.deleted_at')
            ->where('cp.cancelada', false)
            ->whereIn('hr.id_entidad', $this->entidades)
            ->orderBy('af.fecha_parte')
            ->orderBy('cp.numero')
            ->select([
                'cp.numero as nrocp', 'cp.fecha_emision as femisioncp',
                'af.fecha_carga as fcarga', 'af.fecha_descarga as fdescarga', 'af.fecha_parte as fparte',
                'hr.numero as nrohr', 't.placa as chapa', 't.codigo as vehiculo',
                'ch1.nombre as ch1n', 'ch1.apellidos as ch1a',
                'ch2.nombre as ch2n', 'ch2.apellidos as ch2a',
                'cl.nombre as cliente', 'osde.nombre as osde', 'min.nombre as ministerio',
                'af.tn_real_total as tnreal', 'af.km_total_total as kmstot',
                'af.km_carga_total as kmcarga', 'af.km_vacio_total as kmvacio',
                'lo.nombre as origen', 'lo.municipio as munorigen', 'lo.provincia as provorigen',
                'ld.nombre as destino', 'ld.municipio as mundestino', 'ld.provincia as provdestino',
                'pr.nombre as producto',
                'af.tiempo_total', 'af.tiempo_carga', 'af.tiempo_descarga', 'af.tiempo_movimiento',
                'af.ingreso_mt', 'af.flete_mt', 'af.dem_carga', 'af.dem_descarga',
                'af.flete_dem_1', 'af.flete_dem_2', 'af.almacenaje_flete', 'af.otros_mt',
            ]);
    }

    public function map($r): array
    {
        $this->contador++;

        return [
            $this->contador, $r->nrocp, $r->femisioncp, $r->fcarga, $r->fdescarga, $r->fparte, $r->nrohr,
            $r->chapa, $r->vehiculo,
            trim(($r->ch1n ?? '').' '.($r->ch1a ?? '')), trim(($r->ch2n ?? '').' '.($r->ch2a ?? '')),
            $r->cliente, $r->osde, $r->ministerio,
            $r->tnreal, $r->kmstot, $r->kmcarga, $r->kmvacio,
            '', $r->origen, $r->munorigen, $r->provorigen,
            '', $r->destino, $r->mundestino, $r->provdestino,
            $r->producto,
            $r->tiempo_total, $r->tiempo_carga, $r->tiempo_descarga, $r->tiempo_movimiento,
            '', '', $r->ingreso_mt, $r->flete_mt, $r->dem_carga, $r->dem_descarga,
            $r->flete_dem_1, $r->flete_dem_2,
            '', '', $r->almacenaje_flete,
            '', '', '', '', $r->otros_mt, '', '',
        ];
    }
}
