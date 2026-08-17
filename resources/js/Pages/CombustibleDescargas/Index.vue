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
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ descargas: Object, filters: Object, filtros: Object, fechaOperaciones: String })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref(emptyForm())
const title = 'Descarga Combustible'

function emptyForm() {
    return {
        id_tarjeta: null,
        fdescarga: new Date(props.fechaOperaciones || new Date()),
        folio: '',
        saldo_mon: null,
        id_hoja_ruta: null,
        hora_descarga: '',
        id_servicentro: null,
        f_chip: null,
        kms: null,
    }
}

watch(search, () => {
    router.get(route('combustible-descargas.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = emptyForm()
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        id_tarjeta: item.id_tarjeta,
        fdescarga: new Date(item.fdescarga),
        folio: item.folio,
        saldo_mon: Number(item.saldo_mon),
        id_hoja_ruta: item.id_hoja_ruta,
        hora_descarga: item.hora_descarga || '',
        id_servicentro: item.id_servicentro,
        f_chip: item.f_chip ? new Date(item.f_chip) : null,
        kms: item.kms,
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('combustible-descargas.update', editing.value.id) : route('combustible-descargas.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}

function confirmDelete(item) {
    confirm.require({
        message: `¿Eliminar la descarga ${item.folio}?`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        accept: () => router.delete(route('combustible-descargas.destroy', item.id), {
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
                        <Select v-model="filters.id_tarjeta" :options="filtros.tarjetas" optionLabel="numero" optionValue="id" placeholder="Tarjeta" class="w-44" @change="router.get(route('combustible-descargas.index'), { id_tarjeta: filters.id_tarjeta }, { preserveState: true, replace: true })" />
                        <Select v-model="filters.id_servicentro" :options="filtros.servicentros" optionLabel="nombre" optionValue="id" placeholder="Servicentro" class="w-56" @change="router.get(route('combustible-descargas.index'), { id_servicentro: filters.id_servicentro }, { preserveState: true, replace: true })" />
                        <InputText v-model="search" placeholder="Buscar folio/tarjeta/HR..." />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="descargas.data" striped-rows paginator :rows="20" :total-records="descargas.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="fdescarga" header="Fecha" sortable />
                <Column field="folio" header="Folio" sortable />
                <Column field="tarjeta.numero" header="Tarjeta" />
                <Column field="hojaRuta.numero" header="Hoja Ruta" />
                <Column field="hojaRuta.tractivo.codigo" header="Tractivo" />
                <Column field="saldo_mon" header="Saldo MN">
                    <template #body="{ data }">{{ fmt(data.saldo_mon) }}</template>
                </Column>
                <Column field="saldo_lts" header="Saldo Lts">
                    <template #body="{ data }">{{ fmt(data.saldo_lts) }}</template>
                </Column>
                <Column field="servicentro.nombre" header="Servicentro" />
                <Column field="kms" header="Kms">
                    <template #body="{ data }">{{ fmt(data.kms) }}</template>
                </Column>
                <Column field="hora_descarga" header="Hora" />
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

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Descarga' : 'Nueva Descarga'" modal style="width: 680px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Tarjeta</label>
                        <Select v-model="form.id_tarjeta" :options="filtros.tarjetas" optionLabel="numero" optionValue="id" placeholder="Seleccione..." class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha Descarga</label>
                        <DatePicker v-model="form.fdescarga" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Folio</label>
                        <InputText v-model="form.folio" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Hoja de Ruta</label>
                        <Select v-model="form.id_hoja_ruta" :options="filtros.hojasRuta" optionLabel="numero" optionValue="id" placeholder="Seleccione..." class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Saldo MN</label>
                        <InputNumber v-model="form.saldo_mon" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Servicentro</label>
                        <Select v-model="form.id_servicentro" :options="filtros.servicentros" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Kms</label>
                        <InputNumber v-model="form.kms" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Hora Descarga</label>
                        <InputText v-model="form.hora_descarga" placeholder="HH:MM" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha Chip</label>
                        <DatePicker v-model="form.f_chip" dateFormat="dd/mm/yy" class="w-full" />
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
