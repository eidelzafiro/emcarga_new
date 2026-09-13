<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Toolbar from 'primevue/toolbar'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import { formatDate } from '@/Utils/date'

const props = defineProps({ facturas: Object, filters: Object })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || '')
const seleccionadas = ref([])

const severityMap = { emitida: 'info', firmada: 'warn', cobrada: 'success', cancelada: 'danger', refacturada: 'warn' }

// Exporta a CSV: todas las filtradas o solo las seleccionadas.
function exportar(ids = []) {
    const params = new URLSearchParams()
    ids.forEach((id) => params.append('ids[]', id))
    const qs = ids.length ? '?' + params.toString() : ''
    window.location = route('facturas.exportar') + qs
}

function exportarSeleccionadas() {
    exportar(seleccionadas.value.map((f) => f.id))
}

// Impresión del reporte legacy "factura" (id 13) de una factura.
function imprimir(factura) {
    window.open(route('reportes.generar', 13) + '?filtros[factura]=' + factura.id, '_blank')
}

watch([search, estado], () => {
    router.get(route('facturas.index'), { search: search.value, estado: estado.value }, { preserveState: true, replace: true })
})

function confirmCancelar(factura) {
    confirm.require({
        message: `¿Cancelar factura ${factura.numero}? Se desvincularán las cartas porte.`,
        header: 'Cancelar Factura',
        icon: 'pi pi-exclamation-triangle',
        accept: () => router.post(route('facturas.cancelar', factura.id), { _method: 'post' }, { preserveScroll: true })
    })
}

function confirmRefacturar(factura) {
    confirm.require({
        message: `¿Refacturar factura ${factura.numero}? Se desvincularán las cartas porte para refacturar.`,
        header: 'Refacturar Factura',
        icon: 'pi pi-refresh',
        accept: () => router.post(route('facturas.refacturar', factura.id), { _method: 'post' }, { preserveScroll: true })
    })
}

function cobrar(factura) {
    const hoy = new Date().toISOString().split('T')[0]
    confirm.require({
        message: `¿Marcar factura ${factura.numero} como cobrada hoy?`,
        header: 'Cobrar Factura',
        icon: 'pi pi-dollar',
        accept: () => router.post(route('facturas.cobrar', factura.id), { fecha_cobro_mn: hoy }, { preserveScroll: true })
    })
}

function firmar(factura) {
    confirm.require({
        message: `¿Marcar factura ${factura.numero} como firmada?`,
        header: 'Firmar Factura',
        icon: 'pi pi-check',
        accept: () => router.post(route('facturas.firmar', factura.id), { _method: 'post' }, { preserveScroll: true })
    })
}

function confirmEliminar(factura) {
    confirm.require({
        message: `¿Eliminar factura ${factura.numero}?`,
        header: 'Eliminar Factura',
        icon: 'pi pi-trash',
        accept: () => router.delete(route('facturas.destroy', factura.id), { preserveScroll: true })
    })
}
</script>

<template>
    <AppLayout :title="title">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nueva Factura" icon="pi pi-plus" severity="success" @click="router.get(route('facturas.create'))" />
                </template>
                <template #end>
                    <div class="flex gap-2">
                        <Button label="Exportar todo" icon="pi pi-download" severity="info" @click="exportar()" />
                        <Button label="Exportar seleccionadas" icon="pi pi-download" severity="success" :disabled="!seleccionadas.length" @click="exportarSeleccionadas" />
                        <InputText v-model="search" placeholder="Buscar..." />
                        <Select v-model="estado" :options="['', 'emitida', 'firmada', 'cobrada', 'cancelada', 'refacturada']" placeholder="Estado" class="w-40" />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="facturas.data" :loading="false" striped-rows paginator :rows="20" :total-records="facturas.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros"
                v-model:selection="seleccionadas" selectionMode="multiple" dataKey="id"
                rowGroupMode="subheader" groupRowsBy="fecha_emision">
                <template #groupheader="{ data }">
                    <span class="font-bold text-blue-700 dark:text-blue-300">Facturación del {{ formatDate(data.fecha_emision) }}</span>
                </template>
                <Column field="numero" header="No. Factura" sortable />
                <Column field="cliente.nombre" header="Cliente" sortable />
                <Column field="fecha_emision" header="Fecha Emisión" sortable>
                    <template #body="{ data }">{{ formatDate(data.fecha_emision) }}</template>
                </Column>
                <Column field="ingreso_mt" header="Total MN">
                    <template #body="{ data }">${{ Number(data.ingreso_mt).toLocaleString() }}</template>
                </Column>
                <Column field="estado" header="Estado">
                    <template #body="{ data }">
                        <Tag :severity="severityMap[data.estado] || 'info'">{{ data.estado }}</Tag>
                    </template>
                </Column>
                <Column header="Acciones" style="width: 250px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-eye" rounded text severity="info" @click="router.get(route('facturas.show', data.id))" v-tooltip.top="'Ver'" />
                            <Button icon="pi pi-print" rounded text severity="success" @click="imprimir(data)" v-tooltip.top="'Imprimir factura'" />
                            <Button v-if="data.estado === 'emitida'" icon="pi pi-check" rounded text severity="success" @click="firmar(data)" v-tooltip.top="'Firmar'" />
                            <Button v-if="data.estado === 'emitida'" icon="pi pi-dollar" rounded text severity="warn" @click="cobrar(data)" v-tooltip.top="'Cobrar'" />
                            <Button v-if="data.estado === 'emitida'" icon="pi pi-refresh" rounded text severity="warn" @click="confirmRefacturar(data)" v-tooltip.top="'Refacturar'" />
                            <Button v-if="data.estado === 'emitida'" icon="pi pi-times" rounded text severity="danger" @click="confirmCancelar(data)" v-tooltip.top="'Cancelar'" />
                            <Button v-if="data.estado === 'emitida'" icon="pi pi-trash" rounded text severity="danger" @click="confirmEliminar(data)" v-tooltip.top="'Eliminar'" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
        <ConfirmDialog />
    </AppLayout>
</template>
