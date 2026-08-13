@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    <table>
        <tr>
            <td style="border:none"><strong>No. Prefactura:</strong> {{ $prefactura->numero }}</td>
            <td style="border:none"><strong>Fecha:</strong> {{ optional($prefactura->fecha)?->format('d/m/Y') }}</td>
            <td style="border:none"><strong>Estado:</strong> {{ ucfirst($prefactura->estado) }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Cliente:</strong> {{ $prefactura->cliente?->nombre }}</td>
            <td style="border:none"><strong>Entidad:</strong> {{ $prefactura->entidad?->abreviatura }}</td>
            <td style="border:none"></td>
        </tr>
        @if($prefactura->notas)
            <tr>
                <td colspan="3" style="border:none"><strong>Notas:</strong> {{ $prefactura->notas }}</td>
            </tr>
        @endif
    </table>

    <h3 style="margin-top:20px;font-size:11pt;color:#1a365d">Cartas Porte</h3>
    <table>
        <thead>
            <tr>
                <th>CP</th>
                <th>Cliente</th>
                <th>Tractivo</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Producto</th>
                <th>Flete MN</th>
                <th>Demora</th>
                <th>Otros</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($prefactura->aforos as $aforo)
                <tr>
                    <td>{{ $aforo->cartaPorte?->numero }}</td>
                    <td>{{ $aforo->cartaPorte?->cliente?->nombre }}</td>
                    <td>{{ $aforo->cartaPorte?->tractivo?->codigo }}</td>
                    <td>{{ $aforo->cartaPorte?->lugarOrigen?->nombre }}</td>
                    <td>{{ $aforo->cartaPorte?->lugarDestino?->nombre }}</td>
                    <td>{{ $aforo->cartaPorte?->producto?->nombre }}</td>
                    <td style="text-align:right">{{ number_format((float) $aforo->flete_mt, 2) }}</td>
                    <td style="text-align:right">{{ number_format((float) $aforo->flete_demora, 2) }}</td>
                    <td style="text-align:right">{{ number_format((float) $aforo->otros_mt, 2) }}</td>
                    <td style="text-align:right">{{ number_format((float) $aforo->ingreso_mt, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;padding:20px">Sin cartas de porte asociadas</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;background:#f1f5f9">
                <td colspan="6" style="text-align:right">TOTALES</td>
                <td style="text-align:right">{{ number_format((float) $prefactura->flete_mt, 2) }}</td>
                <td style="text-align:right">{{ number_format((float) $prefactura->flete_demora, 2) }}</td>
                <td style="text-align:right">{{ number_format((float) $prefactura->otros_mt, 2) }}</td>
                <td style="text-align:right">{{ number_format((float) $prefactura->ingreso_mt, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <table style="margin-top:15px">
        <tr>
            <td style="border:none"><strong>Flete MN:</strong> ${{ number_format((float) $prefactura->flete_mt, 2) }}</td>
            <td style="border:none"><strong>Flete MLC:</strong> ${{ number_format((float) $prefactura->flete_mlc, 2) }}</td>
            <td style="border:none"><strong>Ingreso Total MN:</strong> ${{ number_format((float) $prefactura->ingreso_mt, 2) }}</td>
        </tr>
    </table>
@endsection
