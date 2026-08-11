<?php

namespace App\Services;

use App\Models\Bateria;
use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\CartaPorte;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\SolicitudesServicio;
use App\Models\Tractivo;
use App\Models\User;

class KpiService
{
    private function periodoMes(): array
    {
        return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
    }

    private function cartasDelMes(?int $entidadId = null)
    {
        return CartaPorte::where('cancelada', false)
            ->whereBetween('fecha_emision', $this->periodoMes())
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)));
    }

    private function fmtMoneda(mixed $valor): string
    {
        return '$'.number_format((float) $valor, 2, '.', ',');
    }
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

        $arrastresQuery = Tractivo::where('id_grupo', 8);
        $this->scopeEntidad($arrastresQuery, $entidadId);

        $bateriasQuery = Bateria::query();
        $this->scopeEntidad($bateriasQuery, $entidadId);

        $conductoresEnRuta = HojasRuta::whereNull('fecha_cierre')
            ->where('cancelada', false)
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->whereNotNull('id_chofer')
            ->distinct('id_chofer')
            ->count('id_chofer');

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
                'label' => 'Conductores en ruta',
                'valor' => $conductoresEnRuta > 0 ? (string) $conductoresEnRuta : '—',
                'subtexto' => 'Choferes en hojas abiertas',
                'icono' => 'pi pi-user',
                'color' => 'bg-blue-500',
            ],
        ];
    }

    private function kpisComercial(?int $entidadId): array
    {
        $cartasMes = $this->cartasDelMes($entidadId);
        $cartas = (clone $cartasMes)->count();
        $ingresos = (clone $cartasMes)->sum('ingreso_mt');
        $porRecepcionar = CartaPorte::where('cancelada', false)
            ->where('estado', 'emitida')
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            ->count();
        $clientesActivos = CartaPorte::where('cancelada', false)
            ->whereBetween('fecha_emision', $this->periodoMes())
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            ->distinct('id_cliente')
            ->count('id_cliente');

        return [
            [
                'label' => 'Cartas de porte del mes',
                'valor' => (string) $cartas,
                'subtexto' => $entidadId ? 'Entidad activa' : 'Emitidas este período',
                'icono' => 'pi pi-file',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Ingresos del mes',
                'valor' => $ingresos > 0 ? $this->fmtMoneda($ingresos) : '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Ingresos acumulados (MN)',
                'icono' => 'pi pi-dollar',
                'color' => 'bg-cyan-500',
            ],
            [
                'label' => 'CP por recepcionar',
                'valor' => (string) $porRecepcionar,
                'subtexto' => 'Cartas emitidas en curso',
                'icono' => 'pi pi-clock',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Clientes activos',
                'valor' => (string) $clientesActivos,
                'subtexto' => 'Con cartas de porte en el mes',
                'icono' => 'pi pi-building',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisContabilidad(?int $entidadId): array
    {
        $ingresos = (clone $this->cartasDelMes($entidadId))->sum('ingreso_mt');
        $emitidasMes = (clone $this->cartasDelMes($entidadId))->count();

        return [
            [
                'label' => 'Ingresos del mes',
                'valor' => $ingresos > 0 ? $this->fmtMoneda($ingresos) : '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Total facturado (MN)',
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
                'label' => 'Cartas emitidas',
                'valor' => (string) $emitidasMes,
                'subtexto' => 'Giros en el período',
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
        $hrAbiertas = HojasRuta::whereNull('fecha_cierre')
            ->where('cancelada', false)
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId));
        $abiertas = (clone $hrAbiertas)->count();
        $vehiculosEnRuta = (clone $hrAbiertas)->whereNotNull('id_tractivo')->distinct('id_tractivo')->count('id_tractivo');
        $cartasMes = $this->cartasDelMes($entidadId);
        $cartas = (clone $cartasMes)->count();
        $toneladas = (clone $cartasMes)->sum('ingreso_mt');

        return [
            [
                'label' => 'Hojas de ruta abiertas',
                'valor' => (string) $abiertas,
                'subtexto' => $entidadId ? 'Entidad activa' : 'Sin cerrar en el sistema',
                'icono' => 'pi pi-compass',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Vehículos en ruta',
                'valor' => (string) $vehiculosEnRuta,
                'subtexto' => 'Tractivos en hojas abiertas',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Toneladas del mes',
                'valor' => $toneladas > 0 ? number_format((float) $toneladas, 0, '.', '.').' t' : '—',
                'subtexto' => 'Ingresos de carga del período',
                'icono' => 'pi pi-weight',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Cartas del mes',
                'valor' => (string) $cartas,
                'subtexto' => 'Giros emitidos en el período',
                'icono' => 'pi pi-file',
                'color' => 'bg-violet-500',
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

        $ingresosMes = $this->cartasDelMes($entidadId)->sum('ingreso_mt');
        $pendientes = SolicitudesServicio::whereIn('estado', ['pendiente', 'en_proceso'])
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->count();
        $porRecepcionar = CartaPorte::where('cancelada', false)
            ->where('estado', 'emitida')
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            ->count();

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
                'valor' => $ingresosMes > 0 ? $this->fmtMoneda($ingresosMes) : '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Cartas de porte del período (MN)',
                'icono' => 'pi pi-dollar',
                'color' => 'bg-violet-500',
            ],
            [
                'label' => 'Solicitudes pendientes',
                'valor' => $pendientes > 0 ? (string) $pendientes : ($porRecepcionar > 0 ? (string) $porRecepcionar : '—'),
                'subtexto' => $pendientes > 0 ? 'Por atender' : 'CP por recepcionar',
                'icono' => 'pi pi-clipboard',
                'color' => 'bg-amber-500',
            ],
        ];
    }
}
