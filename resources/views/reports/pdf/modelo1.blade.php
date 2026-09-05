@extends('reports.layouts.pdf')

@section('title', $data['titulo'])

@section('content')
    <div style="text-align:center; margin-bottom:12px;">
        <h2 style="font-size:14px; margin:0;">{{ $data['titulo'] }}</h2>
        <p style="font-size:11px; color:#666; margin:2px 0 0 0;">Período: {{ $data['periodo'] }}</p>
    </div>

    @forelse($data['por_chofer'] as $chofer)
        <div style="margin-bottom:18px; page-break-inside:avoid;">
            <h3 style="font-size:11px; background:#1e40af; color:white; padding:4px 8px; margin:0 0 4px 0;">
                {{ $chofer['nombre'] }} — CI: {{ $chofer['carnet'] }} ({{ count($chofer['registros']) }} CP)
            </h3>

            <table style="width:100%; font-size:7px; border-collapse:collapse;">
                <thead>
                    <tr style="background:#dbeafe;">
                        <th style="padding:2px; text-align:left;">Fecha</th>
                        <th style="padding:2px; text-align:left;">Nro CP</th>
                        <th style="padding:2px; text-align:left;">Nro HR</th>
                        <th style="padding:2px; text-align:left;">Equipo</th>
                        <th style="padding:2px; text-align:left;">Cliente</th>
                        <th style="padding:2px; text-align:left;">Origen</th>
                        <th style="padding:2px; text-align:left;">Destino</th>
                        <th style="padding:2px; text-align:left;">Producto</th>
                        <th style="padding:2px; text-align:right;">KM</th>
                        <th style="padding:2px; text-align:right;">TN</th>
                        <th style="padding:2px; text-align:right;">Tiempo</th>
                        <th style="padding:2px; text-align:right;">Ingreso</th>
                        <th style="padding:2px; text-align:left;">Tasa</th>
                        <th style="padding:2px; text-align:right;">Salario</th>
                        <th style="padding:2px; text-align:center;">F</th>
                        <th style="padding:2px; text-align:center;">D</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chofer['registros'] as $reg)
                        <tr style="border-bottom:1px solid #e5e7eb;">
                            <td style="padding:2px;">{{ $reg['fecha'] }}</td>
                            <td style="padding:2px;">{{ $reg['nro_cp'] }}</td>
                            <td style="padding:2px;">{{ $reg['nro_hr'] }}</td>
                            <td style="padding:2px;">{{ $reg['equipo'] }}</td>
                            <td style="padding:2px;">{{ $reg['cliente'] ?? '' }}</td>
                            <td style="padding:2px;">{{ $reg['origen'] }}</td>
                            <td style="padding:2px;">{{ $reg['destino'] }}</td>
                            <td style="padding:2px;">{{ $reg['producto'] }}</td>
                            <td style="padding:2px; text-align:right;">{{ number_format($reg['km_total'], 1) }}</td>
                            <td style="padding:2px; text-align:right;">{{ number_format($reg['tn_real'], 1) }}</td>
                            <td style="padding:2px; text-align:right;">{{ number_format($reg['tiempo_total'], 2) }}</td>
                            <td style="padding:2px; text-align:right;">{{ number_format($reg['ingreso'], 2) }}</td>
                            <td style="padding:2px;">{{ $reg['tasa_nombre'] }} {{ $reg['tasa_valor'] ? '(' . $reg['tasa_valor'] . ')' : '' }}</td>
                            <td style="padding:2px; text-align:right; font-weight:bold;">{{ number_format($reg['salario'], 2) }}</td>
                            <td style="padding:2px; text-align:center;">{{ $reg['es_feriado'] ? 'X' : '' }}</td>
                            <td style="padding:2px; text-align:center;">{{ $reg['doble_chofer'] ? 'X' : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#dbeafe; font-weight:bold; border-top:2px solid #1e40af;">
                        <td style="padding:3px;" colspan="8">TOTAL {{ $chofer['nombre'] }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($chofer['totales']['km_total'], 1) }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($chofer['totales']['toneladas'], 1) }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($chofer['totales']['tiempo_total'], 2) }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($chofer['totales']['ingresos'], 2) }}</td>
                        <td style="padding:3px;"></td>
                        <td style="padding:3px; text-align:right;">{{ number_format($chofer['totales']['salario_cp'], 2) }}</td>
                        <td style="padding:3px;"></td>
                        <td style="padding:3px;"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @empty
        <p style="text-align:center; padding:20px; color:#999;">No hay datos para este período.</p>
    @endforelse
@endsection
