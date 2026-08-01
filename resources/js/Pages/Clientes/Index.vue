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

const props = defineProps({ clientes: Object, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ codigo: '', nombre: '', nit: '', telefono: '', email: '', direccion: '', contacto: '', razon_social: '' })
const title = 'Clientes'

function onPage(event) {
    router.get(route('clientes.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

watch(search, () => {
    router.get(route('clientes.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { codigo: '', nombre: '', nit: '', telefono: '', email: '', direccion: '', contacto: '', razon_social: '' }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = { codigo: item.codigo, nombre: item.nombre, nit: item.nit, telefono: item.telefono, email: item.email, direccion: item.direccion, contacto: item.contacto, razon_social: item.razon_social }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('clientes.update', editing.value.id) : route('clientes.store')
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

            <DataTable :value="clientes.data" striped-rows paginator :rows="20" :total-records="clientes.total" :lazy="true" :first="(clientes.current_page - 1) * clientes.per_page" @page="onPage">
                <Column field="codigo" header="Código" sortable />
                <Column field="nombre" header="Nombre" sortable />
                <Column field="nit" header="NIT" />
                <Column field="telefono" header="Teléfono" />
                <Column field="email" header="Email" />
                <Column field="activo" header="Activo">
                    <template #body="{ data }">
                        <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('clientes.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Cliente' : 'Nuevo Cliente'" modal style="width: 550px">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block mb-1 font-medium">Código</label>
                    <InputText v-model="form.codigo" class="w-full" required />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Nombre</label>
                    <InputText v-model="form.nombre" class="w-full" required />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Razón Social</label>
                    <InputText v-model="form.razon_social" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1 font-medium">NIT</label>
                    <InputText v-model="form.nit" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Teléfono</label>
                    <InputText v-model="form.telefono" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Email</label>
                    <InputText v-model="form.email" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Dirección</label>
                    <InputText v-model="form.direccion" class="w-full" />
                </div>
                <div>
                    <label class="block mb-1 font-medium">Contacto</label>
                    <InputText v-model="form.contacto" class="w-full" />
                </div>
                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="showForm = false" />
                    <Button label="Guardar" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
