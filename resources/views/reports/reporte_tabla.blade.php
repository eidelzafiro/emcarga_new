<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #111; }
        .titulo { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 2px; }
        .periodo { text-align: center; font-size: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid #888; padding: 2px 4px; text-align: left; }
        th { background: #e8e8e8; font-weight: bold; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; border-top: 2px solid #333; background: #f3f3f3; }
    </style>
</head>
<body>
    <div class="titulo">{{ $titulo }}</div>
    @if(!empty($periodo))
        <div class="periodo">{{ $periodo }}</div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach($columnas as $c)
                    <th class="{{ !empty($c['num']) ? 'num' : '' }}">{{ $c['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($filas as $f)
                <tr>
                    @foreach($columnas as $c)
                        <td class="{{ !empty($c['num']) ? 'num' : '' }}">{{ $f[$c['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columnas) }}" style="text-align:center">Sin datos</td></tr>
            @endforelse
        </tbody>
        @if(!empty($totales))
            <tfoot>
                <tr>
                    @foreach($columnas as $c)
                        <td class="{{ !empty($c['num']) ? 'num' : '' }}">{{ $totales[$c['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
