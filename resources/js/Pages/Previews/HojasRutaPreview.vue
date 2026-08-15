<script setup>
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import { formatDate } from '@/Utils/date'

defineProps({ hojas: Array })

const title = 'Vista previa · Hoja de Ruta'

function soloFecha(v) { return formatDate(v) }
function soloHora(v) { return v ? String(v).slice(0, 5) : '' }
function choferNombre(c) { return c ? `${c.nombre} ${c.apellidos || ''}`.trim() : '—' }
function iniciales(nombre) {
  return String(nombre || '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map(w => w[0])
    .join('')
    .toUpperCase() || '?'
}
function fmtNum(v) {
  if (v === null || v === undefined || v === '') return '—'
  const n = Number(v)
  return Number.isFinite(n) && n > 0 ? n.toLocaleString('es-CU', { maximumFractionDigits: 2 }) : '—'
}
function estadoHR(d) {
  if (d.cancelada) return { label: 'Cancelada', cls: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' }
  if (!d.fecha_cierre) return { label: 'Abierta', cls: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' }
  return { label: 'Cerrada', cls: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' }
}
const diffComb = (h) => ((Number(h.combustible_consumido) || 0) - (Number(h.combustible_habilitado) || 0))
</script>

<template>
  <AppLayout :title="title">
    <div class="space-y-4">
      <div class="flex items-center justify-between rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 shadow-sm">
        <div>
          <h1 class="text-base font-bold text-gray-800 dark:text-gray-100">Hojas de Ruta — formato tarjetas</h1>
          <p class="text-xs text-gray-500 dark:text-gray-400">Vista previa de prueba (12 recientes). Sin acciones.</p>
        </div>
        <Button label="Ver página actual" severity="secondary" text icon="pi pi-table" as="a" :href="route('hojas-ruta.index')" />
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        <article
          v-for="(h, i) in hojas"
          :key="h.id"
          class="preview-card relative flex flex-col overflow-hidden rounded-2xl border bg-white dark:bg-gray-800 shadow-sm transition-shadow hover:shadow-lg dark:border-gray-700"
          :class="h.cancelada ? 'border-red-300 dark:border-red-800/60' : h.fecha_cierre ? 'border-sky-200 dark:border-sky-800/40' : 'border-emerald-200 dark:border-emerald-800/40'"
          :style="{ animationDelay: `${Math.min(i, 10) * 45}ms` }"
        >
          <!-- Sello cancelada -->
          <div v-if="h.cancelada" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center">
            <span class="rotate-[-14deg] border-[3px] border-red-500/70 text-red-500/80 dark:border-red-400/70 dark:text-red-300/80 rounded-lg px-4 py-1 text-xl font-black uppercase tracking-[0.22em]">Cancelada</span>
          </div>

          <header class="relative px-4 pt-3 pb-2.5 border-b border-gray-100 dark:border-gray-700/70" :class="h.cancelada ? 'bg-red-50/60 dark:bg-red-950/20' : 'bg-gradient-to-br from-emerald-50/80 to-white dark:from-emerald-950/20 dark:to-gray-800'">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Hoja de ruta</span>
                <div class="cp-folio mt-1 text-[24px] font-black leading-none tracking-tight" :class="h.cancelada ? 'text-red-500 dark:text-red-400 line-through' : 'text-emerald-700 dark:text-emerald-300'">
                  {{ h.numero }}
                </div>
                <div class="mt-1.5 flex items-center gap-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                  <i class="pi pi-calendar mr-0.5 text-[10px]" />{{ soloFecha(h.fecha_emision) }} {{ h.hora_emision ? `· ${soloHora(h.hora_emision)}` : '' }}
                  <i class="pi pi-arrow-right mx-1 text-[9px]" />
                  <i class="pi pi-calendar-times mr-0.5 text-[10px]" />{{ h.fecha_cierre ? `${soloFecha(h.fecha_cierre)} ${h.hora_cierre ? `· ${soloHora(h.hora_cierre)}` : ''}` : 'abierta' }}
                </div>
              </div>
              <span class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-bold" :class="estadoHR(h).cls">{{ estadoHR(h).label }}</span>
            </div>
          </header>

          <div class="flex flex-1 flex-col gap-3 px-4 py-3">
            <div class="flex items-center gap-2 min-w-0">
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-[11px] font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                {{ iniciales(choferNombre(h.chofer)) }}
              </span>
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ choferNombre(h.chofer) }}</div>
                <div v-if="h.chofer2" class="truncate text-[11px] text-gray-500 dark:text-gray-400">2do: {{ choferNombre(h.chofer2) }}</div>
              </div>
              <span v-if="Number(h.cartas_porte_count) > 0" class="ml-auto shrink-0 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                {{ h.cartas_porte_count }} CP
              </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 dark:border-emerald-700/50 bg-emerald-50 dark:bg-emerald-950/30 px-3 py-1.5">
                <i class="pi pi-truck text-xl" style="color:#059669" />
                <span class="text-lg font-black tracking-tight text-emerald-800 dark:text-emerald-300">{{ h.tractivo?.codigo || '—' }}</span>
              </span>
              <span v-if="h.arrastre" class="inline-flex items-center gap-2 rounded-xl border border-violet-200 dark:border-violet-700/50 bg-violet-50 dark:bg-violet-950/30 px-3 py-1.5">
                <i class="pi pi-box text-xl" style="color:#7c3aed" />
                <span class="text-lg font-black tracking-tight text-violet-800 dark:text-violet-300">{{ h.arrastre.codigo }}</span>
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-center">
              <div class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-700/30 px-2 py-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">KMS totales</div>
                <div class="text-sm font-black text-gray-800 dark:text-gray-100">{{ fmtNum(h.kms_totales) }}</div>
              </div>
              <div class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-700/30 px-2 py-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Consumo</div>
                <div class="text-sm font-black text-gray-800 dark:text-gray-100">{{ fmtNum(h.combustible_consumido) }}</div>
              </div>
              <div class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-700/30 px-2 py-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Habilitado</div>
                <div class="text-sm font-black text-gray-800 dark:text-gray-100">{{ fmtNum(h.combustible_habilitado) }}</div>
              </div>
              <div class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-700/30 px-2 py-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Dif</div>
                <div class="text-sm font-black" :class="diffComb(h) < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-100'">{{ fmtNum(diffComb(h)) }}</div>
              </div>
            </div>
          </div>

          <footer class="mt-auto border-t border-gray-100 dark:border-gray-700/70 px-3 py-2 bg-gray-50/80 dark:bg-gray-700/30">
            <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
              <span>{{ h.entidad?.nombre || '—' }}</span>
              <span>{{ h.parqueo?.nombre || '—' }}</span>
            </div>
          </footer>
        </article>
      </div>

      <div v-if="!hojas.length" class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-16 text-center">
        <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600" />
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No hay hojas de ruta vigentes en el mes de operaciones</p>
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
