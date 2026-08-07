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
import Checkbox from 'primevue/checkbox'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ hojas: Object, catalogos: Object, filters: Object })
const toast = useToast()
const title = 'Hoja de Ruta'
const search = ref(props.filters?.search || '')
const estado = ref(props.filters?.estado || 'todas')

const showApertura = ref(false)
const showCierre = ref(false)
const showEdicion = ref(false)
const creandoHr = ref(null)
const editandoId = ref(null)

const choferes = (props.catalogos?.choferes || []).map(c => ({ id: c.id, label: `${c.nombre} ${c.apellidos || ''}`.trim() }))

const apertura = ref({ numero: '', fecha_emision: '', hora_emision: '', id_tractivo: null, id_arrastre: null, id_chofer: null, id_chofer2: null, id_parqueo: null, id_grupo: null, kms_disponible: null, kms_disponibles_adicionales: null })

const cierre = ref({ fecha_cierre: '', hora_cierre: '', kms_totales: null, combustible_habilitado: 0, combustible_consumido: 0, combustible_tecnico: 0, dias_trabajados: '', crear_siguiente: true, numero_nueva: '', fecha_emision: '', hora_emision: '', kms_disponible: null, kms_disponibles_adicionales: null })

const edicion = ref({})

watch([search, estado], () => {
  router.get(route('hojas-ruta.index'), { search: search.value, estado: estado.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('hojas-ruta.index'), { page: event.page + 1, search: search.value, estado: estado.value }, { preserveState: true, replace: true })
}

function nowDate() { return new Date().toISOString().slice(0, 10) }
function nowTime() { return new Date().toTimeString().slice(0, 5) }

function openApertura() {
  apertura.value = { numero: '', fecha_emision: nowDate(), hora_emision: nowTime(), id_tractivo: null, id_arrastre: null, id_chofer: null, id_chofer2: null, id_parqueo: null, id_grupo: null, kms_disponible: null, kms_disponibles_adicionales: null }
  showApertura.value = true
}

function submitApertura() {
  router.post(route('hojas-ruta.store'), apertura.value, {
    onSuccess: () => { showApertura.value = false; toast.add({ severity: 'success', summary: 'Creada', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function openCierre(row) {
  creandoHr.value = row
  cierre.value = { fecha_cierre: nowDate(), hora_cierre: nowTime(), kms_totales: null, combustible_habilitado: 0, combustible_consumido: 0, combustible_tecnico: 0, dias_trabajados: '', crear_siguiente: true, numero_nueva: '', fecha_emision: row.fecha_emision || nowDate(), hora_emision: nowTime(), kms_disponible: row.kms_disponible, kms_disponibles_adicionales: row.kms_disponibles_adicionales }
  showCierre.value = true
}

function submitCierre() {
  const operacion = cierre.value.crear_siguiente ? 'cierre-con-siguiente' : 'cierre'
  router.put(route('hojas-ruta.update', creandoHr.value.id), { operacion, ...cierre.value }, {
    onSuccess: () => { showCierre.value = false; toast.add({ severity: 'success', summary: 'Cerrada', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function openEdicion(row) {
  editandoId.value = row.id
  edicion.value = {
    numero: row.numero, fecha_emision: row.fecha_emision, hora_emision: row.hora_emision,
    fecha_cierre: row.fecha_cierre, hora_cierre: row.hora_cierre,
    id_tractivo: row.id_tractivo, id_arrastre: row.id_arrastre, id_chofer: row.id_chofer, id_chofer2: row.id_chofer2,
    id_parqueo: row.id_parqueo, id_grupo: row.id_grupo,
    kms_disponible: row.kms_disponible, kms_disponibles_adicionales: row.kms_disponibles_adicionales,
    kms_totales: row.kms_totales, combustible_habilitado: row.combustible_habilitado, combustible_consumido: row.combustible_consumido, combustible_tecnico: row.combustible_tecnico,
    notas: row.notas, analisis: row.analisis, dias_trabajados: row.dias_trabajados,
  }
  showEdicion.value = true
}

function submitEdicion() {
  router.put(route('hojas-ruta.update', editandoId.value), edicion.value, {
    onSuccess: () => { showEdicion.value = false; toast.add({ severity: 'success', summary: 'Actualizada', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function cancelar(row) {
  if (!confirm(`¿Cancelar la Hoja de Ruta ${row.numero}?`)) return
  router.post(route('hojas-ruta.destroy', row.id), { operacion: 'cancelar', _method: 'delete' }, {
    onSuccess: () => toast.add({ severity: 'success', summary: 'Cancelada', life: 3000 }),
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function eliminar(row) {
  if (!confirm(`¿Eliminar la Hoja de Ruta ${row.numero}?`)) return
  router.delete(route('hojas-ruta.destroy', row.id), {
    onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminada', life: 3000 }),
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function choferNombre(c) { return c ? `${c.nombre} ${c.apellidos || ''}`.trim() : '—' }
function tractorCodigo(t) { return t ? t.codigo : '—' }
function estadoTag(e) {
  return { abierta: 'info', cerrada: 'success', cancelada: 'danger' }[e] || 'secondary'
}
</script>

<template>
  <AppLayout :title="title">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nueva Hoja" icon="pi pi-plus" severity="success" @click="openApertura" />
        </template>
        <template #end>
          <div class="flex gap-2 items-center">
            <Select v-model="estado" :options="[{ label: 'Todas', value: 'todas' }, { label: 'Abiertas', value: 'abiertas' }, { label: 'Cerradas', value: 'cerradas' }, { label: 'Canceladas', value: 'canceladas' }]" optionLabel="label" optionValue="value" class="w-44" />
            <InputText v-model="search" placeholder="Buscar número, tractor, chofer..." class="w-64" />
          </div>
        </template>
      </Toolbar>

      <DataTable :value="hojas.data" striped-rows paginator :rows="20" :total-records="hojas.total" :lazy="true" :first="(hojas.current_page - 1) * hojas.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="numero" header="Nº HR" sortable :style="{ width: '90px' }" />
        <Column field="fecha_emision" header="Emisión" :style="{ width: '110px' }" />
        <Column header="Tractor"><template #body="{ data }">{{ tractorCodigo(data.tractivo) }}</template></Column>
        <Column header="Arrastre"><template #body="{ data }">{{ data.arrastre?.codigo || '—' }}</template></Column>
        <Column header="Chofer"><template #body="{ data }">{{ choferNombre(data.chofer) }}</template></Column>
        <Column header="Parqueo"><template #body="{ data }">{{ data.parqueo?.nombre || '—' }}</template></Column>
        <Column field="kms_totales" header="KMS" sortable />
        <Column field="estado" header="Estado"><template #body="{ data }"><Tag :value="data.estado" :severity="estadoTag(data.estado)" /></template></Column>
        <Column header="Acciones" :style="{ width: '220px' }">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button v-if="!data.cancelada && !data.fecha_cierre" icon="pi pi-check" rounded text severity="success" title="Cerrar" @click="openCierre(data)" />
              <Button icon="pi pi-pencil" rounded text severity="info" title="Editar" @click="openEdicion(data)" />
              <Button v-if="!data.cancelada && !data.fecha_cierre" icon="pi pi-ban" rounded text severity="warning" title="Cancelar" @click="cancelar(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" title="Eliminar" @click="eliminar(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Apertura -->
    <Dialog v-model:visible="showApertura" header="Apertura de Hoja de Ruta" modal style="width: 700px">
      <form @submit.prevent="submitApertura" class="grid grid-cols-2 gap-4">
        <div>
          <label class="block mb-1 font-medium">Número</label>
          <InputText v-model="apertura.numero" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Fecha emisión</label>
          <input v-model="apertura.fecha_emision" type="date" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Hora emisión</label>
          <input v-model="apertura.hora_emision" type="time" class="w-full border rounded p-2" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Tractivo</label>
          <Select v-model="apertura.id_tractivo" :options="catalogos.tractivos" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Arrastre</label>
          <Select v-model="apertura.id_arrastre" :options="catalogos.arrastres" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Chofer</label>
          <Select v-model="apertura.id_chofer" :options="choferes" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Chofer 2</label>
          <Select v-model="apertura.id_chofer2" :options="choferes" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Parqueo</label>
          <Select v-model="apertura.id_parqueo" :options="catalogos.lugares" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Grupo</label>
          <Select v-model="apertura.id_grupo" :options="catalogos.grupos" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS disponibles</label>
          <InputNumber v-model="apertura.kms_disponible" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS disponibles adicionales</label>
          <InputNumber v-model="apertura.kms_disponibles_adicionales" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div class="flex gap-2 justify-end col-span-2">
          <Button label="Cancelar" severity="secondary" @click="showApertura = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>

    <!-- Cierre -->
    <Dialog v-model:visible="showCierre" header="Cierre de Hoja de Ruta" modal style="width: 700px">
      <form @submit.prevent="submitCierre" class="grid grid-cols-2 gap-4">
        <div>
          <label class="block mb-1 font-medium">Fecha cierre</label>
          <input v-model="cierre.fecha_cierre" type="date" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Hora cierre</label>
          <input v-model="cierre.hora_cierre" type="time" class="w-full border rounded p-2" />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS totales</label>
          <InputNumber v-model="cierre.kms_totales" :min="0" :max-fraction-digits="2" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Combustible habilitado</label>
          <InputNumber v-model="cierre.combustible_habilitado" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Combustible consumido</label>
          <InputNumber v-model="cierre.combustible_consumido" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Combustible técnico</label>
          <InputNumber v-model="cierre.combustible_tecnico" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Días trabajados</label>
          <InputText v-model="cierre.dias_trabajados" class="w-full" />
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="cierre.crear_siguiente" :binary="true" />
          <label class="font-medium">Crear siguiente Hoja</label>
        </div>
        <template v-if="cierre.crear_siguiente">
          <div>
            <label class="block mb-1 font-medium">Nº siguiente</label>
            <InputText v-model="cierre.numero_nueva" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha emisión siguiente</label>
            <input v-model="cierre.fecha_emision" type="date" class="w-full border rounded p-2" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Hora emisión siguiente</label>
            <input v-model="cierre.hora_emision" type="time" class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block mb-1 font-medium">KMS disponibles siguiente</label>
            <InputNumber v-model="cierre.kms_disponible" :min="0" :max-fraction-digits="2" class="w-full" />
          </div>
        </template>
        <div class="flex gap-2 justify-end col-span-2 mt-2">
          <Button label="Cancelar" severity="secondary" @click="showCierre = false" />
          <Button label="Cerrar" type="submit" icon="pi pi-check" />
        </div>
      </form>
    </Dialog>

    <!-- Edición -->
    <Dialog v-model:visible="showEdicion" header="Editar Hoja de Ruta" modal style="width: 700px">
      <form @submit.prevent="submitEdicion" class="grid grid-cols-2 gap-4">
        <div>
          <label class="block mb-1 font-medium">Número</label>
          <InputText v-model="edicion.numero" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Fecha emisión</label>
          <input v-model="edicion.fecha_emision" type="date" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Tractivo</label>
          <Select v-model="edicion.id_tractivo" :options="catalogos.tractivos" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Arrastre</label>
          <Select v-model="edicion.id_arrastre" :options="catalogos.arrastres" optionLabel="codigo" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Chofer</label>
          <Select v-model="edicion.id_chofer" :options="choferes" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Chofer 2</label>
          <Select v-model="edicion.id_chofer2" :options="choferes" optionLabel="label" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Parqueo</label>
          <Select v-model="edicion.id_parqueo" :options="catalogos.lugares" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Grupo</label>
          <Select v-model="edicion.id_grupo" :options="catalogos.grupos" optionLabel="nombre" optionValue="id" filter class="w-full" :showClear="true" />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS disponibles</label>
          <InputNumber v-model="edicion.kms_disponible" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS adicionales</label>
          <InputNumber v-model="edicion.kms_disponibles_adicionales" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">KMS totales</label>
          <InputNumber v-model="edicion.kms_totales" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Combustible habilitado</label>
          <InputNumber v-model="edicion.combustible_habilitado" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Combustible consumido</label>
          <InputNumber v-model="edicion.combustible_consumido" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Combustible técnico</label>
          <InputNumber v-model="edicion.combustible_tecnico" :min="0" :max-fraction-digits="2" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Días trabajados</label>
          <InputText v-model="edicion.dias_trabajados" class="w-full" />
        </div>
        <div class="col-span-2">
          <label class="block mb-1 font-medium">Notas</label>
          <Textarea v-model="edicion.notas" rows="2" class="w-full" />
        </div>
        <div class="col-span-2">
          <label class="block mb-1 font-medium">Análisis</label>
          <Textarea v-model="edicion.analisis" rows="2" class="w-full" />
        </div>
        <div class="flex gap-2 justify-end col-span-2 mt-2">
          <Button label="Cancelar" severity="secondary" @click="showEdicion = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>