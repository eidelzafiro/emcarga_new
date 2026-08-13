@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    <table>
        <tr>
            <td style="border:none"><strong>No. Factura:</strong> {{ $factura->numero }}</td>
            <td style="border:none"><strong>Fecha Emisión:</strong> {{ optional($factura->fecha_emision)?->format('d/m/Y') }}</td>
            <td style="border:none"><strong>Estado:</strong> {{ ucfirst($factura->estado) }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Cliente:</strong> {{ $factura->cliente?->nombre }}</td>
            <td style="border:none"><strong>Entidad:</strong> {{ $factura->entidad?->abreviatura }}</td>
            <td style="border:none"><strong>Tipo Ingreso:</strong> {{ $factura->tipoIngreso?->nombre }}</td>
        </tr>
        @if($factura->notas)
            <tr>
                <td colspan="3" style="border:none"><strong>Notas:</strong> {{ $factura->notas }}</td>
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
            @forelse($factura->aforos as $aforo)
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
                <td style="text-align:right">{{ number_format((float) $factura->flete_mt, 2) }}</td>
                <td style="text-align:right">{{ number_format((float) $factura->flete_demora, 2) }}</td>
                <td style="text-align:right">{{ number_format((float) $factura->otros_mt, 2) }}</td>
                <td style="text-align:right">{{ number_format((float) $factura->ingreso_mt, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <table style="margin-top:15px">
        <tr>
            <td style="border:none"><strong>Flete MN:</strong> ${{ number_format((float) $factura->flete_mt, 2) }}</td>
            <td style="border:none"><strong>Flete MLC:</strong> ${{ number_format((float) $factura->flete_mlc, 2) }}</td>
            <td style="border:none"><strong>Ingreso Total MN:</strong> ${{ number_format((float) $factura->ingreso_mt, 2) }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Firma:</strong> {{ optional($factura->fecha_firma)?->format('d/m/Y') }}</td>
            <td style="border:none"><strong>Cobro MN:</strong> {{ optional($factura->fecha_cobro_mn)?->format('d/m/Y') }}</td>
            <td style="border:none"><strong>Conciliación:</strong> {{ optional($factura->fecha_conciliacion)?->format('d/m/Y') }}</td>
        </tr>
    </table>
@endsection
