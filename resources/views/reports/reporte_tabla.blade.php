<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #111; }
        .header { display: flex; align-items: center; margin-bottom: 10px; border-bottom: 2px solid #1a365d; padding-bottom: 8px; }
        .header .logo { width: 50px; height: auto; margin-right: 15px; }
        .header .header-text { flex: 1; text-align: center; }
        .header .header-text h1 { font-size: 14px; color: #1a365d; margin: 0; }
        .header .header-text p { font-size: 8px; color: #666; margin: 2px 0 0 0; }
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
    <div class="header">
        <img src="{{ public_path('images/emcarga.png') }}" class="logo" alt="EMCARGA">
        <div class="header-text">
            <h1>EMPRESA CAMIONES EMCARGA</h1>
            <p>{{ $titulo }}</p>
        </div>
    </div>
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
