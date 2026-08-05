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
import Checkbox from 'primevue/checkbox'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ bolsa: Object, filters: Object, cargos: Array, entidades: Array, roles: Array, esSuperadmin: Boolean })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const isChofer = ref(false)

const emptyForm = () => ({
  ci: '', nombre: '', apellidos: '', sexo: null, color_piel: '', nivel_educacional: '',
  estado_civil: '', ubicacion_defensa: '', fecha_nacimiento: null,
  tiene_licencia: false, categorias_licencia: '', licencia_emision: null, licencia_vencimiento: null,
  limitaciones: '',
  chequeo_medico_emision: null, chequeo_medico_vencimiento: null,
  reubicacion_emision: null, reubicacion_vencimiento: null,
  psicometrico_emision: null, psicometrico_vencimiento: null,
  direccion: '', telefono: '', email: '', id_cargo: null, id_entidad: null,
  crear_usuario: false, rol: 'RECHUM',
})

const form = ref(emptyForm())

const sexos = [{ label: 'Masculino', value: 'M' }, { label: 'Femenino', value: 'F' }]
const colorPielOpciones = ['Blanco', 'Negro', 'Mestizo']
const nivelEducOpciones = ['6to Grado', '9no Grado', '12mo Grado', 'Técnico Medio', 'Universitario']
const estadoCivilOpciones = ['Soltero', 'Casado', 'Divorciado', 'Viudo', 'Unión Libre']

watch(search, () => {
  router.get(route('bolsa.index'), { search: search.value }, { preserveState: true, replace: true })
})

watch(() => form.value.id_cargo, (val) => {
  if (val) {
    const cargo = props.cargos.find(c => c.id === val)
    isChofer.value = cargo?.nombre?.toUpperCase().includes('CHOFER') || false
  } else {
    isChofer.value = false
  }
})

function openCreate() {
  editing.value = null
  form.value = emptyForm()
  isChofer.value = false
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    ci: item.ci, nombre: item.nombre, apellidos: item.apellidos,
    sexo: item.sexo, color_piel: item.color_piel || '', nivel_educacional: item.nivel_educacional || '',
    estado_civil: item.estado_civil || '', ubicacion_defensa: item.ubicacion_defensa || '',
    fecha_nacimiento: item.fecha_nacimiento ? new Date(item.fecha_nacimiento) : null,
    tiene_licencia: Boolean(item.tiene_licencia), categorias_licencia: item.categorias_licencia || '',
    licencia_emision: item.licencia_emision ? new Date(item.licencia_emision) : null,
    licencia_vencimiento: item.licencia_vencimiento ? new Date(item.licencia_vencimiento) : null,
    limitaciones: item.limitaciones || '',
    chequeo_medico_emision: item.chequeo_medico_emision ? new Date(item.chequeo_medico_emision) : null,
    chequeo_medico_vencimiento: item.chequeo_medico_vencimiento ? new Date(item.chequeo_medico_vencimiento) : null,
    reubicacion_emision: item.reubicacion_emision ? new Date(item.reubicacion_emision) : null,
    reubicacion_vencimiento: item.reubicacion_vencimiento ? new Date(item.reubicacion_vencimiento) : null,
    psicometrico_emision: item.psicometrico_emision ? new Date(item.psicometrico_emision) : null,
    psicometrico_vencimiento: item.psicometrico_vencimiento ? new Date(item.psicometrico_vencimiento) : null,
    direccion: item.direccion || '', telefono: item.telefono || '', email: item.email || '',
    id_cargo: item.id_cargo, id_entidad: item.id_entidad,
  }
  isChofer.value = item.cargo?.nombre?.toUpperCase().includes('CHOFER') || false
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
  <AppLayout title="Bolsa de Trabajo">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
        </template>
        <template #end>
          <InputText v-model="search" placeholder="Buscar..." />
        </template>
      </Toolbar>

      <DataTable :value="bolsa.data" striped-rows paginator :rows="20" :total-records="bolsa.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="ci" header="CI" sortable />
        <Column field="nombre" header="Nombre" sortable />
        <Column field="apellidos" header="Apellidos" sortable />
        <Column field="sexo" header="Sexo" />
        <Column header="Licencia">
          <template #body="{ data }">
            <Tag v-if="data.tiene_licencia" value="Sí" severity="success" />
            <Tag v-else value="No" severity="secondary" />
          </template>
        </Column>
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

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Empleado' : 'Nuevo Empleado'" modal :style="{ width: '750px' }">
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Datos personales -->
        <fieldset class="border rounded-lg p-4">
          <legend class="font-bold text-lg px-2">Datos Personales</legend>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium">CI *</label>
              <InputText v-model="form.ci" class="w-full" required />
            </div>
            <div>
              <label class="block mb-1 font-medium">Sexo</label>
              <Select v-model="form.sexo" :options="sexos" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Nombre *</label>
              <InputText v-model="form.nombre" class="w-full" required />
            </div>
            <div>
              <label class="block mb-1 font-medium">Apellidos *</label>
              <InputText v-model="form.apellidos" class="w-full" required />
            </div>
            <div>
              <label class="block mb-1 font-medium">Fecha Nacimiento</label>
              <DatePicker v-model="form.fecha_nacimiento" dateFormat="dd/mm/yy" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Color de la Piel</label>
              <Select v-model="form.color_piel" :options="colorPielOpciones" placeholder="Seleccione..." class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Nivel Educacional</label>
              <Select v-model="form.nivel_educacional" :options="nivelEducOpciones" placeholder="Seleccione..." class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Estado Civil</label>
              <Select v-model="form.estado_civil" :options="estadoCivilOpciones" placeholder="Seleccione..." class="w-full" />
            </div>
            <div class="col-span-2">
              <label class="block mb-1 font-medium">Ubicación en la Defensa</label>
              <InputText v-model="form.ubicacion_defensa" class="w-full" />
            </div>
          </div>
        </fieldset>

        <!-- Asignación -->
        <fieldset class="border rounded-lg p-4">
          <legend class="font-bold text-lg px-2">Asignación</legend>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium">Cargo</label>
              <Select v-model="form.id_cargo" :options="cargos" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
            </div>
            <div v-if="esSuperadmin">
              <label class="block mb-1 font-medium">Entidad</label>
              <Select v-model="form.id_entidad" :options="entidades" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
            </div>
          </div>
          <div v-if="!editing" class="mt-4 p-3 bg-blue-50 rounded-lg space-y-3">
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.crear_usuario" :binary="true" inputId="crear_usuario" />
              <label for="crear_usuario" class="text-sm text-blue-700 font-medium">
                Crear usuario en el sistema (contraseña temporal: ZAFIRO)
              </label>
            </div>
            <div v-if="form.crear_usuario" class="ml-6">
              <label class="block mb-1 text-sm font-medium">Rol del usuario</label>
              <Select v-model="form.rol" :options="roles" optionLabel="name" optionValue="name" placeholder="Seleccione rol..." class="w-48" />
            </div>
          </div>
        </fieldset>

        <!-- Licencia de Conducción -->
        <fieldset class="border rounded-lg p-4">
          <legend class="font-bold text-lg px-2">Licencia de Conducción</legend>
          <div class="flex items-center gap-4 mb-3">
            <label class="font-medium">¿Tiene Licencia de Conducción?</label>
            <ToggleSwitch v-model="form.tiene_licencia" />
          </div>
          <div v-if="form.tiene_licencia" class="grid grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium">Categorías</label>
              <InputText v-model="form.categorias_licencia" class="w-full" placeholder="Ej: A, B, C, D" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Fecha de Emisión</label>
              <DatePicker v-model="form.licencia_emision" dateFormat="dd/mm/yy" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Vencimiento</label>
              <DatePicker v-model="form.licencia_vencimiento" dateFormat="dd/mm/yy" class="w-full" />
            </div>
            <div class="col-span-2">
              <label class="block mb-1 font-medium">Limitaciones</label>
              <InputText v-model="form.limitaciones" class="w-full" placeholder="Restricciones o limitaciones físicas" />
            </div>
          </div>
        </fieldset>

        <!-- Chequeos Médicos (solo choferes) -->
        <fieldset v-if="isChofer" class="border rounded-lg p-4">
          <legend class="font-bold text-lg px-2 text-orange-600">Chequeos Médicos (Chofer de Transportación)</legend>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium">Chequeo Médico - Emisión</label>
              <DatePicker v-model="form.chequeo_medico_emision" dateFormat="dd/mm/yy" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Chequeo Médico - Vencimiento</label>
              <DatePicker v-model="form.chequeo_medico_vencimiento" dateFormat="dd/mm/yy" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Recalificación - Emisión</label>
              <DatePicker v-model="form.reubicacion_emision" dateFormat="dd/mm/yy" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Recalificación - Vencimiento</label>
              <DatePicker v-model="form.reubicacion_vencimiento" dateFormat="dd/mm/yy" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Psicométrico - Emisión</label>
              <DatePicker v-model="form.psicometrico_emision" dateFormat="dd/mm/yy" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Psicométrico - Vencimiento</label>
              <DatePicker v-model="form.psicometrico_vencimiento" dateFormat="dd/mm/yy" class="w-full" />
            </div>
          </div>
        </fieldset>

        <!-- Contacto -->
        <fieldset class="border rounded-lg p-4">
          <legend class="font-bold text-lg px-2">Contacto</legend>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block mb-1 font-medium">Teléfono</label>
              <InputText v-model="form.telefono" class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Email</label>
              <InputText v-model="form.email" type="email" class="w-full" />
            </div>
            <div class="col-span-2">
              <label class="block mb-1 font-medium">Dirección</label>
              <InputText v-model="form.direccion" class="w-full" />
            </div>
          </div>
        </fieldset>

        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
