@extends('reports.layouts.pdf')

@section('title', $data['titulo'])

@section('content')
    <div style="text-align:center; margin-bottom:15px;">
        <h2 style="font-size:14px; margin:0;">{{ $data['titulo'] }}</h2>
        <p style="font-size:11px; color:#666; margin:2px 0 0 0;">Período: {{ $data['periodo'] }}</p>
    </div>

    @if($tipo === 'choferes')
        {{-- PRENOMINA CHOFERES --}}
        <table style="width:100%; font-size:8px; border-collapse:collapse;">
            <thead>
                <tr style="background:#2563eb; color:white;">
                    <th style="padding:4px; text-align:left;">Chofer</th>
                    <th style="padding:4px; text-align:center;">CI</th>
                    <th style="padding:4px; text-align:right;">Hrs Reg</th>
                    <th style="padding:4px; text-align:right;">Hrs Irr</th>
                    <th style="padding:4px; text-align:right;">Ingresos</th>
                    <th style="padding:4px; text-align:right;">Salario CP</th>
                    <th style="padding:4px; text-align:right;">CLA</th>
                    <th style="padding:4px; text-align:right;">Noct.1</th>
                    <th style="padding:4px; text-align:right;">Noct.2</th>
                    <th style="padding:4px; text-align:right;">Feriados</th>
                    <th style="padding:4px; text-align:right;">TN</th>
                    <th style="padding:4px; text-align:right;">KM</th>
                    <th style="padding:4px; text-align:right; background:#1e40af;">SALARIO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['registros'] as $reg)
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:3px;">{{ $reg['nombre_completo'] }}</td>
                        <td style="padding:3px; text-align:center;">{{ $reg['carnet'] ?? '' }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($reg['regular'] ?? 0, 2) }}</td>
                        <td style="padding:3px; text-align:right;">{{ ($reg['irregular'] ?? 0) > 0 ? number_format($reg['irregular'], 2) : '' }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($reg['ingresos'] ?? 0, 2) }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($reg['salario_cp'] ?? 0, 2) }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($reg['imp_cla'] ?? 0, 2) }}</td>
                        <td style="padding:3px; text-align:right;">{{ ($reg['imp_nocturnidad_1'] ?? 0) > 0 ? number_format($reg['imp_nocturnidad_1'], 2) : '' }}</td>
                        <td style="padding:3px; text-align:right;">{{ ($reg['imp_nocturnidad_2'] ?? 0) > 0 ? number_format($reg['imp_nocturnidad_2'], 2) : '' }}</td>
                        <td style="padding:3px; text-align:right;">{{ ($reg['imp_feriados'] ?? 0) > 0 ? number_format($reg['imp_feriados'], 2) : '' }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($reg['toneladas'] ?? 0, 1) }}</td>
                        <td style="padding:3px; text-align:right;">{{ number_format($reg['km_total'] ?? 0, 1) }}</td>
                        <td style="padding:3px; text-align:right; font-weight:bold;">{{ number_format($reg['salario_final'] ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="13" style="text-align:center; padding:15px;">No hay registros</td></tr>
                @endforelse
            </tbody>
            @if(count($data['registros']) > 0)
            <tfoot>
                <tr style="background:#dbeafe; font-weight:bold; border-top:2px solid #2563eb;">
                    <td style="padding:4px;" colspan="2">TOTALES ({{ count($data['registros']) }} choferes)</td>
                    <td style="padding:4px; text-align:right;">{{ number_format($data['totales']['regular'] ?? 0, 2) }}</td>
                    <td style="padding:4px; text-align:right;"></td>
                    <td style="padding:4px; text-align:right;">{{ number_format($data['totales']['ingresos'] ?? 0, 2) }}</td>
                    <td style="padding:4px; text-align:right;">{{ number_format($data['totales']['salario_cp'] ?? 0, 2) }}</td>
                    <td style="padding:4px; text-align:right;">{{ number_format($data['totales']['imp_cla'] ?? 0, 2) }}</td>
                    <td style="padding:4px; text-align:right;">{{ number_format($data['totales']['imp_nocturnidad_1'] ?? 0, 2) }}</td>
                    <td style="padding:4px; text-align:right;">{{ number_format($data['totales']['imp_nocturnidad_2'] ?? 0, 2) }}</td>
                    <td style="padding:4px; text-align:right;">{{ number_format($data['totales']['imp_feriados'] ?? 0, 2) }}</td>
                    <td style="padding:4px; text-align:right;">{{ number_format($data['totales']['toneladas'] ?? 0, 1) }}</td>
                    <td style="padding:4px; text-align:right;">{{ number_format($data['totales']['km_total'] ?? 0, 1) }}</td>
                    <td style="padding:4px; text-align:right; font-weight:bold;">{{ number_format($data['totales']['salario_final'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>

    @else
        {{-- PRENOMINA ADMINISTRATIVO --}}
        @foreach($data['por_area'] ?? [] as $area => $registros)
            <div style="margin-bottom:15px; page-break-inside:avoid;">
                <h3 style="font-size:11px; background:#059669; color:white; padding:4px 8px; margin:0 0 5px 0;">
                    {{ $area }} ({{ count($registros) }})
                </h3>
                <table style="width:100%; font-size:8px; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#d1fae5;">
                            <th style="padding:3px; text-align:left;">Empleado</th>
                            <th style="padding:3px; text-align:center;">CI</th>
                            <th style="padding:3px; text-align:left;">Cargo</th>
                            <th style="padding:3px; text-align:right;">Hrs Reg</th>
                            <th style="padding:3px; text-align:right;">Hrs Irr</th>
                            <th style="padding:3px; text-align:right;">H.Extra</th>
                            <th style="padding:3px; text-align:right;">Días Taller</th>
                            <th style="padding:3px; text-align:right;">Feriados</th>
                            <th style="padding:3px; text-align:right;">Tarifa</th>
                            <th style="padding:3px; text-align:right;">Tiempo Mes</th>
                            <th style="padding:3px; text-align:right;">Salario Base</th>
                            <th style="padding:3px; text-align:right; background:#047857; color:white;">SALARIO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registros as $reg)
                            <tr style="border-bottom:1px solid #e5e7eb;">
                                <td style="padding:3px;">{{ $reg['nombre_completo'] }}</td>
                                <td style="padding:3px; text-align:center;">{{ $reg['carnet'] ?? '' }}</td>
                                <td style="padding:3px;">{{ $reg['cargo'] ?? '' }}</td>
                                <td style="padding:3px; text-align:right;">{{ number_format($reg['regular'] ?? 0, 2) }}</td>
                                <td style="padding:3px; text-align:right;">{{ ($reg['irregular'] ?? 0) > 0 ? number_format($reg['irregular'], 2) : '' }}</td>
                                <td style="padding:3px; text-align:right;">{{ ($reg['h_extra'] ?? 0) > 0 ? number_format($reg['h_extra'], 2) : '' }}</td>
                                <td style="padding:3px; text-align:right;">{{ ($reg['dias_taller'] ?? 0) > 0 ? number_format($reg['dias_taller'], 0) : '' }}</td>
                                <td style="padding:3px; text-align:right;">{{ ($reg['feriados_editados'] ?? 0) > 0 ? number_format($reg['feriados_editados'], 2) : '' }}</td>
                                <td style="padding:3px; text-align:right;">{{ number_format($reg['tarifa'] ?? 0, 2) }}</td>
                                <td style="padding:3px; text-align:right;">{{ number_format($reg['tiempo_mes'] ?? 0, 2) }}</td>
                                <td style="padding:3px; text-align:right;">{{ number_format($reg['salario_base'] ?? 0, 2) }}</td>
                                <td style="padding:3px; text-align:right; font-weight:bold;">{{ number_format($reg['salario_final'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:#d1fae5; font-weight:bold;">
                            <td style="padding:3px;" colspan="3">Subtotal {{ $area }}</td>
                            <td style="padding:3px; text-align:right;">{{ number_format(collect($registros)->sum('regular'), 2) }}</td>
                            <td style="padding:3px; text-align:right;"></td>
                            <td style="padding:3px; text-align:right;">{{ number_format(collect($registros)->sum('h_extra'), 2) }}</td>
                            <td style="padding:3px; text-align:right;"></td>
                            <td style="padding:3px; text-align:right;"></td>
                            <td style="padding:3px; text-align:right;"></td>
                            <td style="padding:3px; text-align:right;"></td>
                            <td style="padding:3px; text-align:right;"></td>
                            <td style="padding:3px; text-align:right;">{{ number_format(collect($registros)->sum('salario_final'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach

        {{-- TOTALES GENERALES --}}
        <div style="margin-top:10px; padding:6px; background:#ecfdf5; border:1px solid #059669; font-size:10px;">
            <strong>TOTAL GENERAL ({{ count($data['registros']) }} empleados):</strong>
            <span style="float:right; font-weight:bold;">{{ number_format($data['totales']['salario_final'] ?? 0, 2) }}</span>
        </div>
    @endif
@endsection
