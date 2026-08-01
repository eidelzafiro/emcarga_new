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
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ lugares: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ nombre: '', provincia: '', municipio: '', latitud: null, longitud: null })
const title = 'Lugares'

function onPage(event) {
    router.get(route('lugares.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

watch(search, () => {
    router.get(route('lugares.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { nombre: '', provincia: '', municipio: '', latitud: null, longitud: null }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = { nombre: item.nombre, provincia: item.provincia, municipio: item.municipio, latitud: item.latitud, longitud: item.longitud }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('lugares.update', editing.value.id) : route('lugares.store')
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

            <DataTable :value="lugares.data" striped-rows paginator :rows="20" :total-records="lugares.total" :lazy="true" :first="(lugares.current_page - 1) * lugares.per_page" @page="onPage">
                <Column field="nombre" header="Nombre" sortable />
                <Column field="provincia" header="Provincia" sortable />
                <Column field="municipio" header="Municipio" sortable />
                <Column field="activo" header="Activo">
                    <template #body="{ data }">
                        <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('lugares.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Lugar' : 'Nuevo Lugar'" modal style="width: 550px">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium">Nombre</label>
                    <InputText v-model="form.nombre" class="w-full" required />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Provincia</label>
                    <InputText v-model="form.provincia" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Municipio</label>
                    <InputText v-model="form.municipio" class="w-full" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Latitud</label>
                        <InputNumber v-model="form.latitud" :min="-90" :max="90" :max-fraction-digits="8" class="w-full" placeholder="0.00000000" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Longitud</label>
                        <InputNumber v-model="form.longitud" :min="-180" :max="180" :max-fraction-digits="8" class="w-full" placeholder="0.00000000" />
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
