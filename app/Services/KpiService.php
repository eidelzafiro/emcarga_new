<?php

namespace App\Services;

use App\Models\Tractivo;
use App\Models\User;

class KpiService
{
    public function calcular(): array
    {
        $vehiculosActivos = Tractivo::where('estado', 'activo')->count();
        $vehiculosTotales = Tractivo::count();
        $conductores = User::whereHas('roles', fn ($q) => $q->where('name', 'OPERATIVOS'))->count();
        $usuariosActivos = User::whereNull('deleted_at')->count();

        return [
            [
                'label' => 'Vehículos activos',
                'valor' => $vehiculosTotales > 0 ? "{$vehiculosActivos}/{$vehiculosTotales}" : '—',
                'subtexto' => 'En operación actualmente',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Conductores',
                'valor' => $conductores > 0 ? (string) $conductores : '—',
                'subtexto' => 'Registrados en el sistema',
                'icono' => 'pi pi-users',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Órdenes hoy',
                'valor' => '—',
                'subtexto' => 'Pendientes de atención',
                'icono' => 'pi pi-clipboard',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Ingresos del mes',
                'valor' => '—',
                'subtexto' => 'Facturación acumulada',
                'icono' => 'pi pi-dollar',
                'color' => 'bg-violet-500',
            ],
        ];
    }
}
