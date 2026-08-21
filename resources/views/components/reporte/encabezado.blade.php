@props(['titulo' => null, 'subtitulo' => null, 'empresa' => 'EMCARGA'])
<div class="reporte-encabezado" style="text-align:center; border-bottom:2px solid #1a365d; padding-bottom:8px; margin-bottom:14px;">
    <h1 style="font-size:14pt; color:#1a365d; margin:0;">{{ $titulo ?? $title ?? 'Reporte' }}</h1>
    <p style="font-size:8pt; color:#666; margin:2px 0;">{{ $empresa }} - Sistema de Gestión de Transporte</p>
    @if($subtitulo)
        <p style="font-size:9pt; color:#444; margin:2px 0;">{{ $subtitulo }}</p>
    @endif
    <p style="font-size:7pt; color:#999; margin:2px 0;">Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>
