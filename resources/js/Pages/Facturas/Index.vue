<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
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

const props = defineProps({ facturas: Object, filters: Object })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || '')

const severityMap = { emitida: 'info', firmada: 'warn', cobrada: 'success', cancelada: 'danger', refacturada: 'warn' }

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
                        <InputText v-model="search" placeholder="Buscar..." />
                        <Select v-model="estado" :options="['', 'emitida', 'firmada', 'cobrada', 'cancelada', 'refacturada']" placeholder="Estado" class="w-40" />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="facturas.data" :loading="false" striped-rows paginator :rows="20" :total-records="facturas.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="numero" header="No. Factura" sortable />
                <Column field="cliente.nombre" header="Cliente" sortable />
                <Column field="fecha_emision" header="Fecha Emisión" sortable>
                    <template #body="{ data }">{{ data.fecha_emision }}</template>
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
