@props(['cabeceras' => [], 'filas' => [], 'totales' => null, 'alinear' => []])
<table class="reporte-tabla" style="width:100%; border-collapse:collapse; margin-top:10px; font-size:9pt;">
    <thead>
        <tr>
            @foreach($cabeceras as $i => $cab)
                <th style="background:#1a365d; color:#fff; padding:5px 8px; text-align:{{ $alinear[$i] ?? 'left' }};">{{ $cab }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($filas as $fila)
            <tr>
                @foreach($fila as $i => $celda)
                    <td style="padding:4px 8px; border-bottom:1px solid #ddd; text-align:{{ $alinear[$i] ?? 'left' }};">{{ $celda }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td style="padding:8px; text-align:center; color:#999;" colspan="{{ count($cabeceras) ?: 1 }}">Sin datos</td></tr>
        @endforelse
        @if($totales)
            <tr>
                @foreach($totales as $i => $total)
                    <td style="padding:4px 8px; font-weight:bold; border-top:2px solid #1a365d; text-align:{{ $alinear[$i] ?? 'left' }};">{{ $total }}</td>
                @endforeach
            </tr>
        @endif
    </tbody>
</table>
