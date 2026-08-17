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
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ title: String, items: Object, filters: Object, catalogos: Object })
const toast = useToast()
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const baseForm = () => ({
  descripcion: '',
  placa: '',
  id_tipo_vehiculo: null,
  marca: '',
  modelo: '',
  anno: null,
  capacidad_toneladas: null,
  lot: '',
  circulacion: '',
  estado: 'activo',
})

const form = ref(baseForm())

const tipoLabel = (id) => props.catalogos?.tiposArrastre?.find((t) => t.value === id)?.label ?? '—'

// Al elegir el tipo de arrastre, hereda marca/modelo/año de la ficha.
function aplicarFicha() {
  const id = form.value.id_tipo_vehiculo
  const tipo = props.catalogos?.tiposArrastre?.find((t) => t.value === id)
  form.value.marca = tipo?.ficha?.marca || ''
  form.value.modelo = tipo?.ficha?.modelo || ''
  form.value.anno = tipo?.ficha?.anno || null
}

watch(search, () => {
  router.get(route('arrastres.index'), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route('arrastres.index'), {
    page: event.page + 1,
    search: search.value,
  }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    descripcion: item.descripcion ?? '',
    placa: item.placa ?? '',
    id_tipo_vehiculo: item.id_tipo_vehiculo ?? null,
    marca: item.tipo_ficha?.marca ?? item.marca ?? '',
    modelo: item.tipo_ficha?.modelo ?? item.modelo ?? '',
    anno: item.tipo_ficha?.anno ?? item.anno ?? null,
    capacidad_toneladas: item.capacidad_toneladas ?? null,
    lot: item.lot ?? '',
    circulacion: item.circulacion ?? '',
    estado: item.estado ?? 'activo',
  }
  showForm.value = true
}

function submit() {
  const payload = {
    descripcion: form.value.descripcion,
    placa: form.value.placa,
    id_tipo_vehiculo: form.value.id_tipo_vehiculo,
    capacidad_toneladas: form.value.capacidad_toneladas,
    lot: form.value.lot,
    circulacion: form.value.circulacion,
    estado: form.value.estado,
  }
  const url = editing.value
    ? route('arrastres.update', { tractivo: editing.value.id })
    : route('arrastres.store')
  router[editing.value ? 'put' : 'post'](url, payload, {
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Guardado', detail: editing.value ? 'Arrastre actualizado.' : 'Arrastre creado.', life: 3000 })
      showForm.value = false
    },
  })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar el arrastre ${item.descripcion ?? item.id}?`,
    header: 'Eliminar Arrastre',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('arrastres.destroy', { tractivo: item.id }), {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Arrastre eliminado.', life: 3000 })
        },
      })
    },
  })
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start>
        <h2 class="text-xl font-bold m-0">{{ title ?? 'Arrastres' }}</h2>
      </template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por descripción o placa..." class="w-64" />
          <Button icon="pi pi-plus" label="Nuevo arrastre" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="items.data" paginator :rows="items.per_page" :totalRecords="items.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(items.current_page - 1) * items.per_page"
      @page="onPage" stripedRows class="p-datatable-sm" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="descripcion" header="Descripción" sortable />
      <Column field="placa" header="Placa" sortable />
      <Column header="Tipo">
        <template #body="{ data }">{{ data.tipo_vehiculo_label || '—' }}</template>
      </Column>
      <Column field="capacidad_toneladas" header="Capacidad" sortable />
      <Column field="lot" header="LOT" />
      <Column field="circulacion" header="Circulación" />
      <Column field="estado" header="Estado" style="width:110px">
        <template #body="{ data }">
          <Tag :value="data.estado || 'activo'" :severity="(data.estado || 'activo') === 'activo' ? 'success' : 'warn'" />
        </template>
      </Column>
      <Column header="Acciones" style="width:120px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" />
          <Button icon="pi pi-trash" text rounded severity="danger" @click="destroy(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar arrastre' : 'Nuevo arrastre'"
      :style="{ width: '560px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1">
          <label for="desc" class="text-sm font-medium">Descripción *</label>
          <InputText id="desc" v-model="form.descripcion" />
        </div>
        <div class="flex flex-col gap-1">
          <label for="placa" class="text-sm font-medium">Placa *</label>
          <InputText id="placa" v-model="form.placa" />
        </div>
        <div class="flex flex-col gap-1 col-span-2">
          <label for="tipo" class="text-sm font-medium">Tipo de arrastre</label>
          <Select id="tipo" v-model="form.id_tipo_vehiculo" :options="catalogos?.tiposArrastre ?? []"
            optionLabel="label" optionValue="value" placeholder="Seleccione" showClear class="w-full" @change="aplicarFicha" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Marca <i class="pi pi-lock text-xs text-surface-400 ml-1" /></label>
          <InputText v-model="form.marca" readonly />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Modelo <i class="pi pi-lock text-xs text-surface-400 ml-1" /></label>
          <InputText v-model="form.modelo" readonly />
        </div>
        <div class="flex flex-col gap-1 col-span-2">
          <label class="text-sm font-medium">Año <i class="pi pi-lock text-xs text-surface-400 ml-1" /></label>
          <InputText :value="form.anno" readonly />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Capacidad</label>
          <InputText v-model="form.capacidad_toneladas" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">LOT</label>
          <InputText v-model="form.lot" />
        </div>
        <div class="flex flex-col gap-1 col-span-2">
          <label class="text-sm font-medium">Circulación</label>
          <InputText v-model="form.circulacion" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>