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

const props = defineProps({ otros_gastos: Object, filters: Object, bolsa: Array, tractivos: Array, tipos_concepto: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_bolsa: null, id_tractivo: null, id_tipo_concepto: null, fecha: null, concepto: '', monto_mn: null, monto_mlc: null, descripcion: '' })
const title = 'Otros Gastos'

watch(search, () => {
    router.get(route('otros-gastos.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { id_bolsa: null, id_tractivo: null, id_tipo_concepto: null, fecha: null, concepto: '', monto_mn: null, monto_mlc: null, descripcion: '' }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        id_bolsa: item.id_bolsa,
        id_tractivo: item.id_tractivo,
        id_tipo_concepto: item.id_tipo_concepto,
        fecha: item.fecha ? new Date(item.fecha) : null,
        concepto: item.concepto || '',
        monto_mn: item.monto_mn,
        monto_mlc: item.monto_mlc,
        descripcion: item.descripcion || '',
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('otros-gastos.update', editing.value.id) : route('otros-gastos.store')
    const method = editing.value ? 'put' : 'post'
    router[method](url, form.value, {
        onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    })
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
                    <InputText v-model="search" placeholder="Buscar..." />
                </template>
            </Toolbar>

            <DataTable :value="otros_gastos.data" striped-rows paginator :rows="20" :total-records="otros_gastos.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
                <Column field="concepto" header="Concepto" sortable />
                <Column field="bolsa.nombre" header="Bolsa" />
                <Column field="tractivo.descripcion" header="Tractivo" />
                <Column field="tipo_concepto.nombre" header="Tipo Concepto" />
                <Column field="fecha" header="Fecha" sortable />
                <Column field="monto_mn" header="Monto MN">
                    <template #body="{ data }">
                        {{ data.monto_mn?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column field="monto_mlc" header="Monto MLC">
                    <template #body="{ data }">
                        {{ data.monto_mlc?.toLocaleString('es-CU', { minimumFractionDigits: 2 }) }}
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('otros-gastos.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Otro Gasto' : 'Nuevo Otro Gasto'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Bolsa</label>
                        <Select v-model="form.id_bolsa" :options="bolsa" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tractivo</label>
                        <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="descripcion" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tipo Concepto</label>
                        <Select v-model="form.id_tipo_concepto" :options="tipos_concepto" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha</label>
                        <DatePicker v-model="form.fecha" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Concepto</label>
                        <InputText v-model="form.concepto" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Monto MN</label>
                        <InputNumber v-model="form.monto_mn" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Monto MLC</label>
                        <InputNumber v-model="form.monto_mlc" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Descripción</label>
                        <Textarea v-model="form.descripcion" class="w-full" rows="3" />
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
