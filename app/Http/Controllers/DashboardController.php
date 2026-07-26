<?php

namespace App\Http\Controllers;

use App\Services\KpiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected KpiService $kpiService,
    ) {}

    public function index(Request $request)
    {
        $kpis = $this->kpiService->calcular();

        $movimientos = [
            ['id' => 1001, 'tipo' => 'Venta', 'icono' => 'pi pi-shopping-cart', 'color' => '#059669', 'descripcion' => 'Factura #F-2025-001', 'monto' => 12500, 'estado' => 'Completado', 'claseBadge' => 'status-badge-completado', 'fecha' => '25 jul 2026'],
            ['id' => 1002, 'tipo' => 'Combustible', 'icono' => 'pi pi-fuel', 'color' => '#d97706', 'descripcion' => 'Carga de combustible', 'monto' => -3450, 'estado' => 'Pendiente', 'claseBadge' => 'status-badge-pendiente', 'fecha' => '25 jul 2026'],
            ['id' => 1003, 'tipo' => 'Mantenimiento', 'icono' => 'pi pi-wrench', 'color' => '#6366f1', 'descripcion' => 'Servicio taller #T-089', 'monto' => -8900, 'estado' => 'En proceso', 'claseBadge' => 'status-badge-proceso', 'fecha' => '24 jul 2026'],
            ['id' => 1004, 'tipo' => 'Nómina', 'icono' => 'pi pi-money-bill', 'color' => '#ec4899', 'descripcion' => 'Pago de salarios', 'monto' => -45200, 'estado' => 'Completado', 'claseBadge' => 'status-badge-completado', 'fecha' => '23 jul 2026'],
            ['id' => 1005, 'tipo' => 'Venta', 'icono' => 'pi pi-shopping-cart', 'color' => '#059669', 'descripcion' => 'Factura #F-2025-002', 'monto' => 8900, 'estado' => 'Completado', 'claseBadge' => 'status-badge-completado', 'fecha' => '22 jul 2026'],
        ];

        $actividadReciente = [
            ['titulo' => 'Nuevo tractivo registrado', 'descripcion' => 'Se añadió el vehículo KIA-1234', 'icono' => 'pi pi-truck', 'color' => 'bg-blue-500', 'hace' => 'Hace 10 min'],
            ['titulo' => 'Factura generada', 'descripcion' => 'Factura #F-2025-001 por $12,500', 'icono' => 'pi pi-file', 'color' => 'bg-emerald-500', 'hace' => 'Hace 1 hora'],
            ['titulo' => 'Carga de combustible', 'descripcion' => '500 L registrados en servicentro', 'icono' => 'pi pi-fuel', 'color' => 'bg-amber-500', 'hace' => 'Hace 2 horas'],
            ['titulo' => 'Usuario creado', 'descripcion' => 'Nuevo usuario: María García', 'icono' => 'pi pi-user-plus', 'color' => 'bg-violet-500', 'hace' => 'Hace 3 horas'],
        ];

        return Inertia::render('Dashboard', [
            'title' => 'Dashboard',
            'user' => $request->user(),
            'kpis' => $kpis,
            'movimientos' => $movimientos,
            'actividadReciente' => $actividadReciente,
        ]);
    }

    public function kpis()
    {
        return response()->json([
            'kpis' => $this->kpiService->calcular(),
        ]);
    }
}
