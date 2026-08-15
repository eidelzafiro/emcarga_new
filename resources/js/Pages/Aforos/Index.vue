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
        <div class="card p-4 dark:bg-surface-900">
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

            <!-- Agrupación por fecha de parte -->
            <div v-if="grupos.length === 0" class="text-center py-16 text-surface-500 dark:text-surface-400">
                No hay aforos para el período.
            </div>

            <div v-for="grupo in grupos" :key="grupo.fecha" class="mb-6">
                <!-- Cabecera de grupo (fecha de parte) -->
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex items-center gap-2 bg-blue-600 text-white px-3 py-1.5 rounded-lg">
                        <i class="pi pi-calendar text-sm"></i>
                        <span class="font-semibold">{{ grupo.fecha }}</span>
                    </div>
                    <span class="text-sm text-surface-500 dark:text-surface-400">{{ grupo.aforos.length }} documento(s)</span>
                </div>

                <!-- Tarjetas: 4 por línea -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    <div v-for="a in grupo.aforos" :key="a.id"
                        class="bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl shadow-sm hover:shadow-md transition cursor-pointer"
                        @click="abrirAforo(a)">
                        <!-- Encabezado tarjeta -->
                        <div class="flex items-center justify-between p-2.5 border-b border-surface-100 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/60 rounded-t-xl">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="font-bold text-blue-700 dark:text-blue-400 text-sm truncate">CP {{ a.carta_porte?.numero }}</span>
                                <span v-if="a.carta_porte?.tractivo?.codigo" class="text-[10px] bg-surface-200 dark:bg-surface-700 px-1.5 py-0.5 rounded-full truncate">{{ a.carta_porte.tractivo.codigo }}</span>
                            </div>
                            <Tag :severity="estadoDe(a).severity" :value="estadoDe(a).label" />
                        </div>

                        <!-- Cuerpo: solo campos con valor -->
                        <div class="p-2.5 space-y-1 text-xs">
                            <div v-if="a.carta_porte?.cliente?.nombre" class="flex items-center justify-between gap-2">
                                <span class="text-surface-500 dark:text-surface-400 truncate">Cliente</span>
                                <span class="font-medium text-right truncate max-w-[60%] text-surface-700 dark:text-surface-200">{{ a.carta_porte.cliente.nombre }}</span>
                            </div>
                            <div v-if="a.carta_porte?.hoja_ruta?.numero" class="flex items-center justify-between">
                                <span class="text-surface-500 dark:text-surface-400">HR</span>
                                <span class="font-medium text-surface-700 dark:text-surface-200">{{ a.carta_porte.hoja_ruta.numero }}</span>
                            </div>
                            <div v-if="a.carta_porte?.distancia" class="flex items-center justify-between">
                                <span class="text-surface-500 dark:text-surface-400">Kms</span>
                                <span class="font-medium text-surface-700 dark:text-surface-200">{{ a.carta_porte.distancia }}</span>
                            </div>
                            <div v-if="Number(a.flete_mt)" class="flex items-center justify-between">
                                <span class="text-surface-500 dark:text-surface-400">Flete MN</span>
                                <span class="font-semibold text-surface-700 dark:text-surface-100">{{ monet(a.flete_mt) }}</span>
                            </div>
                            <div v-if="Number(a.flete_demora)" class="flex items-center justify-between">
                                <span class="text-surface-500 dark:text-surface-400">Demora</span>
                                <span class="font-medium text-surface-700 dark:text-surface-200">{{ monet(a.flete_demora) }}</span>
                            </div>
                            <div v-if="Number(a.otros_mt)" class="flex items-center justify-between">
                                <span class="text-surface-500 dark:text-surface-400">Otros</span>
                                <span class="font-medium text-surface-700 dark:text-surface-200">{{ monet(a.otros_mt) }}</span>
                            </div>
                            <div v-if="Number(a.salario)" class="flex items-center justify-between">
                                <span class="text-surface-500 dark:text-surface-400">Salario</span>
                                <span class="font-medium text-surface-700 dark:text-surface-200">{{ monet(a.salario) }}</span>
                            </div>
                            <div v-if="a.factura" class="flex items-center justify-between">
                                <span class="text-surface-500 dark:text-surface-400">Factura</span>
                                <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ a.factura.numero }}</span>
                            </div>
                        </div>

                        <!-- Total + acción -->
                        <div class="flex items-center justify-between px-2.5 py-2 border-t border-surface-100 dark:border-surface-700">
                            <div class="text-sm font-bold text-blue-700 dark:text-blue-400">
                                {{ monet(produccion(a)) }}
                            </div>
                            <div class="flex gap-1">
                                <Button v-if="a.id_factura" icon="pi pi-eye" rounded text severity="info" size="small" @click.stop="router.get(route('aforos.show', a.id))" v-tooltip.top="'Ver'" />
                                <Button v-else icon="pi pi-pencil" rounded text severity="warn" size="small" @click.stop="router.get(route('aforos.edit', a.id))" v-tooltip.top="'Editar'" />
                            </div>
                        </div>
                    </div>
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
