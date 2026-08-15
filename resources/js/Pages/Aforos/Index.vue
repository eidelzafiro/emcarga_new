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

const props = defineProps({ aforos: Object, filters: Object, filtros: Object, fechaOperaciones: String })

const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || '')
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

watch([search, estado, cliente, chofer, equipo], () => {
    router.get(route('aforos.index'), { search: search.value, estado: estado.value, cliente: cliente.value, chofer: chofer.value, equipo: equipo.value }, { preserveState: true, replace: true })
})
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
                        <Select v-model="estado" :options="['', 'pendiente', 'prefacturado', 'facturado']" placeholder="Estado" class="w-40" />
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

                <!-- Grid de tarjetas (estilo Carta de Porte) -->
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    <article
                        v-for="a in grupo.aforos"
                        :key="a.id"
                        class="cp-card relative flex flex-col overflow-hidden rounded-2xl border bg-white dark:bg-gray-800 shadow-sm transition-shadow hover:shadow-lg dark:border-gray-700 border-gray-200 cursor-pointer"
                        @click="abrirAforo(a)"
                    >
                        <!-- Cabecera: CP protagonista + HR -->
                        <header class="relative px-4 pt-3 pb-2.5 border-b border-gray-100 dark:border-gray-700/70 bg-gradient-to-br from-blue-50/80 to-white dark:from-blue-950/20 dark:to-gray-800">
                            <div class="flex items-start gap-4">
                                <div class="min-w-0 flex-1">
                                    <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Carta de porte</span>
                                    <div class="cp-folio mt-1 text-[24px] font-black leading-none tracking-tight text-blue-800 dark:text-blue-300">CP {{ a.carta_porte?.numero }}</div>
                                    <div class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                                        <i class="pi pi-calendar mr-1 text-[10px]" />{{ formatDate(a.fecha_parte) }}
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Hoja de ruta</span>
                                    <div class="cp-folio mt-1 text-[24px] font-black leading-none tracking-tight text-blue-700 dark:text-blue-300">{{ a.carta_porte?.hoja_ruta?.numero || '—' }}</div>
                                    <div class="mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                                        <i class="pi pi-hashtag mr-1 text-[10px]" />{{ a.carta_porte?.tractivo?.codigo || '—' }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 pt-4 shrink-0">
                                    <Tag :severity="estadoDe(a).severity" :value="estadoDe(a).label" />
                                </div>
                            </div>
                        </header>

                        <!-- Cuerpo -->
                        <div class="flex flex-1 flex-col gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-[16px] font-black tracking-tight text-gray-900 dark:text-white">{{ a.carta_porte?.cliente?.nombre || '—' }}</div>
                                <div class="mt-0.5 h-1 w-10 rounded-full bg-gradient-to-r from-blue-500 to-blue-300 dark:from-blue-400 dark:to-blue-600" />
                                <div class="mt-1.5 flex items-center gap-1.5 text-sm">
                                    <span class="inline-flex min-w-0 items-center gap-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-gray-700 dark:text-gray-300">
                                        <i class="pi pi-map-marker text-xs" style="color:#2563eb" />
                                        <span class="truncate">{{ a.carta_porte?.lugar_origen?.nombre || '—' }}</span>
                                    </span>
                                    <i class="pi pi-arrow-right text-xs text-gray-400 shrink-0" />
                                    <span class="inline-flex min-w-0 items-center gap-1.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-gray-700 dark:text-gray-300">
                                        <i class="pi pi-map-marker text-xs" style="color:#dc2626" />
                                        <span class="truncate">{{ a.carta_porte?.lugar_destino?.nombre || '—' }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Totales -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="inline-flex items-center gap-1 rounded-lg border border-blue-200 dark:border-blue-700/50 bg-blue-50 dark:bg-blue-950/30 px-2 py-1 text-xs font-bold text-blue-700 dark:text-blue-300">
                                    <i class="pi pi-dollar text-[11px]" />{{ monet(produccion(a)) }}
                                </span>
                                <span v-if="Number(a.flete_mt)" class="inline-flex items-center gap-1 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Flete {{ monet(a.flete_mt) }}
                                </span>
                                <span v-if="Number(a.flete_demora)" class="inline-flex items-center gap-1 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Demora {{ monet(a.flete_demora) }}
                                </span>
                                <span v-if="Number(a.otros_mt)" class="inline-flex items-center gap-1 rounded-lg bg-gray-50 dark:bg-gray-700/50 px-2 py-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                                    Otros {{ monet(a.otros_mt) }}
                                </span>
                                <span v-if="Number(a.salario)" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                    Sal {{ monet(a.salario) }}
                                </span>
                            </div>
                        </div>

                        <!-- Pie: acciones -->
                        <div class="mt-auto flex items-center justify-end gap-1 border-t border-gray-100 dark:border-gray-700/70 px-3 py-2 bg-gray-50/80 dark:bg-gray-700/30">
                            <Button v-if="a.id_factura" icon="pi pi-eye" rounded text severity="info" size="small" @click.stop="router.get(route('aforos.show', a.id))" v-tooltip.top="'Ver'" />
                            <Button v-else icon="pi pi-pencil" rounded text severity="warn" size="small" @click.stop="router.get(route('aforos.edit', a.id))" v-tooltip.top="'Editar'" />
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
