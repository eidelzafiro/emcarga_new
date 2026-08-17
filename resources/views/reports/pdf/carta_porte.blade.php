@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    <table>
        <tr>
            <td style="border:none"><strong>No. CP:</strong> {{ $carta->numero }}</td>
            <td style="border:none"><strong>Fecha Emisión:</strong> {{ optional($carta->fecha_emision)?->format('d/m/Y') }}</td>
            <td style="border:none"><strong>Fecha Parte:</strong> {{ optional($carta->fecha_parte)?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Cliente:</strong> {{ $carta->cliente?->nombre }}</td>
            <td style="border:none"><strong>Hoja de Ruta:</strong> {{ $carta->hojaRuta?->numero }}</td>
            <td style="border:none"><strong>Entidad:</strong> {{ $carta->hojaRuta?->entidad?->abreviatura }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Solicitud:</strong> {{ $carta->solicitud?->numero }}</td>
            <td style="border:none"><strong>Estado:</strong> {{ ucfirst($carta->estado) }}</td>
            <td style="border:none"><strong>Conduce:</strong> {{ $carta->conduce }}</td>
        </tr>
    </table>

    <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Datos de la Transportación</h3>
    <table>
        <tr>
            <th>Origen</th>
            <th>Destino</th>
            <th>Distancia (km)</th>
            <th>Peso 1 (t)</th>
            <th>Peso 2 (t)</th>
            <th>Total (t)</th>
        </tr>
        <tr>
            <td>{{ $carta->lugarOrigen?->nombre }}</td>
            <td>{{ $carta->lugarDestino?->nombre }}</td>
            <td style="text-align:right">{{ $carta->distancia }}</td>
            <td style="text-align:right">{{ number_format((float) $carta->peso1, 2) }}</td>
            <td style="text-align:right">{{ number_format((float) $carta->peso2, 2) }}</td>
            <td style="text-align:right">{{ number_format((float) $carta->toneladas, 2) }}</td>
        </tr>
    </table>

    <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Productos / Tipos de Carga</h3>
    <table>
        <tr>
            <th>Carga 1</th>
            <th>Tipo 1</th>
            <th>Carga 2</th>
            <th>Tipo 2</th>
        </tr>
        <tr>
            <td>{{ $carta->producto?->nombre ?? $carta->solicitud?->producto?->nombre }}</td>
            <td>{{ $carta->tipoCarga?->nombre ?? $carta->solicitud?->tipoCarga?->nombre }}</td>
            <td>{{ $carta->solicitud?->producto2?->nombre }}</td>
            <td>{{ $carta->solicitud?->tipoCarga2?->nombre }}</td>
        </tr>
    </table>

    <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Equipo y Personal</h3>
    <table>
        <tr>
            <th>Tractivo</th>
            <th>Arrastre</th>
            <th>Chofer</th>
            <th>2do Chofer</th>
        </tr>
        @php
            $chofer = $carta->chofer ?? $carta->hojaRuta?->chofer;
            $chofer2 = $carta->chofer2 ?? $carta->hojaRuta?->chofer2;
            $nombreChofer = $chofer ? trim($chofer->nombre.' '.$chofer->apellidos) : '';
            $nombreChofer2 = $chofer2 ? trim($chofer2->nombre.' '.$chofer2->apellidos) : '';
        @endphp
        <tr>
            <td>{{ $carta->tractivo?->codigo ?? $carta->hojaRuta?->tractivo?->codigo }}</td>
            <td>{{ $carta->arrastre?->codigo ?? $carta->hojaRuta?->arrastre?->codigo }}</td>
            <td>{{ $nombreChofer }}</td>
            <td>{{ $nombreChofer2 }}</td>
        </tr>
    </table>

    @if($carta->notas)
        <table style="margin-top:15px">
            <tr>
                <td style="border:none"><strong>Notas:</strong> {{ $carta->notas }}</td>
            </tr>
        </table>
    @endif
@endsection
