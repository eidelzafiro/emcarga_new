<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import ProgressSpinner from 'primevue/progressspinner'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    title: String,
    salarios: { type: Array, default: () => [] },
    mes: Number,
    ano: Number,
    filters: Object,
    paginacion: Object,
    areasDisponibles: { type: Array, default: () => [] },
    grupoHorarios: { type: Array, default: () => [] },
    grupoEscalas: { type: Array, default: () => [] },
})

const toast = useToast()
const loading = ref(false)
const expandedRows = ref({})
const search = ref(props.filters?.search || '')
const selectedMes = ref(props.mes)
const selectedAno = ref(props.ano)
const areaFilter = ref(props.filters?.area_filter || '')
const horarioFilter = ref(props.filters?.horario_filter || '')
const escalaFilter = ref(props.filters?.escala_filter || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({})

const showTurnoCalendar = ref(false)
const turnoEmpleado = ref(null)
const turnoLoading = ref(false)
const turnoGuardando = ref(false)
const turnoDias = ref([])

const meses = [
    { label: 'Enero', value: 1 }, { label: 'Febrero', value: 2 },
    { label: 'Marzo', value: 3 }, { label: 'Abril', value: 4 },
    { label: 'Mayo', value: 5 }, { label: 'Junio', value: 6 },
    { label: 'Julio', value: 7 }, { label: 'Agosto', value: 8 },
    { label: 'Septiembre', value: 9 }, { label: 'Octubre', value: 10 },
    { label: 'Noviembre', value: 11 }, { label: 'Diciembre', value: 12 },
]

const mesLabel = computed(() => meses.find(m => m.value === selectedMes.value)?.label || '')

const currentPage = ref(props.paginacion?.current_page || 1)

function aplicarFiltros(page = 1) {
    loading.value = true
    currentPage.value = page
    router.get(route('salarios-administrativos.index'), {
        mes: selectedMes.value,
        ano: selectedAno.value,
        search: search.value,
        area_filter: areaFilter.value,
        horario_filter: horarioFilter.value,
        escala_filter: escalaFilter.value,
        page: page,
    }, {
        preserveState: true,
        onFinish: () => loading.value = false,
    })
}

function onPage(event) {
    aplicarFiltros(event.page + 1)
}

function formatCurrency(val) {
    if (!val && val !== 0) return '—'
    return new Intl.NumberFormat('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val)
}

function formatNum(val) {
    if (!val && val !== 0) return '—'
    return new Intl.NumberFormat('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val)
}

function getTagSeverity(salario) {
    if (!salario || salario <= 0) return 'secondary'
    if (salario < 500) return 'warn'
    return 'success'
}

function openEdit(item) {
    editing.value = item
    form.value = {
        id_movimiento: item.id_movimiento,
        fecha: `${selectedAno.value}-${String(selectedMes.value).padStart(2, '0')}-01`,
        irregular: item.irregular || 0,
        feriados: item.feriados_editados || 0,
        dias_taller: item.dias_taller || 0,
        h_extra: item.h_extra || 0,
        imp_h_extra: item.imp_h_extra || 0,
        observaciones: item.observaciones || '',
    }
    showForm.value = true
}

function submitForm() {
    const payload = { ...form.value }
    const url = editing.value?.id_salario_admin
        ? route('salarios-administrativos.update', editing.value.id_salario_admin)
        : route('salarios-administrativos.store')

    router.post(url, payload, {
        preserveState: true,
        onSuccess: () => {
            showForm.value = false
            toast.add({ severity: 'success', summary: 'Guardado', life: 3000 })
            aplicarFiltros()
        },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}

// --- Calendario de Turnos ---
function diasEnMes(mes, ano) {
    return new Date(ano, mes, 0).getDate()
}

function semanaDelAno(ano, mes, dia) {
    const d = new Date(ano, mes - 1, dia)
    const enero1 = new Date(ano, 0, 1)
    const diff = d - enero1
    return Math.floor(diff / (7 * 24 * 60 * 60 * 1000)) + 1
}

function abrirTurno(item) {
    turnoEmpleado.value = item
    const totalDias = diasEnMes(selectedMes.value, selectedAno.value)
    const existentes = {}
    if (item.turnos && Array.isArray(item.turnos)) {
        for (const t of item.turnos) {
            const fecha = t.inicio ? t.inicio.split('-')[2] : null
            if (fecha) {
                existentes[parseInt(fecha)] = {
                    tiempo: t.tiempo || 0,
                    noct1: t.noct1 || 0,
                    noct2: t.noct2 || 0,
                    doblaje: t.doblaje || 0,
                }
            }
        }
    }

    turnoDias.value = []
    for (let d = 1; d <= totalDias; d++) {
        const fecha = `${selectedAno.value}-${String(selectedMes.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        const semana = semanaDelAno(selectedAno.value, selectedMes.value, d)
        turnoDias.value.push({
            dia: d,
            fecha,
            semana,
            tiempo: existentes[d]?.tiempo || 0,
            noct1: existentes[d]?.noct1 || 0,
            noct2: existentes[d]?.noct2 || 0,
            doblaje: existentes[d]?.doblaje || 0,
        })
    }
    showTurnoCalendar.value = true
}

function turnoResumen() {
    let totalTiempo = 0
    let totalNoct1 = 0
    let totalNoct2 = 0
    let totalDoblaje = 0
    for (const d of turnoDias.value) {
        totalTiempo += d.tiempo
        totalNoct1 += d.noct1
        totalNoct2 += d.noct2
        totalDoblaje += d.doblaje
    }
    return { totalTiempo, totalNoct1, totalNoct2, totalDoblaje }
}

function guardarTurnos() {
    if (!turnoEmpleado.value) return
    turnoGuardando.value = true

    const turnos = turnoDias.value.filter(d => d.tiempo > 0 || d.noct1 > 0 || d.noct2 > 0 || d.doblaje > 0)

    router.post(route('salarios-administrativos.guardar-turno'), {
        id_movimiento: turnoEmpleado.value.id_movimiento,
        turnos: turnos.map(d => ({
            inicio: d.fecha,
            tiempo: d.tiempo,
            noct1: d.noct1,
            noct2: d.noct2,
            doblaje: d.doblaje,
        })),
    }, {
        preserveState: true,
        onSuccess: () => {
            showTurnoCalendar.value = false
            turnoGuardando.value = false
            toast.add({ severity: 'success', summary: 'Turnos guardados', life: 3000 })
            aplicarFiltros()
        },
        onError: (e) => {
            turnoGuardando.value = false
            toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 })
        },
    })
}

const semanaDias = computed(() => {
    const semanas = {}
    for (const d of turnoDias.value) {
        if (!semanas[d.semana]) semanas[d.semana] = []
        semanas[d.semana].push(d)
    }
    return semanas
})

let searchTimeout = null
function onSearchInput() {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => aplicarFiltros(), 500)
}

function rowClass(data) {
    if (data.es_turnos) return 'bg-amber-50 dark:bg-amber-900/10'
    return ''
}
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl leading-tight">{{ title }}</h2>
                <div class="flex gap-2">
                    <a :href="route('reportes.prenomina-administrativo', { mes: selectedMes, ano: selectedAno })"
                        target="_blank"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 transition">
                        <i class="pi pi-file-pdf"></i> Prenomina PDF
                    </a>
                </div>
            </div>
        </template>

        <div class="py-4">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <!-- Filtros -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mes</label>
                            <Select v-model="selectedMes" :options="meses" optionLabel="label" optionValue="value"
                                class="w-40" @change="aplicarFiltros()" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Área</label>
                            <Select v-model="areaFilter" :options="areasDisponibles" optionLabel="nombre"
                                optionValue="id" placeholder="Todas" class="w-48" showClear @change="aplicarFiltros()" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo Horario</label>
                            <Select v-model="horarioFilter" :options="grupoHorarios" optionLabel="nombre"
                                optionValue="id" placeholder="Todos" class="w-44" showClear @change="aplicarFiltros()" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Grupo Escala</label>
                            <Select v-model="escalaFilter" :options="grupoEscalas" optionLabel="nombre"
                                optionValue="id" placeholder="Todos" class="w-44" showClear @change="aplicarFiltros()" />
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar empleado</label>
                            <InputText v-model="search" placeholder="Nombre del empleado..." class="w-full"
                                @input="onSearchInput" />
                        </div>
                    </div>
                </div>

                <!-- Cargando -->
                <div v-if="loading" class="flex justify-center py-12">
                    <ProgressSpinner style="width: 50px; height: 50px" />
                </div>

                <!-- Tabla principal -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <DataTable :value="salarios" v-model:expandedRows="expandedRows" dataKey="id_bolsa"
                        stripedRows responsiveLayout="scroll" :rows="20" lazy
                        :totalRecords="paginacion?.total || 0"
                        :first="(currentPage - 1) * 20"
                        @page="onPage"
                        :rowClass="rowClass"
                        :globalFilterFields="['nombre_completo', 'cargo', 'area']"
                        paginator
                        emptyMessage="No hay empleados con datos para este período.">
                        <template #paginatorstart>
                            <span class="text-sm text-gray-500">
                                {{ paginacion?.total || 0 }} empleados · Página {{ paginacion?.current_page || 1 }} de {{ paginacion?.total_pages || 1 }}
                            </span>
                        </template>
                        <template #header>
                            <div class="text-lg font-semibold">
                                {{ mesLabel }} {{ selectedAno }}
                            </div>
                        </template>

                        <Column expander style="width: 3rem" />
                        <Column field="nombre_completo" header="Empleado" sortable style="min-width: 200px">
                            <template #body="{ data }">
                                <div>
                                    <div class="font-semibold">{{ data.nombre_completo }}</div>
                                    <div class="text-xs text-gray-500">{{ data.cargo }} — {{ data.area }}</div>
                                </div>
                            </template>
                        </Column>
                        <Column header="Tarifa" class="text-right">
                            <template #body="{ data }">{{ formatNum(data.tarifa) }}</template>
                        </Column>
                        <Column header="Tiempo" class="text-right">
                            <template #body="{ data }">
                                <span :class="{ 'text-red-600': data.ttotal > 240 }">
                                    {{ formatNum(data.regular) }}
                                </span>
                                <span v-if="data.irregular > 0" class="text-xs text-orange-500 ml-1">
                                    ({{ formatNum(data.irregular) }} irr)
                                </span>
                            </template>
                        </Column>
                        <Column header="Básico" field="imp_regular" class="text-right" sortable>
                            <template #body="{ data }">{{ formatCurrency(data.imp_regular) }}</template>
                        </Column>
                        <Column header="Irregular" class="text-right">
                            <template #body="{ data }">{{ formatCurrency(data.imp_irregular) }}</template>
                        </Column>
                        <Column header="CLA" field="imp_cla" class="text-right">
                            <template #body="{ data }">{{ formatCurrency(data.imp_cla) }}</template>
                        </Column>
                        <Column header="Nocturnidad" class="text-right">
                            <template #body="{ data }">
                                {{ formatCurrency(data.imp_nocturnidad) }}
                            </template>
                        </Column>
                        <Column header="Doblaje" class="text-right">
                            <template #body="{ data }">{{ formatCurrency(data.imp_doblaje) }}</template>
                        </Column>
                        <Column header="H. Extra" class="text-right">
                            <template #body="{ data }">{{ formatCurrency(data.imp_extra) }}</template>
                        </Column>
                        <Column header="Incidencias" class="text-right">
                            <template #body="{ data }">{{ formatCurrency(data.imp_incidencia) }}</template>
                        </Column>
                        <Column header="Salario Final" field="salario_final" sortable style="min-width: 120px">
                            <template #body="{ data }">
                                <Tag :value="formatCurrency(data.salario_final)" :severity="getTagSeverity(data.salario_final)" />
                            </template>
                        </Column>

                        <!-- Fila expandida: detalle -->
                        <template #expansion="{ data }">
                            <div class="p-4 bg-gray-50 dark:bg-gray-900">
                                <!-- Fila: Acciones rápidas -->
                                <div class="flex gap-2 mb-4">
                                    <Button label="Editar Datos" icon="pi pi-pencil" size="small" @click="openEdit(data)" />
                                    <template v-if="data.es_turnos">
                                        <Button label="Abrir Calendario de Turnos" icon="pi pi-calendar"
                                            size="small" severity="secondary" @click="abrirTurno(data)" />
                                        <span class="text-xs text-gray-500 self-center">
                                            Empleado por turnos — registre los días trabajados en el calendario.
                                        </span>
                                    </template>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Tiempos</h4>
                                        <div class="text-sm space-y-1">
                                            <div>Regular: <span class="font-mono">{{ formatNum(data.regular) }}</span></div>
                                            <div>Irregular: <span class="font-mono">{{ formatNum(data.irregular) }}</span></div>
                                            <div>T. Total: <span class="font-mono">{{ formatNum(data.ttotal) }}</span></div>
                                            <div>T. Mes: <span class="font-mono">{{ data.tiempo_mes }}</span></div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Importes Base</h4>
                                        <div class="text-sm space-y-1">
                                            <div>Imp. Regular: <span class="font-mono">{{ formatCurrency(data.imp_regular) }}</span></div>
                                            <div>Imp. Irregular: <span class="font-mono">{{ formatCurrency(data.imp_irregular) }}</span></div>
                                            <div>Imp. CLA: <span class="font-mono">{{ formatCurrency(data.imp_cla) }}</span></div>
                                            <div>Imp. Base: <span class="font-mono font-bold">{{ formatCurrency(data.imp_base) }}</span></div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Adicionales</h4>
                                        <div class="text-sm space-y-1">
                                            <div>Nocturnidad: <span class="font-mono">{{ formatCurrency(data.imp_nocturnidad) }}</span></div>
                                            <div>Doblaje: <span class="font-mono">{{ formatCurrency(data.imp_doblaje) }}</span></div>
                                            <div>H. Extra: <span class="font-mono">{{ formatCurrency(data.imp_h_extra) }}</span></div>
                                            <div>Maestrías: <span class="font-mono">{{ formatCurrency(data.imp_maestrias) }}</span></div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Otros</h4>
                                        <div class="text-sm space-y-1">
                                            <div>Incidencias: <span class="font-mono">{{ formatCurrency(data.imp_incidencia) }}</span></div>
                                            <div>Feriados: <span class="font-mono">{{ formatCurrency(data.imp_feriados) }}</span></div>
                                            <div>Garantía: <span class="font-mono">{{ formatCurrency(data.imp_garantia) }}</span></div>
                                            <div>Adicionales: <span class="font-mono">{{ formatCurrency(data.padicionales) }}</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex gap-6">
                                    <div class="text-sm">
                                        <span class="text-gray-500">Nocturnidad 1:</span>
                                        <span class="font-mono ml-2">{{ formatNum(data.noct1) }}h × {{ data.tarifa > 0 ? '0.60' : '0' }}</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-500">Nocturnidad 2:</span>
                                        <span class="font-mono ml-2">{{ formatNum(data.noct2) }}h × {{ data.tarifa > 0 ? '1.15' : '0' }}</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-500">Doblaje:</span>
                                        <span class="font-mono ml-2">{{ formatNum(data.tdoblaje) }}h</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-500">Días Taller:</span>
                                        <span class="font-mono ml-2">{{ formatNum(data.dias_taller) }}</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-500">Norma Salarial:</span>
                                        <span class="font-mono ml-2">{{ data.norma_salarial }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Dialog para editar datos manuales -->
        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Datos' : 'Registrar Datos'" modal style="width: 500px">
            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Irregular (horas)</label>
                        <InputNumber v-model="form.irregular" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Feriados (días)</label>
                        <InputNumber v-model="form.feriados" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Días Taller</label>
                        <InputNumber v-model="form.dias_taller" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" :max="24" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Horas Extra</label>
                        <InputNumber v-model="form.h_extra" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Imp. Horas Extra</label>
                        <InputNumber v-model="form.imp_h_extra" class="w-full" :minFractionDigits="2" :maxFractionDigits="2" />
                    </div>
                </div>
                <div>
                    <label class="block mb-1 font-medium">Observaciones</label>
                    <Textarea v-model="form.observaciones" class="w-full" rows="2" />
                </div>
                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showForm = false" />
                    <Button label="Guardar" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>

        <!-- Dialog Calendario de Turnos -->
        <Dialog v-model:visible="showTurnoCalendar" :header="`Turnos — ${turnoEmpleado?.nombre_completo || ''}`"
            modal :style="{ width: '90vw', maxWidth: '1100px' }" :closable="!turnoGuardando">
            <div v-if="turnoEmpleado" class="space-y-4">
                <!-- Resumen -->
                <div class="flex flex-wrap gap-4 text-sm p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Tarifa:</span>
                        <span class="font-mono ml-1 font-bold">{{ formatNum(turnoEmpleado.tarifa) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Tiempo total:</span>
                        <span class="font-mono ml-1 font-bold">{{ formatNum(turnoResumen().totalTiempo) }}h</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Noct1 (7pm-11pm):</span>
                        <span class="font-mono ml-1 font-bold text-purple-600">{{ formatNum(turnoResumen().totalNoct1) }}h</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Noct2 (11pm-7am):</span>
                        <span class="font-mono ml-1 font-bold text-indigo-600">{{ formatNum(turnoResumen().totalNoct2) }}h</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Doblaje:</span>
                        <span class="font-mono ml-1 font-bold text-orange-600">{{ formatNum(turnoResumen().totalDoblaje) }}h</span>
                    </div>
                </div>

                <!-- Calendario por semanas -->
                <div v-for="(dias, semana) in semanaDias" :key="semana" class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                    <div class="bg-blue-50 dark:bg-blue-900/30 px-3 py-1 text-xs font-semibold text-blue-700 dark:text-blue-300">
                        Semana {{ semana }}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600">
                                    <th class="px-2 py-1 text-left w-8">Día</th>
                                    <th class="px-2 py-1 text-center w-10">Lun</th>
                                    <th class="px-2 py-1 text-center w-10">Mar</th>
                                    <th class="px-2 py-1 text-center w-10">Mié</th>
                                    <th class="px-2 py-1 text-center w-10">Jue</th>
                                    <th class="px-2 py-1 text-center w-10">Vie</th>
                                    <th class="px-2 py-1 text-center w-10">Sáb</th>
                                    <th class="px-2 py-1 text-center w-10">Dom</th>
                                    <th class="px-2 py-1 text-right w-14">Tiempo</th>
                                    <th class="px-2 py-1 text-right w-14">Noct1</th>
                                    <th class="px-2 py-1 text-right w-14">Noct2</th>
                                    <th class="px-2 py-1 text-right w-14">Doblaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="dia in dias" :key="dia.dia"
                                    class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                    :class="{ 'bg-gray-50 dark:bg-gray-800/30': dia.dia % 2 === 0 }">
                                    <td class="px-2 py-1 font-semibold text-gray-700 dark:text-gray-300">{{ dia.dia }}</td>
                                    <td v-for="(_, idx) in 7" :key="idx" class="px-1 py-0.5 text-center text-gray-400 text-xs">
                                        {{ dias[idx] ? dias[idx].dia : '' }}
                                    </td>
                                    <td class="px-1 py-0.5">
                                        <input type="number" v-model.number="dia.tiempo" min="0" max="24" step="0.5"
                                            class="w-14 text-center border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-blue-500" />
                                    </td>
                                    <td class="px-1 py-0.5">
                                        <input type="number" v-model.number="dia.noct1" min="0" max="24" step="0.5"
                                            class="w-14 text-center border border-purple-300 dark:border-purple-600 rounded px-1 py-0.5 bg-purple-50 dark:bg-purple-900/20 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-purple-500" />
                                    </td>
                                    <td class="px-1 py-0.5">
                                        <input type="number" v-model.number="dia.noct2" min="0" max="24" step="0.5"
                                            class="w-14 text-center border border-indigo-300 dark:border-indigo-600 rounded px-1 py-0.5 bg-indigo-50 dark:bg-indigo-900/20 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-1 py-0.5">
                                        <input type="number" v-model.number="dia.doblaje" min="0" max="24" step="0.5"
                                            class="w-14 text-center border border-orange-300 dark:border-orange-600 rounded px-1 py-0.5 bg-orange-50 dark:bg-orange-900/20 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-orange-500" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">
                        Solo se guardan días con datos (tiempo, nocturnidad o doblaje > 0).
                    </span>
                    <div class="flex gap-2">
                        <Button label="Cancelar" severity="secondary" @click="showTurnoCalendar = false" :disabled="turnoGuardando" />
                        <Button label="Guardar Turnos" icon="pi pi-save" @click="guardarTurnos" :loading="turnoGuardando" />
                    </div>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
