@extends('reports.layouts.pdf')

@section('title', $title)

@section('content')
    <table>
        <thead>
            <tr>
                @foreach($campos as $campo)
                    <th>{{ $campo }}</th>
                @endforeach
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    @foreach($columnas as $col)
                        <td>{{ data_get($item, $col) }}</td>
                    @endforeach
                    <td>
                        <span class="badge {{ $item->activo ? 'badge-activo' : 'badge-inactivo' }}">
                            {{ $item->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($campos) + 1 }}" style="text-align:center;padding:20px">No hay registros</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:15px;font-size:8pt;color:#666;text-align:right">
        Total: {{ $items->count() }} registros
    </p>
@endsection
