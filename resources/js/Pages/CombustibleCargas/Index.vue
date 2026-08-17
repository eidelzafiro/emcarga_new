<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ cargas: Object, filters: Object, tiposCombustibles: Array, monedas: Array, filtros: Object, fechaOperaciones: String })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref(emptyForm())
const title = 'Carga Combustible'

function emptyForm() {
    return {
        fcarga: new Date(props.fechaOperaciones || new Date()),
        folio: '',
        saldocargado: null,
        saldoxtarjeta: null,
        id_monedas: null,
        id_tipo_combustibles: null,
        id_responsable: null,
        notas: '',
        detalles: [],
    }
}

function nuevoDetalle() {
    return { id: null, id_tarjeta: null, saldo_mon: null }
}

watch(search, () => {
    router.get(route('combustible-cargas.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = emptyForm()
    form.value.detalles = [nuevoDetalle()]
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        fcarga: new Date(item.fcarga),
        folio: item.folio,
        saldocargado: Number(item.saldocargado),
        saldoxtarjeta: Number(item.saldoxtarjeta),
        id_monedas: item.id_monedas,
        id_tipo_combustibles: item.id_tipo_combustibles,
        id_responsable: item.id_responsable,
        notas: item.notas || '',
        detalles: (item.detalles || []).map((d) => ({ id: d.id, id_tarjeta: d.id_tarjeta, saldo_mon: Number(d.saldo_mon) })),
    }
    showForm.value = true
}

function addDetalle() {
    form.value.detalles.push(nuevoDetalle())
}

function removeDetalle(idx) {
    form.value.detalles.splice(idx, 1)
}

function submit() {
    const url = editing.value ? route('combustible-cargas.update', editing.value.id) : route('combustible-cargas.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}

function confirmDelete(item) {
    confirm.require({
        message: `¿Eliminar la carga ${item.folio}?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        accept: () => router.delete(route('combustible-cargas.destroy', item.id), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminada', life: 3000 }),
        }),
    })
}

const fmt = (n) => n?.toLocaleString('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>

<template>
    <AppLayout :title="title">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
                </template>
                <template #end>
                    <div class="flex gap-2 items-center">
                        <Select v-model="filters.id_tipo_combustible" :options="[{id:null,nombre:'Todos'}, ...tiposCombustibles]" optionLabel="nombre" optionValue="id" placeholder="Tipo" class="w-48" @change="router.get(route('combustible-cargas.index'), { id_tipo_combustible: filters.id_tipo_combustible }, { preserveState: true, replace: true })" />
                        <InputText v-model="search" placeholder="Buscar folio/tarjeta..." />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="cargas.data" striped-rows paginator :rows="20" :total-records="cargas.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="fcarga" header="Fecha" sortable />
                <Column field="folio" header="Folio" sortable />
                <Column field="tipoCombustible.nombre" header="Combustible" />
                <Column field="moneda.codigo" header="Moneda" />
                <Column field="responsable.nombre" header="Responsable">
                    <template #body="{ data }">{{ data.responsable?.nombre }} {{ data.responsable?.apellidos }}</template>
                </Column>
                <Column field="saldocargado" header="Saldo Cargado">
                    <template #body="{ data }">{{ fmt(data.saldocargado) }}</template>
                </Column>
                <Column field="saldoxtarjeta" header="Saldo x Tarjeta">
                    <template #body="{ data }">{{ fmt(data.saldoxtarjeta) }}</template>
                </Column>
                <Column header="Tarjetas">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-1">
                            <Tag v-for="d in data.detalles" :key="d.id" :value="`${d.tarjeta?.numero}: ${fmt(d.saldo_mon)} (${fmt(d.saldo_lts)} L)`" severity="info" />
                        </div>
                    </template>
                </Column>
                <Column field="estado" header="Estado">
                    <template #body="{ data }">
                        <Tag :value="data.estado" :severity="data.estado === 'registrada' ? 'success' : 'warn'" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Carga' : 'Nueva Carga'" modal style="width: 720px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Fecha</label>
                        <DatePicker v-model="form.fcarga" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Folio</label>
                        <InputText v-model="form.folio" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Combustible</label>
                        <Select v-model="form.id_tipo_combustibles" :options="tiposCombustibles" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Moneda</label>
                        <Select v-model="form.id_monedas" :options="monedas" optionLabel="codigo" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Responsable</label>
                        <Select v-model="form.id_responsable" :options="filtros.empleados" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Saldo Cargado</label>
                        <InputNumber v-model="form.saldocargado" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                </div>

                <div class="border rounded p-3">
                    <div class="flex justify-between items-center mb-2">
                        <label class="font-medium">Detalle por tarjeta</label>
                        <Button label="Añadir tarjeta" icon="pi pi-plus" size="small" severity="info" @click="addDetalle" />
                    </div>
                    <div v-for="(d, idx) in form.detalles" :key="idx" class="flex gap-2 items-center mb-2">
                        <Select v-model="d.id_tarjeta" :options="filtros.tarjetas" optionLabel="numero" optionValue="id" placeholder="Tarjeta" class="flex-1" />
                        <InputNumber v-model="d.saldo_mon" :minFractionDigits="2" :maxFractionDigits="2" placeholder="Saldo (MN)" class="w-40" />
                        <Button icon="pi pi-times" rounded text severity="danger" @click="removeDetalle(idx)" />
                    </div>
                </div>

                <div>
                    <label class="block mb-1 font-medium">Notas</label>
                    <Textarea v-model="form.notas" rows="2" class="w-full" />
                </div>

                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showForm = false" />
                    <Button label="Guardar" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
