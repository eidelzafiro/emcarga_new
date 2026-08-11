<script setup>
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'

defineProps({ solicitudes: Array })

const title = 'Vista previa · Solicitudes'

function soloFecha(v) { return v ? String(v).slice(0, 10) : '' }
function fmtNum(v) {
  if (v === null || v === undefined || v === '') return '—'
  const n = Number(v)
  return Number.isFinite(n) && n > 0 ? n.toLocaleString('es-CU', { maximumFractionDigits: 2 }) : '—'
}
function pct(ejec, total) {
  if (!total) return 0
  return Math.min(100, Math.round((Number(ejec) / Number(total)) * 100))
}
function estadoBadge(s) {
  return {
    pendiente: { label: 'Pendiente', cls: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' },
    en_proceso: { label: 'En proceso', cls: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' },
    realizada: { label: 'Realizada', cls: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' },
  }[s] || { label: s, cls: 'bg-gray-100 text-gray-600 dark:bg-gray-500/15 dark:text-gray-300' }
}
</script>

<template>
  <AppLayout :title="title">
    <div class="space-y-4">
      <div class="flex items-center justify-between rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 shadow-sm">
        <div>
          <h1 class="text-base font-bold text-gray-800 dark:text-gray-100">Solicitudes — formato tarjetas</h1>
          <p class="text-xs text-gray-500 dark:text-gray-400">Vista previa de prueba (12 recientes). Sin acciones.</p>
        </div>
        <Button label="Ver página actual" severity="secondary" text icon="pi pi-table" as="a" :href="route('solicitudes.index')" />
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        <article
          v-for="(s, i) in solicitudes"
          :key="s.id"
          class="preview-card relative flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:bg-gray-800 shadow-sm transition-shadow hover:shadow-lg dark:border-gray-700"
          :style="{ animationDelay: `${Math.min(i, 10) * 45}ms` }"
        >
          <header class="relative border-b border-gray-100 bg-gradient-to-br from-amber-50/80 to-white px-4 pt-3 pb-2.5 dark:border-gray-700/70 dark:from-amber-950/20 dark:to-gray-800">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Solicitud</span>
                <div class="cp-folio mt-1 text-[24px] font-black leading-none tracking-tight text-amber-700 dark:text-amber-300">{{ s.numero }}</div>
                <div class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                  <i class="pi pi-calendar mr-1 text-[10px]" />Plan: {{ soloFecha(s.fecha_planificada) }}
                </div>
              </div>
              <span class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-bold" :class="estadoBadge(s.estado_cumplimiento).cls">{{ estadoBadge(s.estado_cumplimiento).label }}</span>
            </div>
          </header>

          <div class="flex flex-1 flex-col gap-3 px-4 py-3">
            <div>
              <div class="truncate text-[16px] font-black tracking-tight text-gray-900 dark:text-white">{{ s.cliente?.nombre || '—' }}</div>
              <div class="mt-0.5 h-1 w-10 rounded-full bg-gradient-to-r from-amber-500 to-amber-300 dark:from-amber-400 dark:to-amber-600" />
            </div>

            <div class="flex items-center gap-1.5 text-sm">
              <span class="inline-flex min-w-0 items-center gap-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-gray-700 dark:text-gray-300">
                <i class="pi pi-map-marker text-xs" style="color:#2563eb" />
                <span class="truncate">{{ s.lugar_origen?.nombre || '—' }}</span>
              </span>
              <i class="pi pi-arrow-right text-xs text-gray-400 shrink-0" />
              <span class="inline-flex min-w-0 items-center gap-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-gray-700 dark:text-gray-300">
                <i class="pi pi-map-marker text-xs" style="color:#dc2626" />
                <span class="truncate">{{ s.lugar_destino?.nombre || '—' }}</span>
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-700/30 px-2 py-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Carga 1</div>
                <div class="truncate text-sm font-bold text-gray-800 dark:text-gray-100">{{ s.producto?.nombre || '—' }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ s.tipo_carga?.nombre || '—' }} · {{ fmtNum(s.peso1) }} t</div>
              </div>
              <div class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-700/30 px-2 py-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Carga 2</div>
                <div class="truncate text-sm font-bold text-gray-800 dark:text-gray-100">{{ s.producto2?.nombre || '—' }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ s.tipo_carga2?.nombre || '—' }} · {{ fmtNum(s.peso2) }} t</div>
              </div>
            </div>

            <div>
              <div class="mb-1 flex items-center justify-between text-[11px] font-semibold text-gray-500 dark:text-gray-400">
                <span>Toneladas</span>
                <span>{{ fmtNum(s.toneladas_ejecutadas) }} / {{ fmtNum(s.toneladas_total) }}</span>
              </div>
              <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700/60">
                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-emerald-500" :style="{ width: `${pct(s.toneladas_ejecutadas, s.toneladas_total)}%` }" />
              </div>
            </div>
          </div>

          <footer class="mt-auto flex items-center justify-between border-t border-gray-100 dark:border-gray-700/70 bg-gray-50/80 px-3 py-2 text-[11px] text-gray-500 dark:bg-gray-700/30 dark:text-gray-400">
            <span>{{ fmtNum(s.toneladas_pendientes) }} t pendientes</span>
            <span v-if="s.cartas_porte?.length">{{ s.cartas_porte.length }} CP</span>
            <span v-else>sin cartas</span>
          </footer>
        </article>
      </div>

      <div v-if="!solicitudes.length" class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-16 text-center">
        <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600" />
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No hay solicitudes para la entidad activa</p>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.preview-card {
  animation: cp-rise 0.45s ease both;
}
@keyframes cp-rise {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.cp-folio {
  font-variant-numeric: tabular-nums;
}
</style>
