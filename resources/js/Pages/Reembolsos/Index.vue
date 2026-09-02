<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ reembolsos: Object, bolsas: Array, filters: Object, fechaOperaciones: String })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const estadoFilter = ref(props.filters?.estado || null)
const showForm = ref(false)
const editing = ref(null)
const title = 'Reembolsos'

const estados = [
    { label: 'Pendiente', value: 'pendiente' },
    { label: 'Aprobado', value: 'aprobado' },
    { label: 'Rechazado', value: 'rechazado' },
]

function baseForm() {
    return { id_bolsa: null, fecha: null, monto: null, concepto: '', documentos: '' }
}
const form = ref(baseForm())

watch(search, () => reload())
watch(estadoFilter, () => reload())

function reload() {
    router.get(route('reembolsos.index'), {
        search: search.value,
        estado: estadoFilter.value || '',
    }, { preserveState: true, replace: true })
}

function onPage(event) {
    router.get(route('reembolsos.index'), {
        page: event.page + 1,
        search: search.value,
        estado: estadoFilter.value || '',
    }, { preserveState: true, replace: true })
}

function fmtFecha(fecha) {
    if (!fecha) return ''
    const d = new Date(fecha)
    return d.toLocaleDateString('es-CU', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function openCreate() {
    editing.value = null
    form.value = baseForm()
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        id_bolsa: item.id_bolsa,
        fecha: item.fecha ? new Date(item.fecha) : null,
        monto: Number(item.monto),
        concepto: item.concepto || '',
        documentos: item.documentos || '',
    }
    showForm.value = true
}

function submit() {
    if (!form.value.id_bolsa) { toast.add({ severity: 'warn', summary: 'Seleccione un empleado', life: 3000 }); return }
    if (!form.value.fecha) { toast.add({ severity: 'warn', summary: 'Seleccione una fecha', life: 3000 }); return }
    if (!form.value.concepto) { toast.add({ severity: 'warn', summary: 'Ingrese el concepto', life: 3000 }); return }

    const payload = {
        id_bolsa: form.value.id_bolsa,
        fecha: form.value.fecha instanceof Date ? form.value.fecha.toISOString().split('T')[0] : form.value.fecha,
        monto: form.value.monto ?? 0,
        concepto: form.value.concepto,
        documentos: form.value.documentos || '',
    }

    const url = editing.value ? route('reembolsos.update', editing.value.id) : route('reembolsos.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, payload, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
        onError: (e) => {
            const msgs = Object.values(e).flat().join(', ')
            toast.add({ severity: 'error', summary: 'Error', detail: msgs || 'Error de validación', life: 5000 })
        },
    })
}

function confirmAprobar(item) {
    confirm.require({
        message: `¿Aprobar el reembolso de ${item.bolsa?.nombrecompleto ?? ''}?`,
        header: 'Aprobar reembolso',
        acceptLabel: 'Si, aprobar',
        rejectLabel: 'No',
        accept: () => {
            router.post(route('reembolsos.aprobar', item.id), {}, {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Aprobado', life: 3000 }),
            })
        },
    })
}

function confirmRechazar(item) {
    confirm.require({
        message: `¿Rechazar el reembolso de ${item.bolsa?.nombrecompleto ?? ''}?`,
        header: 'Rechazar reembolso',
        acceptLabel: 'Si, rechazar',
        rejectLabel: 'No',
        accept: () => {
            router.post(route('reembolsos.rechazar', item.id), {}, {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Rechazado', life: 3000 }),
            })
        },
    })
}

function confirmEliminar(item) {
    confirm.require({
        message: `¿Eliminar este reembolso?`,
        header: 'Eliminar reembolso',
        acceptLabel: 'Si, eliminar',
        rejectLabel: 'No',
        accept: () => {
            router.delete(route('reembolsos.destroy', item.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminado', life: 3000 }),
            })
        },
    })
}

function severityEstado(estado) {
    return { pendiente: 'warn', aprobado: 'success', rechazado: 'danger' }[estado] || 'secondary'
}
</script>

<template>
    <AppLayout :title="title">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
                </template>
                <template #end>
                    <div class="flex items-center gap-3">
                        <Select v-model="estadoFilter" :options="estados" optionLabel="label" optionValue="value" placeholder="Todos los estados" :showClear="true" class="w-48" />
                        <InputText v-model="search" placeholder="Buscar..." />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="reembolsos.data" striped-rows paginator :rows="20" :total-records="reembolsos.total" :lazy="true" :first="(reembolsos.current_page - 1) * reembolsos.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column header="Fecha">
                    <template #body="{ data }">{{ fmtFecha(data.fecha) }}</template>
                </Column>
                <Column header="Empleado">
                    <template #body="{ data }">{{ data.bolsa?.nombrecompleto }}</template>
                </Column>
                <Column field="concepto" header="Concepto" show-overflow-tooltip />
                <Column field="monto" header="Monto">
                    <template #body="{ data }">{{ Number(data.monto).toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}</template>
                </Column>
                <Column field="documentos" header="Documentos" show-overflow-tooltip />
                <Column header="Estado">
                    <template #body="{ data }">
                        <Tag :severity="severityEstado(data.estado)" :value="data.estado" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 180px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button v-if="data.estado === 'pendiente'" icon="pi pi-pencil" rounded text severity="info" title="Editar" @click="openEdit(data)" />
                            <Button v-if="data.estado === 'pendiente'" icon="pi pi-check" rounded text severity="success" title="Aprobar" @click="confirmAprobar(data)" />
                            <Button v-if="data.estado === 'pendiente'" icon="pi pi-times" rounded text severity="warn" title="Rechazar" @click="confirmRechazar(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" title="Eliminar" @click="confirmEliminar(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Reembolso' : 'Nuevo Reembolso'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Empleado *</label>
                        <Select v-model="form.id_bolsa" :options="bolsas" optionLabel="nombrecompleto" optionValue="id" filter class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha *</label>
                        <DatePicker v-model="form.fecha" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Monto *</label>
                        <InputNumber v-model="form.monto" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Concepto *</label>
                        <Textarea v-model="form.concepto" class="w-full" rows="3" />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Documentos</label>
                        <InputText v-model="form.documentos" class="w-full" />
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showForm = false" />
                    <Button label="Guardar" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
