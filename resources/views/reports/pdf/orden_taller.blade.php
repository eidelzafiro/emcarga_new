@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    <table>
        <tr>
            <td style="border:none"><strong>N°:</strong> {{ $orden->numero }}</td>
            <td style="border:none"><strong>Estado:</strong> {{ $orden->estado }}</td>
            <td style="border:none"><strong>Clasificación:</strong> {{ $orden->clasificacion?->nombre }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Tractivo:</strong> {{ $orden->tractivo?->placa }} {{ $orden->tractivo?->descripcion }}</td>
            <td style="border:none"><strong>Motivo entrada:</strong> {{ $orden->motivoEntrada?->nombre }}</td>
            <td style="border:none"><strong>Tipo mtto:</strong> {{ $orden->tipoMantenimiento?->nombre }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Entrada:</strong> {{ optional($orden->fecha_ingreso)->format('d/m/Y') }} {{ $orden->hora_ingreso }}</td>
            <td style="border:none"><strong>Salida:</strong> {{ optional($orden->fecha_salida)->format('d/m/Y') }} {{ $orden->hora_salida }}</td>
            <td style="border:none"><strong>Km:</strong> {{ $orden->kilometraje }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Combustible taller:</strong> {{ $orden->combtaller }}</td>
            <td style="border:none"><strong>Tiempo total:</strong> {{ $orden->ottiempo }}</td>
            <td style="border:none"><strong>Paralizado:</strong> {{ $orden->ot_paralizado ?: '-' }}</td>
        </tr>
    </table>

    @if($orden->notas)
        <table style="margin-top:10px">
            <tr><td style="border:none"><strong>Notas:</strong> {{ $orden->notas }}</td></tr>
        </table>
    @endif

    @if($orden->operaciones->isNotEmpty())
        <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Operaciones</h3>
        <table>
            <tr>
                <th>Operación</th>
                <th>Inicio</th>
                <th>H. Inicio</th>
                <th>Final</th>
                <th>H. Final</th>
                <th>Tiempo</th>
            </tr>
            @foreach($orden->operaciones as $op)
                <tr>
                    <td>{{ $op->tipoOperacion?->nombre ?? $op->id_tipo_operacion }}</td>
                    <td>{{ optional($op->fecha_inicio)->format('d/m/Y') }}</td>
                    <td>{{ $op->hora_inicio }}</td>
                    <td>{{ optional($op->fecha_final)->format('d/m/Y') }}</td>
                    <td>{{ $op->hora_final }}</td>
                    <td style="text-align:right">{{ $op->tiempo }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($orden->gastos->isNotEmpty())
        <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Piezas / Recursos de almacén</h3>
        <table>
            <tr>
                <th>Vale</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Cant.</th>
                <th>Motivo</th>
            </tr>
            @foreach($orden->gastos as $g)
                <tr>
                    <td>{{ $g->vale }}</td>
                    <td>{{ $g->codigo_pieza }}</td>
                    <td>{{ $g->nombre }}</td>
                    <td style="text-align:right">{{ $g->cantidad }}</td>
                    <td>{{ $g->motivo }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($orden->movimientos->isNotEmpty())
        <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Movimientos en taller</h3>
        <table>
            <tr>
                <th>Inicio</th>
                <th>H. Inicio</th>
                <th>Final</th>
                <th>H. Final</th>
                <th>Tiempo</th>
                <th>Observaciones</th>
            </tr>
            @foreach($orden->movimientos as $m)
                <tr>
                    <td>{{ optional($m->fecha_inicio)->format('d/m/Y') }}</td>
                    <td>{{ $m->hora_inicio }}</td>
                    <td>{{ optional($m->fecha_final)->format('d/m/Y') }}</td>
                    <td>{{ $m->hora_final }}</td>
                    <td style="text-align:right">{{ $m->tiempo }}</td>
                    <td>{{ $m->observaciones }}</td>
                </tr>
            @endforeach
        </table>
    @endif
@endsection
