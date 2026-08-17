@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    @php
        $cp = $aforo->cartaPorte;
        $nombreChofer = $cp->chofer ? trim($cp->chofer->nombre.' '.$cp->chofer->apellidos) : '';
        $nombreChofer2 = $cp->chofer2 ? trim($cp->chofer2->nombre.' '.$cp->chofer2->apellidos) : '';
        $fleteMt = (float) $aforo->flete_mt;
        $demora = (float) $aforo->flete_demora;
        $otros = (float) $aforo->otros_mt;
        $ingreso = (float) $aforo->ingreso_mt;
    @endphp

    <table>
        <tr>
            <td style="border:none"><strong>CP:</strong> {{ $cp?->numero }}</td>
            <td style="border:none"><strong>Fecha Parte:</strong> {{ optional($aforo->fecha_parte)?->format('d/m/Y') }}</td>
            <td style="border:none"><strong>Entidad:</strong> {{ $cp?->hojaRuta?->entidad?->abreviatura }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Cliente:</strong> {{ $cp?->cliente?->nombre }}</td>
            <td style="border:none"><strong>HR:</strong> {{ $cp?->hojaRuta?->numero }}</td>
            <td style="border:none"><strong>Usuario:</strong> {{ $aforo->user?->name }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Tractivo:</strong> {{ $cp?->tractivo?->codigo }}</td>
            <td style="border:none"><strong>Chofer:</strong> {{ $nombreChofer }}</td>
            <td style="border:none"><strong>2do:</strong> {{ $nombreChofer2 }}</td>
        </tr>
    </table>

    <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Tarifas (Líneas)</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tipo</th>
                <th>Distancia</th>
                <th>Peso Cobrar</th>
                <th>Tarifa</th>
                <th>Flete MN</th>
                <th>Flete MLC</th>
            </tr>
        </thead>
        <tbody>
            @forelse($aforo->lineas as $linea)
                <tr>
                    <td>{{ $linea->posicion }}</td>
                    <td>{{ $linea->tipoCarga?->nombre ?? $linea->id_tipo_carga }}</td>
                    <td style="text-align:right">{{ $linea->distancia }}</td>
                    <td style="text-align:right">{{ $linea->peso_cobrar }}</td>
                    <td style="text-align:right">{{ $linea->tarifa_mt }}</td>
                    <td style="text-align:right">{{ $linea->flete_mt }}</td>
                    <td style="text-align:right">{{ $linea->flete_mlc }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:10px">Sin líneas de tarifa</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Demora, Almacenaje y Recargos</h3>
    <table>
        <tr>
            <td style="border:none"><strong>Demora carga:</strong> {{ $aforo->dem_carga }} h</td>
            <td style="border:none"><strong>Demora descarga:</strong> {{ $aforo->dem_descarga }} h</td>
            <td style="border:none"><strong>Demora total:</strong> {{ $aforo->dem_total }} h</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Almacenaje:</strong> {{ $aforo->almacenaje_peso }} t / {{ $aforo->almacenaje_horas }} h</td>
            <td style="border:none"><strong>Tarifa almacenaje:</strong> {{ $aforo->almacenaje_tarifa }}</td>
            <td style="border:none"><strong>Flete almacenaje:</strong> {{ $aforo->almacenaje_flete }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Recargos:</strong> {{ $aforo->recargo_1 }} / {{ $aforo->recargo_2 }} / {{ $aforo->recargo_3 }} / {{ $aforo->recargo_4 }} / {{ $aforo->recargo_5 }}</td>
            <td style="border:none"><strong>Otros total:</strong> {{ $otros }}</td>
            <td style="border:none"></td>
        </tr>
    </table>

    <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Salario</h3>
    <table>
        <tr>
            <td style="border:none"><strong>Tasa:</strong> {{ $aforo->tasa ?: ($aforo->tasa?->nombre ?? '—') }}</td>
            <td style="border:none"><strong>Salario:</strong> {{ $aforo->salario }}</td>
            <td style="border:none"><strong>Viajes:</strong> {{ $aforo->viajes }}</td>
        </tr>
    </table>

    <table style="margin-top:15px;background:#f1f5f9">
        <tr>
            <td style="border:none"><strong>FLETE MN:</strong> ${{ number_format($fleteMt, 2) }}</td>
            <td style="border:none"><strong>DEMORA:</strong> ${{ number_format($demora, 2) }}</td>
            <td style="border:none"><strong>OTROS:</strong> ${{ number_format($otros, 2) }}</td>
            <td style="border:none"><strong>INGRESO TOTAL:</strong> ${{ number_format($ingreso, 2) }}</td>
        </tr>
    </table>
@endsection
