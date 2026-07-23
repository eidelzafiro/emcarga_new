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

const props = defineProps({ descargas: Object, filters: Object, cargas: Array, tractivos: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ id_carga: null, id_tractivo: null, fecha_descarga: null, cantidad_litros: null, kilometraje: null, tipo_combustible: null })
const title = 'Descargas de Combustible'

const tiposCombustible = [
    { label: 'Diesel', value: 'diesel' },
    { label: 'Gasolina', value: 'gasolina' },
    { label: 'Gas', value: 'gas' },
]

watch(search, () => {
    router.get(route('combustible-descargas.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { id_carga: null, id_tractivo: null, fecha_descarga: null, cantidad_litros: null, kilometraje: null, tipo_combustible: null }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        id_carga: item.id_carga,
        id_tractivo: item.id_tractivo,
        fecha_descarga: item.fecha_descarga ? new Date(item.fecha_descarga) : null,
        cantidad_litros: item.cantidad_litros,
        kilometraje: item.kilometraje,
        tipo_combustible: item.tipo_combustible,
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

            <DataTable :value="descargas.data" striped-rows paginator :rows="20" :total-records="descargas.total">
                <Column field="carga.numero" header="Carga" />
                <Column field="tractivo.descripcion" header="Tractivo" />
                <Column field="fecha_descarga" header="Fecha Descarga" sortable />
                <Column field="cantidad_litros" header="Litros" />
                <Column field="kilometraje" header="Kilometraje" />
                <Column field="tipo_combustible" header="Tipo">
                    <template #body="{ data }">
                        <Tag :value="data.tipo_combustible" :severity="data.tipo_combustible === 'diesel' ? 'warn' : data.tipo_combustible === 'gasolina' ? 'info' : 'success'" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('combustible-descargas.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Descarga' : 'Nueva Descarga'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Carga</label>
                        <Select v-model="form.id_carga" :options="cargas" optionLabel="numero" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tractivo</label>
                        <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="descripcion" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha Descarga</label>
                        <DatePicker v-model="form.fecha_descarga" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Cantidad (Litros)</label>
                        <InputNumber v-model="form.cantidad_litros" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Kilometraje</label>
                        <InputNumber v-model="form.kilometraje" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tipo Combustible</label>
                        <Select v-model="form.tipo_combustible" :options="tiposCombustible" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
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
