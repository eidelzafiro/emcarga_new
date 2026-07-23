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
import DatePicker from 'primevue/datepicker'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ vales: Object, filters: Object, bolsa: Array, tractivos: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ numero: '', id_bolsa: null, id_tractivo: null, fecha_emision: null, tipo: null, concepto: '', estado: null })
const title = 'Vales'

const tipos = [
    { label: 'Almacén', value: 'almacen' },
    { label: 'Combustible', value: 'combustible' },
    { label: 'Repuesto', value: 'repuesto' },
]

const estados = [
    { label: 'Pendiente', value: 'pendiente' },
    { label: 'Aprobado', value: 'aprobado' },
    { label: 'Rechazado', value: 'rechazado' },
    { label: 'Cancelado', value: 'cancelado' },
]

watch(search, () => {
    router.get(route('vales.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { numero: '', id_bolsa: null, id_tractivo: null, fecha_emision: null, tipo: null, concepto: '', estado: null }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        numero: item.numero,
        id_bolsa: item.id_bolsa,
        id_tractivo: item.id_tractivo,
        fecha_emision: item.fecha_emision ? new Date(item.fecha_emision) : null,
        tipo: item.tipo,
        concepto: item.concepto || '',
        estado: item.estado,
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('vales.update', editing.value.id) : route('vales.store')
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

            <DataTable :value="vales.data" striped-rows paginator :rows="20" :total-records="vales.total">
                <Column field="numero" header="Número" sortable />
                <Column field="bolsa.nombre" header="Bolsa" />
                <Column field="tractivo.descripcion" header="Tractivo" />
                <Column field="fecha_emision" header="Fecha Emisión" sortable />
                <Column field="tipo" header="Tipo">
                    <template #body="{ data }">
                        <Tag :value="data.tipo" :severity="data.tipo === 'combustible' ? 'warn' : data.tipo === 'repuesto' ? 'info' : 'success'" />
                    </template>
                </Column>
                <Column field="concepto" header="Concepto" />
                <Column field="estado" header="Estado">
                    <template #body="{ data }">
                        <Tag :value="data.estado" :severity="data.estado === 'aprobado' ? 'success' : data.estado === 'rechazado' || data.estado === 'cancelado' ? 'danger' : 'warn'" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('vales.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Vale' : 'Nuevo Vale'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Número</label>
                        <InputText v-model="form.numero" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Bolsa</label>
                        <Select v-model="form.id_bolsa" :options="bolsa" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tractivo</label>
                        <Select v-model="form.id_tractivo" :options="tractivos" optionLabel="descripcion" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha Emisión</label>
                        <DatePicker v-model="form.fecha_emision" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tipo</label>
                        <Select v-model="form.tipo" :options="tipos" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Estado</label>
                        <Select v-model="form.estado" :options="estados" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Concepto</label>
                        <Textarea v-model="form.concepto" class="w-full" rows="3" />
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
