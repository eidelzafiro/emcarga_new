@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    <table>
        <thead>
            <tr>
                <th>No. Nómina</th>
                <th>CI</th>
                <th>Empleado</th>
                <th>Cargo</th>
                <th>Salario Base</th>
                <th>Imp. Salario Final</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salarios as $salario)
                <tr>
                    <td>{{ $salario->numero_nomina }}</td>
                    <td>{{ $salario->bolsa?->ci }}</td>
                    <td>{{ $salario->bolsa?->nombre }} {{ $salario->bolsa?->apellidos }}</td>
                    <td>{{ $salario->cargo?->nombre }}</td>
                    <td style="text-align:right">{{ number_format((float) $salario->salario_base, 2) }}</td>
                    <td style="text-align:right">{{ number_format((float) $salario->imp_salario_final, 2) }}</td>
                    <td>{{ ucfirst($salario->estado ?? '') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:20px">No hay registros</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:15px;font-size:8pt;color:#666;text-align:right">
        Total: {{ $salarios->count() }} registros
    </p>
@endsection
