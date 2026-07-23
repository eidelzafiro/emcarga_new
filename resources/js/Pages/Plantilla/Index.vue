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
import ToggleSwitch from 'primevue/toggleswitch'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
    plantilla: Object,
    filters: Object,
    cargos: Array,
    entidades: Array,
    bolsa: Array,
    turnos: Array,
    tipos_contrato: Array,
    tipos_sistemas_pago: Array,
})
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({
    codigo: '', nombre: '', id_cargo: null, id_entidad: null, id_bolsa: null,
    id_turno: null, id_tipo_contrato: null, plazas: 1, cubiertas: 0,
    salario_base_mn: 0, aseo: false, activo: true,
})
const title = 'Plantilla'

watch(search, () => {
    router.get(route('plantilla.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
    editing.value = null
    form.value = {
        codigo: '', nombre: '', id_cargo: null, id_entidad: null, id_bolsa: null,
        id_turno: null, id_tipo_contrato: null, plazas: 1, cubiertas: 0,
        salario_base_mn: 0, aseo: false, activo: true,
    }
    showForm.value = true
}

function openEdit(item) {
    editing.value = item
    form.value = {
        codigo: item.codigo,
        nombre: item.nombre,
        id_cargo: item.id_cargo,
        id_entidad: item.id_entidad,
        id_bolsa: item.id_bolsa,
        id_turno: item.id_turno,
        id_tipo_contrato: item.id_tipo_contrato,
        plazas: item.plazas || 1,
        cubiertas: item.cubiertas || 0,
        salario_base_mn: item.salario_base_mn || 0,
        aseo: Boolean(item.aseo),
        activo: Boolean(item.activo),
    }
    showForm.value = true
}

function submit() {
    const url = editing.value ? route('plantilla.update', editing.value.id) : route('plantilla.store')
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

            <DataTable :value="plantilla.data" striped-rows paginator :rows="20" :total-records="plantilla.total">
                <Column field="codigo" header="Código" sortable />
                <Column field="nombre" header="Nombre" sortable />
                <Column field="cargo.nombre" header="Cargo" />
                <Column field="entidad.nombre" header="Entidad" />
                <Column field="plazas" header="Plazas" />
                <Column field="cubiertas" header="Cubiertas" />
                <Column field="salario_base_mn" header="Salario Base MN" />
                <Column field="aseo" header="Aseo">
                    <template #body="{ data }">
                        <Tag :value="data.aseo ? 'Sí' : 'No'" :severity="data.aseo ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column field="activo" header="Activo">
                    <template #body="{ data }">
                        <Tag :value="data.activo ? 'Sí' : 'No'" :severity="data.activo ? 'success' : 'danger'" />
                    </template>
                </Column>
                <Column header="Acciones" style="width: 120px">
                    <template #body="{ data }">
                        <div class="flex gap-1">
                            <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('plantilla.destroy', data.id))" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="showForm" :header="editing ? 'Editar Plaza' : 'Nueva Plaza'" modal style="width: 700px">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1 font-medium">Código</label>
                        <InputText v-model="form.codigo" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Nombre</label>
                        <InputText v-model="form.nombre" class="w-full" required />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Cargo</label>
                        <Select v-model="form.id_cargo" :options="cargos" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Entidad</label>
                        <Select v-model="form.id_entidad" :options="entidades" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Bolsa / Candidato</label>
                        <Select v-model="form.id_bolsa" :options="bolsa" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Turno</label>
                        <Select v-model="form.id_turno" :options="turnos" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Tipo de Contrato</label>
                        <Select v-model="form.id_tipo_contrato" :options="tipos_contrato" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Sistema de Pago</label>
                        <Select v-model="form.id_tipo_sistema_pago" :options="tipos_sistemas_pago" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Plazas</label>
                        <InputNumber v-model="form.plazas" :min="1" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Cubiertas</label>
                        <InputNumber v-model="form.cubiertas" :min="0" class="w-full" />
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Salario Base MN</label>
                        <InputNumber v-model="form.salario_base_mn" :min="0" class="w-full" />
                    </div>
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="form.aseo" inputId="aseo" />
                        <label for="aseo" class="font-medium">Aseo</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="form.activo" inputId="activo" />
                        <label for="activo" class="font-medium">Activo</label>
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
