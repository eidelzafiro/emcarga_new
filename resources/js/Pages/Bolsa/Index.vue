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

const props = defineProps({ bolsa: Object, filters: Object, cargos: Array, entidades: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ ci: '', nombre: '', apellidos: '', sexo: null, fecha_nacimiento: null, direccion: '', telefono: '', email: '', id_cargo: null, id_entidad: null })
const title = 'Bolsa de Trabajo'

const sexos = [
    { label: 'Masculino', value: 'M' },
    { label: 'Femenino', value: 'F' },
]

watch(search, () => {
    router.get(route('bolsa.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = { ci: '', nombre: '', apellidos: '', sexo: null, fecha_nacimiento: null, direccion: '', telefono: '', email: '', id_cargo: null, id_entidad: null }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        ci: item.ci,
        nombre: item.nombre,
        apellidos: item.apellidos,
        sexo: item.sexo,
        fecha_nacimiento: item.fecha_nacimiento ? new Date(item.fecha_nacimiento) : null,
        direccion: item.direccion || '',
        telefono: item.telefono || '',
        email: item.email || '',
        id_cargo: item.id_cargo,
        id_entidad: item.id_entidad,
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('bolsa.update', editing.value.id) : route('bolsa.store')
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

            <DataTable :value="bolsa.data" striped-rows paginator :rows="20" :total-records="bolsa.total">
                <Column field="ci" header="CI" sortable />
                <Column field="nombre" header="Nombre" sortable />
                <Column field="apellidos" header="Apellidos" sortable />
                <Column field="sexo" header="Sexo" />
                <Column field="telefono" header="Teléfono" />
                <Column field="email" header="Email" />
                <Column field="cargo.nombre" header="Cargo" />
                <Column field="entidad.nombre" header="Entidad" />
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('bolsa.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Candidato' : 'Nuevo Candidato'" modal style="width: 600px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">CI</label>
                        <InputText v-model="form.ci" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Sexo</label>
                        <Select v-model="form.sexo" :options="sexos" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Nombre</label>
                        <InputText v-model="form.nombre" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Apellidos</label>
                        <InputText v-model="form.apellidos" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Fecha de Nacimiento</label>
                        <DatePicker v-model="form.fecha_nacimiento" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Teléfono</label>
                        <InputText v-model="form.telefono" class="w-full" />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Email</label>
                        <InputText v-model="form.email" type="email" class="w-full" />
                    </div>
                    <div class="col-span-2">
                        <label class="block mb-1 font-medium">Dirección</label>
                        <InputText v-model="form.direccion" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Cargo</label>
                        <Select v-model="form.id_cargo" :options="cargos" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Entidad</label>
                        <Select v-model="form.id_entidad" :options="entidades" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
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
