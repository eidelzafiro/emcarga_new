@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Panel de control</h1>
            <p class="text-gray-500 text-sm mt-1">
                Bienvenido, {{ auth()->user()->name ?? 'Usuario' }} — {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach ($kpis as $kpi)
            <div class="kpi-card">
                <div class="flex items-start justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $kpi['label'] }}</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1.5">{{ $kpi['valor'] }}</p>
                        @if ($kpi['subtexto'] ?? false)
                            <p class="text-xs text-gray-400 mt-1">{{ $kpi['subtexto'] }}</p>
                        @endif
                    </div>
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 ml-3 {{ $kpi['color'] }}">
                        <i class="{{ $kpi['icono'] }} text-white text-lg"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Resumen de actividad</h3>
            <div style="height: 280px;">
                <canvas id="chartCanvas"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Actividad reciente</h3>
            <div class="space-y-4">
                @forelse ($actividadReciente ?? [] as $act)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5 {{ $act['color'] }}">
                            <i class="{{ $act['icono'] }} text-white text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $act['titulo'] }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $act['descripcion'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $act['hace'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6">
                        <i class="pi pi-inbox text-2xl text-gray-300 block mb-2"></i>
                        <p class="text-sm text-gray-400">Sin actividad reciente</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">Últimos movimientos</h3>
                <p class="text-xs text-gray-500 mt-0.5">Listado de las últimas operaciones registradas</p>
            </div>
            <a href="#" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
                Ver todos <i class="pi pi-arrow-right ml-1 text-xs"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="table-header">ID</th>
                        <th class="table-header">Tipo</th>
                        <th class="table-header">Descripción</th>
                        <th class="table-header">Monto</th>
                        <th class="table-header">Estado</th>
                        <th class="table-header">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($movimientos ?? [] as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="table-cell font-mono text-xs text-gray-400">#{{ $row['id'] }}</td>
                            <td class="table-cell">
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="{{ $row['icono'] }}" style="color: {{ $row['color'] }}"></i>
                                    {{ $row['tipo'] }}
                                </span>
                            </td>
                            <td class="table-cell font-medium">{{ $row['descripcion'] }}</td>
                            <td class="table-cell font-medium {{ $row['monto'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $row['monto'] >= 0 ? '+' : '' }}${{ number_format(abs($row['monto'])) }}
                            </td>
                            <td class="table-cell">
                                <span class="{{ $row['claseBadge'] }}">{{ $row['estado'] }}</span>
                            </td>
                            <td class="table-cell text-gray-400">{{ $row['fecha'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <i class="pi pi-inbox text-3xl text-gray-300 block mb-2"></i>
                                <p class="text-sm text-gray-400">No hay movimientos registrados</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('chartCanvas');
    if (!ctx) return;

    const dias = 30;
    const etiquetas = [];
    const ingresos = [];
    const egresos = [];
    const ahora = new Date();

    for (let i = dias - 1; i >= 0; i--) {
        const d = new Date(ahora);
        d.setDate(d.getDate() - i);
        etiquetas.push(d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }));
        ingresos.push(Math.round(Math.random() * 8000 + 2000));
        egresos.push(Math.round(Math.random() * 5000 + 1000));
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: etiquetas,
            datasets: [
                {
                    label: 'Ingresos',
                    data: ingresos,
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#059669',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
                {
                    label: 'Egresos',
                    data: egresos,
                    borderColor: '#d97706',
                    backgroundColor: 'rgba(217, 119, 6, 0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#d97706',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, padding: 16, font: { size: 12 } },
                },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 12,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 }, color: '#9ca3af' },
                },
                y: {
                    grid: { color: '#f3f4f6' },
                    ticks: {
                        font: { size: 11 },
                        color: '#9ca3af',
                        callback: (v) => '$' + v.toLocaleString(),
                    },
                },
            },
            interaction: { intersect: false, mode: 'index' },
        },
    });
});
</script>
@endpush
