@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    @php
        $nombreChofer = $hoja->chofer ? trim($hoja->chofer->nombre.' '.$hoja->chofer->apellidos) : '';
        $nombreChofer2 = $hoja->chofer2 ? trim($hoja->chofer2->nombre.' '.$hoja->chofer2->apellidos) : '';
    @endphp

    <table>
        <tr>
            <td style="border:none"><strong>No. HR:</strong> {{ $hoja->numero }}</td>
            <td style="border:none"><strong>Entidad:</strong> {{ $hoja->entidad?->abreviatura }}</td>
            <td style="border:none"><strong>Estado:</strong> {{ $hoja->cancelada ? 'Cancelada' : ($hoja->fecha_cierre ? 'Cerrada' : 'Abierta') }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Fecha Emisión:</strong> {{ optional($hoja->fecha_emision)?->format('d/m/Y') }}</td>
            <td style="border:none"><strong>Hora:</strong> {{ $hoja->hora_emision }}</td>
            <td style="border:none"><strong>Fecha Cierre:</strong> {{ optional($hoja->fecha_cierre)?->format('d/m/Y') }}</td>
        </tr>
    </table>

    <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Equipo y Personal</h3>
    <table>
        <tr>
            <th>Tractivo</th>
            <th>Placa</th>
            <th>Arrastre</th>
            <th>Chofer</th>
            <th>2do Chofer</th>
        </tr>
        <tr>
            <td>{{ $hoja->tractivo?->codigo }}</td>
            <td>{{ $hoja->tractivo?->placa }}</td>
            <td>{{ $hoja->arrastre?->codigo }}</td>
            <td>{{ $nombreChofer }}</td>
            <td>{{ $nombreChofer2 }}</td>
        </tr>
        <tr>
            <td style="border:none"><small>{{ $hoja->tractivo?->marca }} {{ $hoja->tractivo?->modelo }}</small></td>
            <td style="border:none"></td>
            <td style="border:none"></td>
            <td style="border:none"><small>CI: {{ $hoja->chofer?->ci }}</small></td>
            <td style="border:none"><small>CI: {{ $hoja->chofer2?->ci }}</small></td>
        </tr>
    </table>

    <h3 style="margin-top:16px;font-size:11pt;color:#1a365d">Datos de Operación</h3>
    <table>
        <tr>
            <th>Parqueo</th>
            <th>Grupo</th>
            <th>Kms Disponibles</th>
            <th>Kms Adicionales</th>
            <th>Kms Totales</th>
        </tr>
        <tr>
            <td>{{ $hoja->parqueo?->nombre }}</td>
            <td>{{ $hoja->grupo?->nombre }}</td>
            <td style="text-align:right">{{ $hoja->kms_disponible }}</td>
            <td style="text-align:right">{{ $hoja->kms_disponibles_adicionales }}</td>
            <td style="text-align:right">{{ $hoja->kms_totales }}</td>
        </tr>
    </table>

    <table style="margin-top:10px">
        <tr>
            <td style="border:none"><strong>Combustible habilitado:</strong> {{ $hoja->combustible_habilitado }}</td>
            <td style="border:none"><strong>Consumido:</strong> {{ $hoja->combustible_consumido }}</td>
            <td style="border:none"><strong>Técnico:</strong> {{ $hoja->combustible_tecnico }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Índice HR:</strong> {{ $hoja->indice_hr }}</td>
            <td style="border:none"><strong>Días trabajados:</strong> {{ $hoja->dias_trabajados }}</td>
            <td style="border:none"><strong>Usuario:</strong> {{ $hoja->user?->name }}</td>
        </tr>
        <tr>
            <td style="border:none"><strong>Tiempo total:</strong> {{ $hoja->tiempo_total }}</td>
            <td style="border:none"><strong>T. movimiento:</strong> {{ $hoja->tiempo_mov }}</td>
            <td style="border:none"><strong>T. carga:</strong> {{ $hoja->tiempo_carga }}</td>
        </tr>
    </table>

    @if($hoja->notas)
        <table style="margin-top:15px">
            <tr>
                <td style="border:none"><strong>Notas:</strong> {{ $hoja->notas }}</td>
            </tr>
        </table>
    @endif
@endsection
