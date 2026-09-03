<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, agrupadas: Array, tiposCarga: Array, filters: Object })
const toast = useToast()
const confirm = useConfirm()
const search = ref(props.filters?.search || '')
const idTipoCarga = ref(props.filters?.id_tipo_carga || null)
const mostrarTodas = ref(props.filters?.mostrar_todas || false)
const showForm = ref(false)
const editing = ref(null)
const title = 'Tasas Salariales'

function baseForm() {
  return {
    nombre: '', tasa: 0, tasa2: 0, id_tipo_carga: null,
    distancia_1: 0, distancia_2: 0, capacidad_1: 0, capacidad_2: 0,
    fecha_inicio: new Date(), fecha_fin: null,
  }
}
const form = ref(baseForm())

watch([search, idTipoCarga, mostrarTodas], () => reload())

function reload() {
  router.get(route('tasas.index'), {
    search: search.value,
    id_tipo_carga: idTipoCarga.value || '',
    mostrar_todas: mostrarTodas.value ? 1 : '',
  }, { preserveState: true, replace: true })
}

function onPage(event) {
  router.get(route('tasas.index'), {
    page: event.page + 1,
    search: search.value,
    id_tipo_carga: idTipoCarga.value || '',
    mostrar_todas: mostrarTodas.value ? 1 : '',
  }, { preserveState: true, replace: true })
}

function openCreate(tipoCargaId = null) {
  editing.value = null
  form.value = { ...baseForm(), id_tipo_carga: tipoCargaId }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    nombre: item.nombre,
    tasa: item.tasa,
    tasa2: item.tasa2,
    id_tipo_carga: item.id_tipo_carga,
    distancia_1: item.distancia_1,
    distancia_2: item.distancia_2,
    capacidad_1: item.capacidad_1,
    capacidad_2: item.capacidad_2,
    fecha_inicio: item.fecha_inicio ? new Date(item.fecha_inicio) : null,
    fecha_fin: item.fecha_fin ? new Date(item.fecha_fin) : null,
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  payload.fecha_inicio = payload.fecha_inicio ? new Date(payload.fecha_inicio).toISOString().split('T')[0] : null
  payload.fecha_fin = payload.fecha_fin ? new Date(payload.fecha_fin).toISOString().split('T')[0] : null

  const url = editing.value ? route('tasas.update', editing.value.id) : route('tasas.store')
  const method = editing.value ? 'put' : 'post'
  router[method](url, payload, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function confirmEliminar(item) {
  confirm.require({
    message: `¿Eliminar la tasa "${item.nombre}" (v${item.version})?`,
    header: 'Eliminar tasa',
    acceptLabel: 'Si, eliminar',
    rejectLabel: 'No',
    accept: () => {
      router.delete(route('tasas.destroy', item.id), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminado', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
}

function formatDate(d) {
  if (!d) return '—'
  const date = new Date(d)
  if (isNaN(date)) return '—'
  return date.toLocaleDateString('es-CU', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>

<template>
  <AppLayout :title="title">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nueva" icon="pi pi-plus" severity="success" @click="openCreate()" />
        </template>
        <template #end>
          <div class="flex items-center gap-3">
            <Select v-model="idTipoCarga" :options="tiposCarga" optionLabel="nombre" optionValue="id" placeholder="Tipo de carga" class="w-48" showClear />
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="mostrarTodas" class="rounded" />
              Mostrar todas (incluye inactivas)
            </label>
            <InputText v-model="search" placeholder="Buscar..." />
          </div>
        </template>
      </Toolbar>

      <div v-if="agrupadas && agrupadas.length">
        <Accordion :multiple="true" :activeIndex="[0]">
          <AccordionPanel v-for="grupo in agrupadas" :key="grupo.tipo_carga" :value="grupo.tipo_carga">
            <AccordionHeader>
              <div class="flex items-center gap-3 w-full">
                <i class="pi pi-tag text-blue-500"></i>
                <span class="font-bold text-blue-800 dark:text-blue-300">{{ grupo.tipo_carga }}</span>
                <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 font-mono">
                  {{ grupo.tasas.length }} versiones
                </span>
                <Button icon="pi pi-plus" rounded text size="small" severity="success"
                  @click.stop="openCreate(grupo.tasas[0]?.id_tipo_carga)" title="Nueva versión" />
              </div>
            </AccordionHeader>
            <AccordionContent>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                      <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Nombre</th>
                      <th class="text-center py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Versión</th>
                      <th class="text-left py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Vigencia</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Tasa</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Tasa 2</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Dist. 1</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Dist. 2</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Cap. 1</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Cap. 2</th>
                      <th class="text-center py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Estado</th>
                      <th class="text-right py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="tasa in grupo.tasas" :key="tasa.id"
                      class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                      :class="{ 'bg-green-50 dark:bg-green-900/20': tasa.activo }">
                      <td class="py-2 px-3 font-medium">{{ tasa.nombre }}</td>
                      <td class="py-2 px-3 text-center">
                        <Tag :value="`v${tasa.version}`" :severity="tasa.activo ? 'success' : 'secondary'" />
                      </td>
                      <td class="py-2 px-3 text-xs text-gray-500">
                        {{ formatDate(tasa.fecha_inicio) }} — {{ formatDate(tasa.fecha_fin) }}
                      </td>
                      <td class="py-2 px-3 text-right font-mono">{{ Number(tasa.tasa).toFixed(4) }}</td>
                      <td class="py-2 px-3 text-right font-mono">{{ tasa.tasa2 ? Number(tasa.tasa2).toFixed(4) : '—' }}</td>
                      <td class="py-2 px-3 text-right">{{ tasa.distancia_1 }}</td>
                      <td class="py-2 px-3 text-right">{{ tasa.distancia_2 }}</td>
                      <td class="py-2 px-3 text-right">{{ tasa.capacidad_1 }}</td>
                      <td class="py-2 px-3 text-right">{{ tasa.capacidad_2 }}</td>
                      <td class="py-2 px-3 text-center">
                        <Tag :value="tasa.activo ? 'Vigente' : 'Histórica'" :severity="tasa.activo ? 'success' : 'warn'" />
                      </td>
                      <td class="py-2 px-3 text-right">
                        <div class="flex gap-1 justify-end">
                          <Button icon="pi pi-pencil" rounded text size="small" severity="info" @click="openEdit(tasa)" />
                          <Button icon="pi pi-trash" rounded text size="small" severity="danger" @click="confirmEliminar(tasa)" />
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </AccordionContent>
          </AccordionPanel>
        </Accordion>
      </div>
      <div v-else class="text-center py-12">
        <i class="pi pi-inbox text-4xl text-gray-300 dark:text-gray-600 block mb-3" />
        <p class="text-gray-500 dark:text-gray-400">No hay tasas registradas</p>
      </div>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Tasa' : 'Nueva Tasa'" modal style="width: 700px">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block mb-1 font-medium">Nombre *</label>
            <InputText v-model="form.nombre" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tipo de Carga</label>
            <Select v-model="form.id_tipo_carga" :options="tiposCarga" optionLabel="nombre" optionValue="id" placeholder="(Todos)" class="w-full" showClear />
          </div>
          <div></div>
          <div>
            <label class="block mb-1 font-medium">Tasa *</label>
            <InputNumber v-model="form.tasa" :min="0" :minFractionDigits="4" :maxFractionDigits="6" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tasa 2</label>
            <InputNumber v-model="form.tasa2" :min="0" :minFractionDigits="4" :maxFractionDigits="6" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Distancia 1</label>
            <InputNumber v-model="form.distancia_1" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Distancia 2</label>
            <InputNumber v-model="form.distancia_2" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Capacidad 1</label>
            <InputNumber v-model="form.capacidad_1" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Capacidad 2</label>
            <InputNumber v-model="form.capacidad_2" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Inicio Vigencia</label>
            <DatePicker v-model="form.fecha_inicio" dateFormat="dd/mm/yy" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Fecha Fin Vigencia</label>
            <DatePicker v-model="form.fecha_fin" dateFormat="dd/mm/yy" class="w-full" showClear />
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
