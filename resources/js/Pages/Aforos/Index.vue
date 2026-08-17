<script setup>
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Toolbar from 'primevue/toolbar'
import { formatDate } from '@/Utils/date'

const props = defineProps({ aforos: Object, filters: Object, filtros: Object, fechaOperaciones: String, mesSeleccionado: Number, anioSeleccionado: Number })

const search = ref(props.filters?.search || '')
const cliente = ref(props.filters?.cliente || '')
const chofer = ref(props.filters?.chofer || '')
const equipo = ref(props.filters?.equipo || '')

const monet = (v) => '$' + Number(v || 0).toLocaleString()

function produccion(aforo) {
    return (Number(aforo.flete_mt) || 0) + (Number(aforo.otros_mt) || 0) + (Number(aforo.flete_demora) || 0)
}

function estadoDe(aforo) {
    if (aforo.id_factura) return { label: 'Facturado', severity: 'success' }
    if (aforo.id_prefactura) return { label: 'Prefacturado', severity: 'warn' }
    return { label: 'Pendiente', severity: 'info' }
}

function iniciales(nombre) {
    if (!nombre) return '—'
    return nombre.split(' ').filter(Boolean).slice(0, 2).map((n) => n[0]).join('').toUpperCase()
}
function choferNombre(c) { return c ? `${c.nombre || ''} ${c.apellidos || ''}`.trim() : '—' }

// Agrupación por fecha de parte (documentos procesados ese día)
const grupos = computed(() => {
    const map = new Map()
    for (const a of props.aforos.data || []) {
        const clave = formatDate(a.fecha_parte) || 'Sin fecha'
        if (!map.has(clave)) map.set(clave, { fecha: clave, aforos: [] })
        map.get(clave).aforos.push(a)
    }
    return Array.from(map.values())
})

const totalRegistros = computed(() => props.aforos.total || 0)

function abrirAforo(a) {
    if (a.id_factura) {
        router.get(route('aforos.show', a.id))
    } else {
        router.get(route('aforos.edit', a.id))
    }
}

function imprimir(a) {
    window.open(route('aforos.imprimir', { aforo: a.id }), '_blank')
}

function navegar() {
    router.get(route('aforos.index'), { search: search.value, cliente: cliente.value, chofer: chofer.value, equipo: equipo.value }, { preserveState: true, replace: true })
}

watch([search, cliente, chofer, equipo], navegar)
</script>

<template>
    <AppLayout :title="title">
        <div class="p-4">
            <Toolbar class="mb-4">
                <template #start>
                    <h2 class="text-xl font-bold text-surface-800 dark:text-surface-100">Aforos</h2>
                    <span class="text-sm text-surface-500 dark:text-surface-400 ml-3">{{ totalRegistros }} registros</span>
                </template>
                <template #end>
                    <div class="flex gap-2 flex-wrap">
                        <Button label="Nuevo Aforo" icon="pi pi-plus" @click="router.get(route('aforos.create'))" />
                        <InputText v-model="search" placeholder="Buscar CP o HR..." />
                        <Select v-model="cliente" :options="filtros?.clientes || []" option-value="id" option-label="nombre" placeholder="Cliente" show-clear filter class="w-44" />
                        <Select v-model="chofer" :options="filtros?.choferes || []" option-value="id" option-label="nombre" placeholder="Chofer" show-clear filter class="w-44" />
                        <Select v-model="equipo" :options="filtros?.tractivos || []" option-value="id" option-label="codigo" placeholder="Equipo" show-clear filter class="w-36" />
                    </div>
                </template>
            </Toolbar>

            <div v-if="grupos.length === 0" class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-16 text-center">
                <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600" />
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No hay aforos para el período.</p>
                <Button label="Nuevo Aforo" icon="pi pi-plus" severity="success" @click="router.get(route('aforos.create'))" />
            </div>

            <!-- Agrupación por fecha de parte -->
            <div v-for="grupo in grupos" :key="grupo.fecha" class="mb-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex items-center gap-2 bg-blue-600 text-white px-3 py-1.5 rounded-lg">
                        <i class="pi pi-calendar text-sm"></i>
                        <span class="font-semibold">{{ grupo.fecha }}</span>
                    </div>
                    <span class="text-sm text-surface-500 dark:text-surface-400">{{ grupo.aforos.length }} documento(s)</span>
                </div>

                <!-- Grid de tarjetas (4 por línea) -->
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="a in grupo.aforos"
                        :key="a.id"
                        class="cp-card relative flex flex-col overflow-hidden rounded-xl border bg-white dark:bg-gray-800 shadow-sm transition-shadow hover:shadow-md dark:border-gray-700 border-gray-200 cursor-pointer"
                        :class="a.id_factura ? 'border-emerald-300 dark:border-emerald-800/60' : ''"
                        @click="abrirAforo(a)"
                    >
                        <!-- Sello de facturada -->
                        <div v-if="a.id_factura" class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center">
                            <span class="rotate-[-14deg] border-[3px] border-emerald-500/70 text-emerald-600/80 dark:border-emerald-400/70 dark:text-emerald-300/80 rounded-lg px-4 py-1 text-xl font-black uppercase tracking-[0.22em]">Facturada</span>
                        </div>

                        <!-- Cabecera: folio CP + HR + equipo -->
                        <header class="relative px-3 pt-2 pb-1.5 border-b border-gray-100 dark:border-gray-700/70 bg-gradient-to-br from-blue-50/80 to-white dark:from-blue-950/20 dark:to-gray-800">
                            <div class="flex items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <span class="block text-[9px] font-bold uppercase tracking-[0.16em] text-gray-400 dark:text-gray-500">Carta de porte</span>
                                    <div class="cp-folio mt-0.5 text-[18px] font-black leading-none tracking-tight text-blue-800 dark:text-blue-300">{{ a.carta_porte?.numero }}</div>
                                    <div class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                                        <i class="pi pi-calendar mr-1 text-[9px]" />{{ formatDate(a.fecha_parte) }}
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="block text-[9px] font-bold uppercase tracking-[0.16em] text-gray-400 dark:text-gray-500">HR</span>
                                    <div class="cp-folio mt-0.5 text-[18px] font-black leading-none tracking-tight text-blue-700 dark:text-blue-300">{{ a.carta_porte?.hoja_ruta?.numero || '—' }}</div>
                                    <div class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                                        <i class="pi pi-hashtag mr-1 text-[9px]" />{{ a.carta_porte?.tractivo?.codigo || '—' }}
                                    </div>
                                </div>
                            </div>
                        </header>

                        <!-- Cuerpo: cliente + totales -->
                        <div class="flex flex-1 flex-col gap-2 px-3 py-2">
                            <div class="min-w-0">
                                <div class="truncate text-[14px] font-bold tracking-tight text-gray-900 dark:text-white">{{ a.carta_porte?.cliente?.nombre || '—' }}</div>
                            </div>

                            <!-- Totales (sin símbolo $) -->
                            <div class="flex items-center gap-1 flex-wrap">
                                <span class="inline-flex items-center gap-1 rounded-md border border-blue-200 dark:border-blue-700/50 bg-blue-50 dark:bg-blue-950/30 px-1.5 py-0.5 text-[11px] font-bold text-blue-700 dark:text-blue-300">
                                    {{ produccion(a).toLocaleString() }}
                                </span>
                                <span v-if="Number(a.flete_demora)" class="inline-flex items-center gap-1 rounded-md bg-gray-50 dark:bg-gray-700/50 px-1.5 py-0.5 text-[10px] font-medium text-gray-700 dark:text-gray-300">
                                    Demora {{ Number(a.flete_demora).toLocaleString() }}
                                </span>
                                <span v-if="Number(a.salario)" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 dark:bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
                                    Sal {{ Number(a.salario).toLocaleString() }}
                                </span>
                            </div>
                        </div>

                        <!-- Pie: factura (si está facturada) + acciones -->
                        <div class="mt-auto flex items-center gap-1 border-t border-gray-100 dark:border-gray-700/70 px-2 py-1 bg-gray-50/80 dark:bg-gray-700/30">
                            <div class="flex-1 min-w-0">
                                <span v-if="a.id_factura" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300">
                                    <i class="pi pi-file text-[10px]" />Factura {{ a.factura?.numero }}
                                </span>
                                <Tag v-else :severity="estadoDe(a).severity" :value="estadoDe(a).label" class="text-[10px]" />
                            </div>
                            <Button v-if="a.id_factura" icon="pi pi-eye" rounded text severity="info" size="small" @click.stop="router.get(route('aforos.show', a.id))" v-tooltip.top="'Ver'" />
                            <Button v-else icon="pi pi-pencil" rounded text severity="warn" size="small" @click.stop="router.get(route('aforos.edit', a.id))" v-tooltip.top="'Editar'" />
                            <Button icon="pi pi-print" rounded text severity="success" size="small" @click.stop="imprimir(a)" v-tooltip.top="'Imprimir'" />
                        </div>
                    </article>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="aforos.last_page > 1" class="flex justify-center gap-2 mt-6">
                <Button icon="pi pi-chevron-left" text severity="secondary" :disabled="aforos.current_page <= 1"
                    @click="router.get(route('aforos.index'), { ...filters, page: aforos.current_page - 1 })" />
                <span class="self-center text-sm text-surface-600 dark:text-surface-400">Página {{ aforos.current_page }} de {{ aforos.last_page }}</span>
                <Button icon="pi pi-chevron-right" text severity="secondary" :disabled="aforos.current_page >= aforos.last_page"
                    @click="router.get(route('aforos.index'), { ...filters, page: aforos.current_page + 1 })" />
            </div>
        </div>
    </AppLayout>
</template>
