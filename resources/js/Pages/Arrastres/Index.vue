<template>
  <AppLayout>
    <Card>
      <template #title>Arrastres</template>
      <template #subtitle>Gestión de arrastres de la flota</template>
      <template #content>
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
          <div class="relative w-full sm:w-72">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
            <InputText v-model="search" placeholder="Buscar por código o placa…" class="w-full pl-9" @input="debouncedSearch" />
          </div>
          <div class="flex gap-2">
            <Button icon="pi pi-plus" label="Nuevo arrastre" @click="openCreate" />
          </div>
        </div>

        <DataTable :value="items.data" stripedRows size="small" :rows="items.per_page" :paginator="true"
          :totalRecords="items.total" :first="(items.current_page - 1) * items.per_page" @page="onPage"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
          currentPageReportTemplate="Total: {totalRecords} registros">
          <Column field="codigo" header="Código" sortable />
          <Column field="placa" header="Chapa" sortable />
          <Column header="Tipo de arrastre">
            <template #body="{ data }">{{ data.tipo_vehiculo_label || '—' }}</template>
          </Column>
          <Column header="Color primario">
            <template #body="{ data }">{{ colorLabel(data.id_color_primario) }}</template>
          </Column>
          <Column header="Color secundario">
            <template #body="{ data }">{{ colorLabel(data.id_color_secundario) }}</template>
          </Column>
          <Column field="tara" header="Tara" sortable />
          <Column field="indice_aceite" header="Índice aceite" sortable />
          <Column field="estado" header="Estado" style="width:110px">
            <template #body="{ data }">
              <Tag :value="data.estado || 'activo'" :severity="(data.estado || 'activo') === 'activo' ? 'success' : 'warn'" />
            </template>
          </Column>
          <Column header="Acciones" :exportable="false">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-pencil" severity="secondary" text rounded size="small" @click="openEdit(data)" v-tooltip.left="'Editar'" />
                <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDelete(data)" v-tooltip.left="'Eliminar'" />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-gray-400">
              <i class="pi pi-truck text-3xl mb-2 block" />
              No se encontraron arrastres.
            </div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar arrastre' : 'Nuevo arrastre'" :modal="true" :style="{ width: '640px' }">
      <form @submit.prevent="submit">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
            <InputText v-model="form.codigo" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Chapa *</label>
            <InputText v-model="form.placa" required class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de arrastre</label>
            <Select v-model="form.id_tipo_vehiculo" :options="catalogos.tiposArrastre ?? []"
              optionLabel="label" optionValue="value" class="w-full" showClear placeholder="Seleccione" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color primario</label>
            <Select v-model="form.id_color_primario" :options="coloresOptions" optionLabel="label" optionValue="value" class="w-full" showClear />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color secundario</label>
            <Select v-model="form.id_color_secundario" :options="coloresOptions" optionLabel="label" optionValue="value" class="w-full" showClear />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tara</label>
            <InputNumber v-model="form.tara" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Índice aceite</label>
            <InputNumber v-model="form.indice_aceite" mode="decimal" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <Select v-model="form.estado" :options="estadosOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha alta</label>
            <InputText v-model="form.fecha_alta" type="date" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha baja</label>
            <InputText v-model="form.fecha_baja" type="date" class="w-full" />
          </div>
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="showForm = false" />
        <Button :label="editing ? 'Actualizar' : 'Crear'" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'
import { debounce } from 'lodash'

const props = defineProps({ title: String, items: Object, filters: Object, catalogos: Object })
const confirmDialog = useConfirm()

const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(false)
const form = ref(baseForm())

const coloresOptions = computed(() => (props.catalogos?.colores ?? []).map((c) => ({ value: c.id, label: c.nombre })))
const estadosOptions = [
  { label: 'Activo', value: 'activo' },
  { label: 'Baja', value: 'baja' },
]

const colorLabel = (id) => coloresOptions.value.find((c) => c.value === id)?.label ?? '—'

function baseForm() {
  return {
    id: null,
    codigo: '',
    placa: '',
    id_tipo_vehiculo: null,
    id_color_primario: null,
    id_color_secundario: null,
    tara: null,
    indice_aceite: null,
    estado: 'activo',
    fecha_alta: null,
    fecha_baja: null,
  }
}

const debouncedSearch = debounce(() => {
  router.get(route('arrastres.index'), { search: search.value }, { preserveState: true, replace: true })
}, 300)

const onPage = (event) => {
  router.get(route('arrastres.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = false
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = true
  form.value = {
    id: item.id,
    codigo: item.codigo ?? '',
    placa: item.placa ?? '',
    id_tipo_vehiculo: item.id_tipo_vehiculo ?? null,
    id_color_primario: item.id_color_primario ?? null,
    id_color_secundario: item.id_color_secundario ?? null,
    tara: item.tara ?? null,
    indice_aceite: item.indice_aceite ?? null,
    estado: item.estado ?? 'activo',
    fecha_alta: item.fecha_alta ?? null,
    fecha_baja: item.fecha_baja ?? null,
  }
  showForm.value = true
}

function submit() {
  const payload = {
    codigo: form.value.codigo,
    placa: form.value.placa,
    id_tipo_vehiculo: form.value.id_tipo_vehiculo,
    id_color_primario: form.value.id_color_primario,
    id_color_secundario: form.value.id_color_secundario,
    tara: form.value.tara,
    indice_aceite: form.value.indice_aceite,
    estado: form.value.estado,
    fecha_alta: form.value.fecha_alta,
    fecha_baja: form.value.fecha_baja,
  }
  const url = editing.value
    ? route('arrastres.update', { arrastre: form.value.id })
    : route('arrastres.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => { showForm.value = false },
  })
}

function confirmDelete(item) {
  confirmDialog.require({
    message: `¿Eliminar el arrastre ${item.placa ?? item.id}?`,
    header: 'Eliminar Arrastre',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => router.delete(route('arrastres.destroy', { arrastre: item.id })),
  })
}
</script>
