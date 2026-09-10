<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, allItems: Array, areas: Array, areasJerarquia: Object, cargos: Array, filters: Object })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const idArea = ref(props.filters?.id_area || null)
const showForm = ref(false)
const editing = ref(null)
const vista = ref('tabla')
const title = 'Plantilla de Puestos'

function baseForm() {
  return { id_cargo: null, id_area: null, propuesta: 0, aprobada: 0, cubierta: 0, cubierta2: 0, v_necesidad: 0, necesidad: 0, observaciones: '' }
}
const form = ref(baseForm())

function reload() {
  router.get(route('plantilla.index'), { search: search.value, id_area: idArea.value || '' }, { preserveState: true, replace: true })
}

function onPage(event) {
  router.get(route('plantilla.index'), { page: event.page + 1, search: search.value, id_area: idArea.value || '' }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    id_cargo: item.id_cargo,
    id_area: item.id_area,
    propuesta: item.propuesta,
    aprobada: item.aprobada,
    cubierta: item.cubierta,
    cubierta2: item.cubierta2,
    v_necesidad: item.v_necesidad,
    necesidad: item.necesidad,
    observaciones: item.observaciones || '',
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

function confirmEliminar(item) {
  confirm.require({
    message: `¿Eliminar el puesto "${item.cargo?.nombre}" de la plantilla?`,
    header: 'Eliminar puesto',
    acceptLabel: 'Si, eliminar',
    rejectLabel: 'No',
    accept: () => {
      router.delete(route('plantilla.destroy', item.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminado', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
}

function cobertura(item) {
  const cubierta = item.cubierta_real ?? item.cubierta ?? 0
  const aprobada = item.aprobada ?? 0
  if (aprobada === 0) return '—'
  const pct = Math.round((cubierta / aprobada) * 100)
  return `${cubierta}/${aprobada} (${pct}%)`
}

function estadoColor(item) {
  const cubierta = item.cubierta_real ?? item.cubierta ?? 0
  const aprobada = item.aprobada ?? 0
  if (aprobada === 0) return 'secondary'
  const pct = (cubierta / aprobada) * 100
  if (pct >= 100) return 'success'
  if (pct >= 50) return 'warn'
  return 'danger'
}

const sortedItems = computed(() => {
  return [...(props.allItems || [])].sort((a, b) => (a.area?.nombre || '').localeCompare(b.area?.nombre || ''))
})

const groupedByArea = computed(() => {
  const groups = []
  let current = null
  for (const item of sortedItems.value) {
    const areaName = item.area?.nombre || 'Sin área'
    if (!current || current.area !== areaName) {
      current = { area: areaName, items: [] }
      groups.push(current)
    }
    current.items.push(item)
  }
  return groups
})

// Organigrama: agrupar items por area con datos jerárquicos
const areasOrganigrama = computed(() => {
  if (!props.areasJerarquia) return []
  const raices = props.areasJerarquia[''] || props.areasJerarquia[null] || props.areasJerarquia['null'] || []
  return raices.map(area => construirNodo(area))
})

function construirNodo(area) {
  const hijos = props.areasJerarquia[area.id] || []
  const itemsArea = sortedItems.value.filter(i => i.id_area === area.id)
  const totalPropuesta = itemsArea.reduce((s, i) => s + (i.propuesta || 0), 0)
  const totalAprobada = itemsArea.reduce((s, i) => s + (i.aprobada || 0), 0)
  const totalCubierta = itemsArea.reduce((s, i) => s + (i.cubierta_real ?? i.cubierta ?? 0), 0)
  return {
    ...area,
    items: itemsArea,
    totalPropuesta,
    totalAprobada,
    totalCubierta,
    subAreas: hijos.map(h => construirNodo(h)),
  }
}

function areaIconClasses(area, level) {
  if (area.imagen) return 'border-blue-400 dark:border-blue-600'
  if (level === 0) return 'bg-blue-500 border-blue-400 dark:bg-blue-700 dark:border-blue-600'
  if (level === 1) return 'bg-emerald-500 border-emerald-400 dark:bg-emerald-700 dark:border-emerald-600'
  return 'bg-amber-500 border-amber-400 dark:bg-amber-700 dark:border-amber-600'
}
</script>

<template>
  <AppLayout :title="title">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <div class="flex items-center gap-2">
            <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
            <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden ml-2">
              <button @click="vista = 'tabla'"
                      class="px-3 py-1.5 text-xs font-medium transition"
                      :class="vista === 'tabla' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'">
                <i class="pi pi-list mr-1"></i> Tabla
              </button>
              <button @click="vista = 'organigrama'"
                      class="px-3 py-1.5 text-xs font-medium transition"
                      :class="vista === 'organigrama' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'">
                <i class="pi pi-sitemap mr-1"></i> Organigrama
              </button>
            </div>
          </div>
        </template>
        <template #end>
          <div class="flex items-center gap-3">
            <Select v-model="idArea" :options="areas" optionLabel="nombre" optionValue="id" placeholder="Filtrar por area" class="w-64" :showClear="true" />
            <InputText v-model="search" placeholder="Buscar..." />
          </div>
        </template>
      </Toolbar>

      <!-- Vista Tabla -->
      <div v-if="vista === 'tabla'">
        <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros" dataKey="id" sortField="area.nombre" :sortOrder="1" rowGroupMode="subheader" groupRowsBy="area.nombre">
          <template #groupheader="{ data }">
            <div class="flex items-center gap-2 py-2">
              <i class="pi pi-building text-blue-500" />
              <span class="font-bold text-blue-800 dark:text-blue-300">{{ data.area?.nombre || 'Sin área' }}</span>
            </div>
          </template>
          <Column header="Cargo">
            <template #body="{ data }">{{ data.cargo?.nombre }}</template>
          </Column>
          <Column field="propuesta" header="Propuesta" style="width:80px" />
          <Column field="aprobada" header="Aprobada" style="width:80px" />
          <Column header="Cubierta" style="width:100px">
            <template #body="{ data }">
              <span :class="`text-${estadoColor(data)}`">{{ cobertura(data) }}</span>
            </template>
          </Column>
          <Column field="necesidad" header="Necesidad" style="width:80px" />
          <Column header="Observaciones">
            <template #body="{ data }">{{ data.observaciones || '—' }}</template>
          </Column>
          <Column header="Acciones" style="width: 120px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
                <Button icon="pi pi-trash" rounded text severity="danger" @click="confirmEliminar(data)" />
              </div>
            </template>
          </Column>
          <template #groupfooter="{ data }">
            <div class="flex gap-6 justify-end py-2 text-sm font-semibold text-gray-600 dark:text-gray-300">
              <span>Propuesta: {{ data.propuesta }}</span>
              <span>Aprobada: {{ data.aprobada }}</span>
              <span>Cubierta: {{ data.cubierta_real ?? data.cubierta }}</span>
            </div>
          </template>
        </DataTable>
      </div>

      <!-- Vista Organigrama -->
      <div v-if="vista === 'organigrama'" class="p-6">
        <div v-if="areasOrganigrama.length" class="flex flex-col items-center gap-6">
          <template v-for="areaRaiz in areasOrganigrama" :key="areaRaiz.id">
            <!-- Nodo raíz -->
            <div class="w-full max-w-md">
              <div class="rounded-xl border-2 border-blue-300 dark:border-blue-700 bg-gradient-to-b from-blue-50 to-white dark:from-blue-900/30 dark:to-gray-800 shadow-md overflow-hidden">
                <!-- Header área -->
                <div class="p-5 flex flex-col items-center text-center border-b border-blue-100 dark:border-blue-800">
                  <div class="w-20 h-20 rounded-full border-3 mb-3 overflow-hidden flex items-center justify-center"
                       :class="areaIconClasses(areaRaiz, 0)">
                    <img v-if="areaRaiz.imagen" :src="areaRaiz.imagen" :alt="areaRaiz.nombre" class="w-full h-full object-cover" />
                    <i v-else class="pi pi-building text-white text-3xl"></i>
                  </div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ areaRaiz.nombre }}</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ areaRaiz.totalPropuesta }} propuesta · {{ areaRaiz.totalAprobada }} aprobada · {{ areaRaiz.totalCubierta }} cubierta
                  </p>
                </div>
                <!-- Items del área -->
                <div v-if="areaRaiz.items.length" class="divide-y divide-gray-100 dark:divide-gray-700">
                  <div v-for="item in areaRaiz.items" :key="item.id" class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/80 transition">
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ item.cargo?.nombre }}</p>
                      <p v-if="item.observaciones" class="text-xs text-gray-400 truncate">{{ item.observaciones }}</p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0 ml-3">
                      <div class="text-right">
                        <span class="text-xs text-gray-500">Prop: {{ item.propuesta }}</span>
                        <span class="text-xs text-gray-500 ml-2">Apr: {{ item.aprobada }}</span>
                        <span class="text-xs ml-2" :class="`text-${estadoColor(item)}`">Cub: {{ item.cubierta_real ?? item.cubierta }}</span>
                      </div>
                      <div class="flex gap-1">
                        <Button icon="pi pi-pencil" rounded text size="small" severity="info" @click="openEdit(item)" />
                        <Button icon="pi pi-trash" rounded text size="small" severity="danger" @click="confirmEliminar(item)" />
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="px-5 py-4 text-center text-xs text-gray-400">Sin puestos asignados</div>
              </div>
            </div>

            <!-- Sub-áreas -->
            <template v-if="areaRaiz.subAreas.length">
              <div class="w-0.5 h-6 bg-blue-300 dark:bg-blue-600"></div>
              <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 w-full">
                <div v-for="sub in areaRaiz.subAreas" :key="sub.id" class="rounded-xl border-2 border-emerald-200 dark:border-emerald-800 bg-gradient-to-b from-emerald-50 to-white dark:from-emerald-900/20 dark:to-gray-800 shadow-sm overflow-hidden">
                  <div class="p-4 flex flex-col items-center text-center border-b border-emerald-100 dark:border-emerald-800">
                    <div class="w-14 h-14 rounded-full border-2 mb-2 overflow-hidden flex items-center justify-center"
                         :class="areaIconClasses(sub, 1)">
                      <img v-if="sub.imagen" :src="sub.imagen" :alt="sub.nombre" class="w-full h-full object-cover" />
                      <i v-else class="pi pi-sitemap text-white text-xl"></i>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ sub.nombre }}</h4>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                      {{ sub.totalPropuesta }} prop · {{ sub.totalAprobada }} apr · {{ sub.totalCubierta }} cub
                    </p>
                  </div>
                  <div v-if="sub.items.length" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="item in sub.items" :key="item.id" class="px-4 py-2 flex items-center justify-between text-sm">
                      <span class="text-gray-700 dark:text-gray-300 truncate">{{ item.cargo?.nombre }}</span>
                      <div class="flex items-center gap-2 shrink-0 ml-2">
                        <span class="text-xs" :class="`text-${estadoColor(item)}`">Cub: {{ item.cubierta_real ?? item.cubierta }}/{{ item.aprobada }}</span>
                        <Button icon="pi pi-pencil" rounded text size="small" severity="info" @click="openEdit(item)" />
                      </div>
                    </div>
                  </div>
                  <div v-else class="px-4 py-3 text-center text-xs text-gray-400">Sin puestos</div>
                </div>
              </div>
            </template>
          </template>
        </div>
        <div v-else class="text-center py-16">
          <i class="pi pi-sitemap text-5xl text-gray-300 dark:text-gray-600 block mb-4" />
          <p class="text-gray-500 dark:text-gray-400 mb-4">No hay áreas configuradas</p>
        </div>
      </div>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Puesto' : 'Nuevo Puesto'" modal style="width: 700px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Area</label>
            <Select v-model="form.id_area" :options="areas" optionLabel="nombre" optionValue="id" filter class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Cargo</label>
            <Select v-model="form.id_cargo" :options="cargos" optionLabel="nombre" optionValue="id" filter class="w-full" required />
          </div>
        </div>
        <!-- Plazas: números en fila de 3 con espacio; Necesidad es una pregunta
             sí/no (¿es realmente necesario el cargo?). -->
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block mb-1 font-medium">Propuesta</label>
            <InputNumber v-model="form.propuesta" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Aprobada</label>
            <InputNumber v-model="form.aprobada" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Cubierta</label>
            <InputNumber v-model="form.cubierta" :min="0" class="w-full" />
          </div>
        </div>
        <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
          <label class="text-sm font-medium">¿Es realmente necesario este cargo?</label>
          <Select
            v-model="form.necesidad"
            :options="[{ label: 'Sí', value: 1 }, { label: 'No', value: 0 }]"
            optionLabel="label"
            optionValue="value"
            class="w-24"
          />
        </div>
        <div>
          <label class="block mb-1 font-medium">Observaciones</label>
          <Textarea v-model="form.observaciones" class="w-full" rows="2" />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
