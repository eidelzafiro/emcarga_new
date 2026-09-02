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
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ gastos: Object, tractivos: Array, fechaOperaciones: String, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

function baseForm() {
    return {
        fecha: null,
        id_tractivo: null,
        nombre: '',
        elemento: '',
        cantidad: null,
        valor_mn: null,
        el_gas_mn: '',
        cup: '',
        nodoc: null,
        observaciones: '',
    }
}
const form = ref(baseForm())

watch(search, () => reload())

function reload() {
    router.get(route('gasto-material.index'), { search: search.value }, { preserveState: true, replace: true })
}

function onPage(event) {
    router.get(route('gasto-material.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
    editing.value = null
    form.value = baseForm()
    form.value.fecha = new Date(props.fechaOperaciones)
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        fecha: item.fecha ? new Date(item.fecha) : null,
        id_tractivo: item.id_tractivo,
        nombre: item.nombre,
        elemento: item.elemento,
        cantidad: item.cantidad,
        valor_mn: item.valor_mn,
        el_gas_mn: item.el_gas_mn || '',
        cup: item.cup || '',
        nodoc: item.nodoc,
        observaciones: item.observaciones || '',
    }
    showForm.value = true
}

function submit() {
    if (!form.value.nombre) { toast.add({ severity: 'warn', summary: 'Nombre requerido', life: 3000 }); return }
    if (!form.value.elemento) { toast.add({ severity: 'warn', summary: 'Elemento requerido', life: 3000 }); return }
    if (form.value.cantidad === null) { toast.add({ severity: 'warn', summary: 'Cantidad requerida', life: 3000 }); return }
    if (form.value.valor_mn === null) { toast.add({ severity: 'warn', summary: 'Valor MN requerido', life: 3000 }); return }

    const payload = {
        ...form.value,
        fecha: form.value.fecha instanceof Date ? form.value.fecha.toISOString().split('T')[0] : form.value.fecha,
    }

    const url = editing.value ? route('gasto-material.update', editing.value.id) : route('gasto-material.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, payload, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
}
</script>

<template>
    <AppLayout :title="'Gasto Material'">
        <div class="card">
            <Toolbar class="mb-4">
                <template #start>
                    <div class="flex items-center gap-2">
                        <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
                        <IconField>
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="search" placeholder="Buscar..." @keyup.enter="reload" />
                        </IconField>
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="gastos.data" striped-rows paginator :rows="20" :total-records="gastos.total" :lazy="true" :first="(gastos.current_page - 1) * gastos.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords}">
                <Column field="fecha" header="Fecha">
                    <template #body="{ data }">{{ data.fecha ? new Date(data.fecha).toLocaleDateString('es-CU') : '' }}</template>
                </Column>
                <Column header="Tractivo">
                    <template #body="{ data }">{{ data.tractivo?.codigo }}</template>
                </Column>
                <Column field="nombre" header="Nombre" />
                <Column field="elemento" header="Elemento" show-overflow-tooltip />
                <Column field="cantidad" header="Cant." style="width: 80px" />
                <Column field="valor_mn" header="Valor MN" style="width: 100px">
                    <template #body="{ data }">{{ Number(data.valor_mn).toFixed(2) }}</template>
                </Column>
                <Column field="cup" header="CUP" />
                <Column field="nodoc" header="Doc." />
                <Column header="Acciones" style="width: 100px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('gasto-material.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Gasto' : 'Nuevo Gasto'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Fecha *</label>
                        <DatePicker v-model="form.fecha" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tractivo</label>
                        <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="codigo" optionValue="id" filter placeholder="Seleccione..." class="w-full" :showClear="true" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Nombre *</label>
                        <InputText v-model="form.nombre" maxlength="50" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Elemento *</label>
                        <InputText v-model="form.elemento" maxlength="70" class="w-full" />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Cantidad *</label>
                        <InputNumber v-model="form.cantidad" :max-fraction-digits="3" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Valor MN *</label>
                        <InputNumber v-model="form.valor_mn" :max-fraction-digits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">El. Gas MN</label>
                        <InputText v-model="form.el_gas_mn" maxlength="10" class="w-full" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">CUP</label>
                        <InputText v-model="form.cup" maxlength="15" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Núm. Documento</label>
                        <InputNumber v-model="form.nodoc" class="w-full" />
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
    </AppLayout>
</template>
