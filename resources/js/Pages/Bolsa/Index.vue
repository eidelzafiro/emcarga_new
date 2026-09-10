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

const props = defineProps({ bolsa: Object, filters: Object, cargos: Array, areas: Array, entidades: Array, roles: Array, esSuperadmin: Boolean, catalogo: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const filtroCargo = ref(props.filters?.id_cargo || null)
const filtroArea = ref(props.filters?.id_area || null)
const showForm = ref(false)
const editing = ref(null)
const isChofer = ref(false)

const docTipos = [
  { label: 'Licencia de Conducción', value: 'LICENCIA' },
  { label: 'Chequeo Médico', value: 'CHEQUEO_MEDICO' },
  { label: 'Recalificación', value: 'RECALIFICACION' },
  { label: 'Psicométrico', value: 'PSICOMETRICO' },
]
const docEtiqueta = (t) => docTipos.find(d => d.value === t)?.label || t
const catOpciones = ['A', 'A1', 'B', 'C', 'C1', 'D', 'D1', 'E', 'F', 'FE']

const emptyForm = () => ({
  ci: '', nombre: '', apellidos: '', sexo: null, color_piel: null, nivel_educacional: null,
  estado_civil: null, ubicacion_defensa: null,
  documentos: [],
  categorias_licencia: [],
  direccion: '', telefono: '', email: '', id_cargo: null, id_area: null, id_entidad: null,
  crear_usuario: false, rol: 'RECHUM',
})

const form = ref(emptyForm())

// Opciones del catálogo unificado (tipos_sexo, tipos_color_piel, ...) con
// valores = ids de catalogo_items.
const sexos = props.catalogo?.sexos || []
const colorPielOpciones = props.catalogo?.colores_piel || []
const nivelEducOpciones = props.catalogo?.niveles_educacion || []
const estadoCivilOpciones = props.catalogo?.estados_civiles || []
const ubicacionDefensaOpciones = props.catalogo?.ubicaciones_defensa || []

watch(search, () => {
  router.get(route('bolsa.index'), { search: search.value, id_cargo: filtroCargo.value, id_area: filtroArea.value }, { preserveState: true, replace: true })
})

watch([filtroCargo, filtroArea], () => {
  router.get(route('bolsa.index'), { search: search.value, id_cargo: filtroCargo.value, id_area: filtroArea.value }, { preserveState: true, replace: true })
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
  form.value.documentos = docTipos.map(t => ({ tipo: t.value, numero: '', emision: null, vencimiento: null, notas: '' }))
  isChofer.value = false
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  const docs = {}
  for (const d of (item.documentos || [])) docs[d.tipo] = d
  form.value = {
    ci: item.ci, nombre: item.nombre, apellidos: item.apellidos,
    sexo: item.sexo, color_piel: item.color_piel, nivel_educacional: item.nivel_educacional,
    estado_civil: item.estado_civil, ubicacion_defensa: item.ubicacion_defensa,
    documentos: docTipos.map(t => ({
      tipo: t.value,
      numero: docs[t.value]?.numero || '',
      emision: docs[t.value]?.emision ? new Date(docs[t.value].emision) : null,
      vencimiento: docs[t.value]?.vencimiento ? new Date(docs[t.value].vencimiento) : null,
      notas: docs[t.value]?.notas || '',
    })),
    categorias_licencia: (item.licencia_categorias || []).map(c => c.categoria),
    direccion: item.direccion || '', telefono: item.telefono || '', email: item.email || '',
    id_cargo: item.id_cargo, id_area: item.id_area, id_entidad: item.id_entidad,
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
          <div class="flex gap-2 flex-wrap">
            <Select v-model="filtroArea" :options="areas" optionLabel="nombre" optionValue="id" placeholder="Filtrar por área" class="w-56" showClear />
            <Select v-model="filtroCargo" :options="cargos" optionLabel="nombre" optionValue="id" placeholder="Filtrar por cargo" class="w-56" showClear />
            <InputText v-model="search" placeholder="Buscar..." />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="bolsa.data" striped-rows paginator :rows="20" :total-records="bolsa.total" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="ci" header="CI" sortable />
        <Column field="nombre" header="Nombre" sortable />
        <Column field="apellidos" header="Apellidos" sortable />
        <Column field="sexo_catalogo.nombre" header="Sexo" />
        <Column header="Licencia">
          <template #body="{ data }">
            <Tag v-if="(data.documentos || []).some(d => d.tipo === 'LICENCIA')" value="Sí" severity="success" />
            <Tag v-else value="No" severity="secondary" />
          </template>
        </Column>
        <Column header="Categ.">
          <template #body="{ data }">
            <span class="text-sm">{{ (data.licencia_categorias || []).map(c => c.categoria).join(', ') }}</span>
          </template>
        </Column>
        <Column field="cargo.nombre" header="Cargo" />
        <Column field="area.nombre" header="Área" />
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
              <label class="block mb-1 font-medium">Color de la Piel</label>
              <Select v-model="form.color_piel" :options="colorPielOpciones" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Nivel Educacional</label>
              <Select v-model="form.nivel_educacional" :options="nivelEducOpciones" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
            </div>
            <div>
              <label class="block mb-1 font-medium">Estado Civil</label>
              <Select v-model="form.estado_civil" :options="estadoCivilOpciones" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
            </div>
            <div class="col-span-2">
              <label class="block mb-1 font-medium">Ubicación en la Defensa</label>
              <Select v-model="form.ubicacion_defensa" :options="ubicacionDefensaOpciones" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
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
            <div>
              <label class="block mb-1 font-medium">Área</label>
              <Select v-model="form.id_area" :options="areas" optionLabel="nombre" optionValue="id" placeholder="Seleccione..." class="w-full" />
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

        <!-- Documentos del Chofer -->
        <fieldset class="border rounded-lg p-4">
          <legend class="font-bold text-lg px-2">Documentos</legend>
          <div class="space-y-4">
            <div v-for="(doc, idx) in form.documentos" :key="doc.tipo" class="border rounded-lg p-3">
              <div class="font-semibold mb-2 text-orange-600">{{ docEtiqueta(doc.tipo) }}</div>
              <div class="grid grid-cols-2 gap-4">
                <div v-if="doc.tipo === 'LICENCIA'">
                  <label class="block mb-1 font-medium">Número</label>
                  <InputText v-model="doc.numero" class="w-full" />
                </div>
                <div>
                  <label class="block mb-1 font-medium">Emisión</label>
                  <DatePicker v-model="doc.emision" dateFormat="dd/mm/yy" class="w-full" />
                </div>
                <div>
                  <label class="block mb-1 font-medium">Vencimiento</label>
                  <DatePicker v-model="doc.vencimiento" dateFormat="dd/mm/yy" class="w-full" />
                </div>
                <div v-if="doc.tipo === 'LICENCIA'" class="col-span-2">
                  <label class="block mb-1 font-medium">Limitaciones</label>
                  <InputText v-model="doc.notas" class="w-full" placeholder="Restricciones (ej: C/ESPEJUELOS)" />
                </div>
              </div>
            </div>
            <div class="p-3 bg-orange-50 rounded-lg">
              <label class="block mb-2 font-medium">Categorías de Licencia</label>
              <div class="flex gap-2 flex-wrap">
                <div v-for="cat in catOpciones" :key="cat" class="flex items-center gap-1">
                  <Checkbox v-model="form.categorias_licencia" :value="cat" :inputId="`cat-${cat}`" />
                  <label :for="`cat-${cat}`" class="text-sm">{{ cat }}</label>
                </div>
              </div>
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
