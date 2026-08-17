@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    <p><strong>Periodo:</strong> {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}</p>

    @if($registros->isEmpty())
        <p style="margin-top:16px">No hay registros de control de lubricantes en el periodo.</p>
    @else
        <table>
            <tr>
                <th>Fecha</th>
                <th>Tractivo</th>
                <th>Operación</th>
                <th>Motor</th>
                <th>Transm.</th>
                <th>Direc.</th>
                <th>Hidr.</th>
                <th>Freno</th>
                <th>Agua</th>
                <th>G.Rollete</th>
                <th>G.Copillas</th>
            </tr>
            @foreach($registros as $r)
                <tr>
                    <td>{{ optional($r->fecha_cambio)->format('d/m/Y') }}</td>
                    <td>{{ $r->tractivo?->placa }} {{ $r->tractivo?->descripcion }}</td>
                    <td>{{ $r->tipo_operacion }}</td>
                    <td style="text-align:right">{{ $r->litros_motor }}</td>
                    <td style="text-align:right">{{ $r->litros_transmision }}</td>
                    <td style="text-align:right">{{ $r->litros_direccion }}</td>
                    <td style="text-align:right">{{ $r->litros_hidraulico }}</td>
                    <td style="text-align:right">{{ $r->liquido_freno }}</td>
                    <td style="text-align:right">{{ $r->agua_refrigerada }}</td>
                    <td style="text-align:right">{{ $r->grasa_rollete }}</td>
                    <td style="text-align:right">{{ $r->grasa_copillas }}</td>
                </tr>
            @endforeach
        </table>
    @endif
@endsection
