<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title', $title ?? 'Reporte')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1a365d; padding-bottom: 10px; }
        .header h1 { font-size: 14pt; color: #1a365d; }
        .header p { font-size: 8pt; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #1a365d; color: white; padding: 6px 8px; text-align: left; font-size: 9pt; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; font-size: 9pt; }
        tr:nth-child(even) { background: #f8fafc; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 7pt; color: #999; border-top: 1px solid #ddd; padding-top: 5px; }
        .page-break { page-break-after: always; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 8pt; }
        .badge-activo { background: #dcfce7; color: #166534; }
        .badge-inactivo { background: #fee2e2; color: #991b1b; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="header">
        <h1>@yield('title', $title ?? 'Reporte')</h1>
        <p>EMCARGA - Sistema de Gestión de Transporte</p>
        <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @yield('content')

    <div class="footer">
        EMCARGA &copy; {{ date('Y') }} | Página {PAGE_NUM} de {PAGE_COUNT}
    </div>
</body>
</html>
