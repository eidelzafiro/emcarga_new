@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    <p><strong>Plan:</strong> {{ $tipo === 1 ? 'Por vencer (aviso)' : 'Vencidos' }} | Generado: {{ now()->format('d/m/Y') }}</p>

    @if($neumaticos->isEmpty())
        <p style="margin-top:16px">No hay neumáticos en este plan.</p>
    @else
        <table>
            <tr>
                <th>Folio</th>
                <th>Marca</th>
                <th>Medida</th>
                <th>Tractivo</th>
                <th>Posición</th>
                <th>Km acumulados</th>
                <th>Km promedio/mes</th>
                <th>Plan retiro</th>
                <th>Plan aviso</th>
                <th>Estado</th>
            </tr>
            @foreach($neumaticos as $n)
                <tr>
                    <td>{{ $n->folio }}</td>
                    <td>{{ $n->marca }}</td>
                    <td>{{ $n->medida }}</td>
                    <td>{{ $n->tractivo?->placa }} {{ $n->tractivo?->descripcion }}</td>
                    <td>{{ $n->posicion?->nombre }}</td>
                    <td style="text-align:right">{{ number_format((float)$n->kilometraje, 0) }}</td>
                    <td style="text-align:right">{{ number_format((float)$n->kms_promedio, 1) }}</td>
                    <td>{{ optional($n->fecha_plan_retiro)->format('d/m/Y') }}</td>
                    <td>{{ optional($n->fecha_plan_aviso)->format('d/m/Y') }}</td>
                    <td><span class="badge badge-activo">{{ $n->estado }}</span></td>
                </tr>
            @endforeach
        </table>
    @endif
@endsection
