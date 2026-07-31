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
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, tiposModelo: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const tipoModeloFiltro = ref(props.filters?.codigo_tipo_modelo || null)
const showForm = ref(false)
const editing = ref(null)
const form = ref({ nombre: '', codigo_tipo_modelo: null, set_x: null, set_y: null, letra: null })
const title = 'Configuraciones de Modelo'

function filtrar() {
    router.get(route('configuraciones-modelo.index'), { search: search.value, codigo_tipo_modelo: tipoModeloFiltro.value }, { preserveState: true, replace: true })
}

watch(search, filtrar)
watch(tipoModeloFiltro, filtrar)

function openCreate() {
    editing.value = null
    form.value = { nombre: '', codigo_tipo_modelo: null, set_x: null, set_y: null, letra: null }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        nombre: item.nombre,
        codigo_tipo_modelo: item.codigo_tipo_modelo,
        set_x: item.set_x ?? null,
        set_y: item.set_y ?? null,
        letra: item.letra ?? null,
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('configuraciones-modelo.update', editing.value.id) : route('configuraciones-modelo.store')
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
                    <div class="flex gap-2">
                        <Select v-model="tipoModeloFiltro" :options="tiposModelo" optionLabel="label" optionValue="value" placeholder="Tipo Modelo..." :showClear="true" class="w-48" />
                        <InputText v-model="search" placeholder="Buscar..." />
                    </div>
                </template>
            </Toolbar>

            <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total">
                <Column field="nombre" header="Nombre" sortable />
                <Column field="codigo_tipo_modelo" header="Tipo Modelo">
                    <template #body="{ data }">
                        {{ data.tipo_modelo?.nombre ?? data.codigo_tipo_modelo }}
                    </template>
                </Column>
                <Column field="set_x" header="Set X" sortable />
                <Column field="set_y" header="Set Y" sortable />
                <Column field="letra" header="Letra" sortable />
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('configuraciones-modelo.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Configuración' : 'Nueva Configuración'" modal style="width: 550px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Nombre</label>
                        <InputText v-model="form.nombre" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tipo Modelo</label>
                        <Select v-model="form.codigo_tipo_modelo" :options="tiposModelo" optionLabel="label" optionValue="value" placeholder="Seleccionar..." class="w-full" :showClear="true" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Set X</label>
                        <InputNumber v-model="form.set_x" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Set Y</label>
                        <InputNumber v-model="form.set_y" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Letra</label>
                        <InputNumber v-model="form.letra" class="w-full" />
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
