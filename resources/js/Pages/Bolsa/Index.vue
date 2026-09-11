<script setup>
import { ref, watch, computed } from 'vue'
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
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ bolsa: Object, filters: Object, cargos: Array, areas: Array, entidades: Array, roles: Array, esSuperadmin: Boolean, catalogo: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const filtroCargo = ref(props.filters?.id_cargo || null)
const filtroArea = ref(props.filters?.id_area || null)
const showForm = ref(false)
const editing = ref(null)

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

// Sección de Documentos: visible si el empleado tiene licencia; si no,
// se puede mostrar opcionalmente con un toggle (los documentos son opcionales).
const mostrarDocumentos = ref(false)
const tieneLicencia = computed(() => {
  const doc = form.value.documentos?.find(d => d.tipo === 'LICENCIA')
  return Boolean(doc && String(doc.numero || '').trim() !== '')
})
const mostrarSeccionDocumentos = computed(() => tieneLicencia.value || mostrarDocumentos.value)

function onPage(event) {
  router.get(route('bolsa.index'), {
    page: event.page + 1,
    search: search.value,
    id_cargo: filtroCargo.value,
    id_area: filtroArea.value,
  }, { preserveState: true, replace: true })
}

watch(search, () => {
  router.get(route('bolsa.index'), { search: search.value, id_cargo: filtroCargo.value, id_area: filtroArea.value }, { preserveState: true, replace: true })
})

watch([filtroCargo, filtroArea], () => {
  router.get(route('bolsa.index'), { search: search.value, id_cargo: filtroCargo.value, id_area: filtroArea.value }, { preserveState: true, replace: true })
})

function openCreate() {
  editing.value = null
  form.value = emptyForm()
  form.value.documentos = docTipos.map(t => ({ tipo: t.value, numero: '', emision: null, vencimiento: null, notas: '' }))
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  const docs = {}
  for (const d of (item.documentos || [])) docs[d.tipo] = d
  form.value = {
    ci: item.ci, nombre: item.nombre, apellidos: item.apellidos,
    sexo: typeof item.sexo === 'object' ? item.sexo?.id : item.sexo,
    color_piel: typeof item.color_piel === 'object' ? item.color_piel?.id : item.color_piel,
    nivel_educacional: typeof item.nivel_educacional === 'object' ? item.nivel_educacional?.id : item.nivel_educacional,
    estado_civil: typeof item.estado_civil === 'object' ? item.estado_civil?.id : item.estado_civil,
    ubicacion_defensa: typeof item.ubicacion_defensa === 'object' ? item.ubicacion_defensa?.id : item.ubicacion_defensa,
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

function estadoPlaza(data) {
  // Verde = contratado en una plaza de la plantilla; azul = libre.
  return data.tiene_plaza ? 'success' : 'info'
}
</script>

<template>
  <AppLayout title="Bolsa de Trabajo">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate()" />
        </template>
        <template #end>
          <div class="flex gap-2 flex-wrap">
            <Select v-model="filtroArea" :options="areas" optionLabel="nombre" optionValue="id" placeholder="Filtrar por área" class="w-56" showClear />
            <Select v-model="filtroCargo" :options="cargos" optionLabel="nombre" optionValue="id" placeholder="Filtrar por cargo" class="w-56" showClear />
            <InputText v-model="search" placeholder="Buscar..." />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="bolsa.data" striped-rows paginator lazy :rows="bolsa.per_page" :total-records="bolsa.total"
        :first="(bolsa.current_page - 1) * bolsa.per_page" @page="onPage"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        currentPageReportTemplate="Total: {totalRecords} registros">
        <Column header="Estado" style="width: 90px">
          <template #body="{ data }">
            <Tag :severity="estadoPlaza(data)" :value="data.tiene_plaza ? 'Plaza' : 'Libre'" />
          </template>
        </Column>
        <Column field="ci" header="CI" sortable />
        <Column header="Nombre y Apellidos" sortable sortField="nombre">
          <template #body="{ data }">{{ data.nombre }} {{ data.apellidos }}</template>
        </Column>
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
            <div>
              <label class="block mb-1 font-medium">Ubicación en la Defensa</label>
              <Select v-model="form.ubicacion_defensa" :options="ubicacionDefensaOpciones" optionLabel="label" optionValue="value" placeholder="Seleccione..." class="w-full" />
            </div>
          </div>
        </fieldset>

        <!-- Contacto (debajo de datos personales) -->
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

        <!-- Documentos del Chofer: visibles con licencia; opcionales si no -->
        <div v-if="!mostrarSeccionDocumentos" class="flex items-center gap-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
          <Checkbox v-model="mostrarDocumentos" :binary="true" inputId="mostrar_docs" />
          <label for="mostrar_docs" class="text-sm text-gray-600 dark:text-gray-300">
            Mostrar documentos (opcionales — no tiene licencia)
          </label>
        </div>
        <fieldset v-else class="border rounded-lg p-4">
          <legend class="font-bold text-lg px-2">Documentos</legend>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
            Sección visible porque el empleado tiene licencia. Para gestionar
            chequeos y recalificaciones use el módulo de documentos de choferes.
          </p>
          <div class="space-y-4">
            <div v-for="(doc, idx) in form.documentos" :key="doc.tipo" class="border rounded-lg p-3">
              <div class="font-semibold mb-2 text-orange-600 dark:text-orange-400">{{ docEtiqueta(doc.tipo) }}</div>
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
            <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-950/40">
              <label class="block mb-2 font-medium dark:text-orange-200">Categorías de Licencia</label>
              <div class="flex gap-3 flex-wrap">
                <div v-for="cat in catOpciones" :key="cat" class="flex items-center gap-1 px-2 py-1 rounded bg-white dark:bg-gray-800 border dark:border-gray-600">
                  <Checkbox v-model="form.categorias_licencia" :value="cat" :inputId="`cat-${cat}`" />
                  <label :for="`cat-${cat}`" class="text-sm dark:text-gray-200">{{ cat }}</label>
                </div>
              </div>
            </div>
          </div>
        </fieldset>

        <div v-if="!editing" class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-lg space-y-3">
          <div class="flex items-center gap-2">
            <Checkbox v-model="form.crear_usuario" :binary="true" inputId="crear_usuario" />
            <label for="crear_usuario" class="text-sm text-blue-700 dark:text-blue-300 font-medium">
              Crear usuario en el sistema (contraseña temporal: ZAFIRO)
            </label>
          </div>
          <div v-if="form.crear_usuario" class="ml-6">
            <label class="block mb-1 text-sm font-medium">Rol del usuario</label>
            <Select v-model="form.rol" :options="roles" optionLabel="name" optionValue="name" placeholder="Seleccione rol..." class="w-48" />
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
