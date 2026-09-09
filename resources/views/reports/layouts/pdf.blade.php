<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title', $title ?? 'Reporte')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #333; }
        .header { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #1a365d; padding-bottom: 10px; }
        .header .logo { width: 50px; height: auto; margin-right: 15px; }
        .header .header-text { flex: 1; text-align: center; }
        .header .header-text h1 { font-size: 14pt; color: #1a365d; margin: 0; }
        .header .header-text p { font-size: 8pt; color: #666; margin: 2px 0 0 0; }
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
        <img src="{{ public_path('images/emcarga.png') }}" class="logo" alt="EMCARGA">
        <div class="header-text">
            <h1>EMPRESA CAMIONES EMCARGA</h1>
            <p>@yield('title', $title ?? 'Reporte')</p>
            <p>{{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    @yield('content')

    <div class="footer">
        EMCARGA &copy; {{ date('Y') }} | Página {PAGE_NUM} de {PAGE_COUNT}
    </div>
</body>
</html>
