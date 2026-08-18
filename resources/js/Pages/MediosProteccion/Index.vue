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
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, tipos: Array, filters: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const form = ref({ nombre: '', id_tipo_medio_proteccion: null, duracion: null, tipo_duracion: '', activo: true })
const title = 'Medios de Protección'

watch(search, () => {
  router.get(route('medios-proteccion.index'), { search: search.value }, { preserveState: true, replace: true })
})

function onPage(event) {
  router.get(route('medios-proteccion.index'), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

function openCreate() {
  editing.value = null
  form.value = { nombre: '', id_tipo_medio_proteccion: null, duracion: null, tipo_duracion: '', activo: true }
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    nombre: item.nombre, id_tipo_medio_proteccion: item.id_tipo_medio_proteccion, duracion: item.duracion,
    tipo_duracion: item.tipo_duracion || '', activo: Boolean(item.activo),
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('medios-proteccion.update', editing.value.id) : route('medios-proteccion.store')
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

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total" :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="nombre" header="Nombre" />
        <Column header="Tipo">
          <template #body="{ data }">{{ data.tipoMedioProteccion?.nombre }}</template>
        </Column>
        <Column field="duracion" header="Duración" />
        <Column field="tipo_duracion" header="Tipo Duración" />
        <Column header="Activo">
          <template #body="{ data }">
            <span v-if="data.activo" class="pi pi-check text-green-500"></span>
            <span v-else class="pi pi-times text-red-400"></span>
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="router.delete(route('medios-proteccion.destroy', data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar Medio' : 'Nuevo Medio'" modal style="width: 500px">
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Nombre</label>
          <InputText v-model="form.nombre" class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Tipo de medio</label>
          <Select v-model="form.id_tipo_medio_proteccion" :options="tipos" optionLabel="nombre" optionValue="id" filter placeholder="Seleccione el tipo..." class="w-full" :showClear="true" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">Duración</label>
            <InputNumber v-model="form.duracion" :min="0" class="w-full" />
          </div>
          <div>
            <label class="block mb-1 font-medium">Tipo de duración</label>
            <InputText v-model="form.tipo_duracion" class="w-full" />
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="form.activo" :binary="true" input-id="activo" />
          <label for="activo">Activo</label>
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
