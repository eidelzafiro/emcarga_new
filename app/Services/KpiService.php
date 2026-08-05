<?php

namespace App\Services;

use App\Models\Arrastre;
use App\Models\Bateria;
use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\Entidad;
use App\Models\Tractivo;
use App\Models\User;
use App\Services\NotificarDocumentosChofer;

class KpiService
{
    public function calcular(?int $entidadId = null): array
    {
        return $this->paraRol('default', $entidadId);
    }

    public function paraRol(string $rol, ?int $entidadId = null): array
    {
        return match ($rol) {
            'SUPERADMIN' => $this->kpisSuperadmin($entidadId),
            'TECNICA' => $this->kpisTecnica($entidadId),
            'COMERCIAL' => $this->kpisComercial($entidadId),
            'CONTABILIDAD' => $this->kpisContabilidad($entidadId),
            'RECHUM' => $this->kpisRechum($entidadId),
            'OPERATIVOS' => $this->kpisOperativos($entidadId),
            'CONFIGURACIONES' => $this->kpisConfiguraciones($entidadId),
            default => $this->kpisDefault($entidadId),
        };
    }

    private function scopeEntidad($query, ?int $entidadId): void
    {
        if ($entidadId && $entidadId > 0) {
            $query->where('id_entidad', $entidadId);
        }
    }

    private function kpisSuperadmin(?int $entidadId): array
    {
        $usuariosQuery = User::query();
        $this->scopeEntidad($usuariosQuery, $entidadId);
        $usuarios = $usuariosQuery->count();

        return [
            [
                'label' => 'Usuarios',
                'valor' => (string) $usuarios,
                'subtexto' => $entidadId ? 'En la entidad activa' : 'Registrados en el sistema',
                'icono' => 'pi pi-users',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Entidades',
                'valor' => $entidadId ? '1' : (string) Entidad::count(),
                'subtexto' => $entidadId ? 'Entidad activa' : 'Organizaciones activas',
                'icono' => 'pi pi-building',
                'color' => 'bg-indigo-500',
            ],
            [
                'label' => 'Vehículos',
                'valor' => (string) Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))->count(),
                'subtexto' => 'Total de tractivos registrados',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Personal',
                'valor' => (string) Bolsa::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))->count(),
                'subtexto' => 'Trabajadores en plantilla',
                'icono' => 'pi pi-id-card',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisTecnica(?int $entidadId): array
    {
        $vehiculosActivos = Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->where('estado', 'activo')->count();
        $vehiculosTotales = Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))->count();

        $arrastresQuery = Arrastre::query();
        $this->scopeEntidad($arrastresQuery, $entidadId);

        $bateriasQuery = Bateria::query();
        $this->scopeEntidad($bateriasQuery, $entidadId);

        return [
            [
                'label' => 'Vehículos activos',
                'valor' => $vehiculosTotales > 0 ? "{$vehiculosActivos}/{$vehiculosTotales}" : '—',
                'subtexto' => 'En operación actualmente',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Arrastres',
                'valor' => (string) $arrastresQuery->count(),
                'subtexto' => 'Total de arrastres registrados',
                'icono' => 'pi pi-link',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Baterías',
                'valor' => (string) $bateriasQuery->count(),
                'subtexto' => 'Baterías en inventario',
                'icono' => 'pi pi-bolt',
                'color' => 'bg-orange-500',
            ],
            [
                'label' => 'Conductores asignados',
                'valor' => '—',
                'subtexto' => 'Operadores en ruta',
                'icono' => 'pi pi-user',
                'color' => 'bg-blue-500',
            ],
        ];
    }

    private function kpisComercial(?int $entidadId): array
    {
        return [
            [
                'label' => 'Facturas del mes',
                'valor' => '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Emitidas este período',
                'icono' => 'pi pi-file',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Ingresos del mes',
                'valor' => '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Facturación acumulada',
                'icono' => 'pi pi-dollar',
                'color' => 'bg-cyan-500',
            ],
            [
                'label' => 'Aforos pendientes',
                'valor' => '—',
                'subtexto' => 'Operaciones en curso',
                'icono' => 'pi pi-shopping-cart',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Clientes activos',
                'valor' => '—',
                'subtexto' => 'Con operaciones recientes',
                'icono' => 'pi pi-building',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisContabilidad(?int $entidadId): array
    {
        return [
            [
                'label' => 'Ingresos del mes',
                'valor' => '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Total facturado',
                'icono' => 'pi pi-arrow-up',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Egresos del mes',
                'valor' => '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Gastos acumulados',
                'icono' => 'pi pi-arrow-down',
                'color' => 'bg-red-500',
            ],
            [
                'label' => 'Centros de costo',
                'valor' => '—',
                'subtexto' => 'Activos en sistema',
                'icono' => 'pi pi-chart-bar',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Balance',
                'valor' => '—',
                'subtexto' => 'Ingresos - Egresos',
                'icono' => 'pi pi-calculator',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisRechum(?int $entidadId): array
    {
        $bolsaQuery = Bolsa::with('cargo')->where('activo', true);
        $this->scopeEntidad($bolsaQuery, $entidadId);

        $trabajadores = $bolsaQuery->get();

        $servicio = app(NotificarDocumentosChofer::class);
        $choferes = $trabajadores->filter(fn ($b) => $servicio->esChofer($b))->values();
        $habilitados = $choferes->filter(fn ($b) => $servicio->habilitado($b))->count();

        $cargosQuery = Cargo::where('activo', true);
        $this->scopeEntidad($cargosQuery, $entidadId);

        return [
            [
                'label' => 'Plantilla',
                'valor' => (string) $trabajadores->count(),
                'subtexto' => 'Trabajadores activos',
                'icono' => 'pi pi-id-card',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Choferes',
                'valor' => (string) $choferes->count(),
                'subtexto' => 'Del total de trabajadores',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Habilitados',
                'valor' => "{$habilitados}/{$choferes->count()}",
                'subtexto' => 'Choferes con documentos vigentes',
                'icono' => 'pi pi-check-circle',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Cargos',
                'valor' => (string) $cargosQuery->count(),
                'subtexto' => 'Definiciones de plaza',
                'icono' => 'pi pi-briefcase',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisOperativos(?int $entidadId): array
    {
        return [
            [
                'label' => 'Turnos hoy',
                'valor' => '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Programados para hoy',
                'icono' => 'pi pi-clock',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Vehículos en ruta',
                'valor' => '—',
                'subtexto' => 'Activos esta jornada',
                'icono' => 'pi pi-compass',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Combustible hoy',
                'valor' => '—',
                'subtexto' => 'Litros despachados',
                'icono' => 'pi pi-fuel',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Incidencias',
                'valor' => '—',
                'subtexto' => 'Reportadas en el día',
                'icono' => 'pi pi-exclamation-triangle',
                'color' => 'bg-red-500',
            ],
        ];
    }

    private function kpisConfiguraciones(?int $entidadId): array
    {
        $usuariosQuery = User::query();
        $this->scopeEntidad($usuariosQuery, $entidadId);

        return [
            [
                'label' => 'Usuarios',
                'valor' => (string) $usuariosQuery->count(),
                'subtexto' => $entidadId ? 'En la entidad activa' : 'Registrados en el sistema',
                'icono' => 'pi pi-users',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Entidades',
                'valor' => $entidadId ? '1' : (string) Entidad::count(),
                'subtexto' => $entidadId ? 'Entidad activa' : 'Organizaciones configuradas',
                'icono' => 'pi pi-building',
                'color' => 'bg-indigo-500',
            ],
            [
                'label' => 'Vehículos',
                'valor' => (string) Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))->count(),
                'subtexto' => 'Registrados en sistema',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Sistema',
                'valor' => 'Activo',
                'subtexto' => 'Todo en orden',
                'icono' => 'pi pi-check-circle',
                'color' => 'bg-green-500',
            ],
        ];
    }

    private function kpisDefault(?int $entidadId): array
    {
        $vehiculosActivos = Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->where('estado', 'activo')->count();
        $vehiculosTotales = Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))->count();

        $bolsaQuery = Bolsa::query();
        $this->scopeEntidad($bolsaQuery, $entidadId);

        return [
            [
                'label' => 'Vehículos activos',
                'valor' => $vehiculosTotales > 0 ? "{$vehiculosActivos}/{$vehiculosTotales}" : '—',
                'subtexto' => 'En operación actualmente',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Personal',
                'valor' => (string) $bolsaQuery->count(),
                'subtexto' => $entidadId ? 'Entidad activa' : 'Trabajadores registrados',
                'icono' => 'pi pi-users',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Ingresos del mes',
                'valor' => '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Facturación acumulada',
                'icono' => 'pi pi-dollar',
                'color' => 'bg-violet-500',
            ],
            [
                'label' => 'Órdenes pendientes',
                'valor' => '—',
                'subtexto' => 'Por atender',
                'icono' => 'pi pi-clipboard',
                'color' => 'bg-amber-500',
            ],
        ];
    }
}
