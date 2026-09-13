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
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ title: String, agregados: Object, filters: Object, catalogos: Object, estados: Array, tractivos: Array })
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const marcasOptions = computed(() => props.catalogos?.marcas ?? [])
const estadosOptions = computed(() => props.estados ?? [])
const tractivosOptions = computed(() => props.tractivos ?? [])

const baseForm = () => ({
  codigo: '',
  descripcion: '',
  numero_serie: '',
  id_marca: null,
  id_estado: null,
  id_tractivo: null,
  fecha_instalado: null,
  km_acumulados: null,
  km_retirarse: null,
  notas: '',
  fecha_baja: null,
})

const form = ref(baseForm())

watch(search, () => {
  router.get(route('otros-agregados.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
  editing.value = null
  form.value = baseForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    codigo: item.codigo ?? '',
    descripcion: item.descripcion ?? '',
    numero_serie: item.numero_serie ?? '',
    id_marca: item.id_marca ?? null,
    id_estado: item.id_estado ?? null,
    id_tractivo: item.id_tractivo ?? null,
    fecha_instalado: item.fecha_instalado ?? null,
    km_acumulados: item.km_acumulados ?? null,
    km_retirarse: item.km_retirarse ?? null,
    notas: item.notas ?? '',
    fecha_baja: item.fecha_baja ?? null,
  }
  showForm.value = true
}

function submit() {
  const payload = { ...form.value }
  const url = editing.value ? route('otros-agregados.update', { otros_agregado: editing.value.id }) : route('otros-agregados.store')
  router[editing.value ? 'put' : 'post'](url, payload, { onSuccess: () => { showForm.value = false } })
}

function destroy(item) {
  confirmDialog.require({
    message: `¿Eliminar el agregado ${item.descripcion ?? item.codigo ?? item.id}?`,
    header: 'Eliminar Agregado', icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar', rejectLabel: 'Volver', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('otros-agregados.destroy', { otros_agregado: item.id })),
  })
}
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start><h2 class="text-xl font-bold m-0">{{ title ?? 'Otros Agregados' }}</h2></template>
      <template #end>
        <div class="flex gap-2">
          <InputText v-model="search" placeholder="Buscar por descripción..." class="w-64" />
          <Button icon="pi pi-plus" label="Nuevo agregado" @click="openCreate" />
        </div>
      </template>
    </Toolbar>

    <DataTable lazy :value="agregados.data" paginator :rows="agregados.per_page" :totalRecords="agregados.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(agregados.current_page - 1) * agregados.per_page"
      stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="codigo" header="Código" sortable style="width:120px" />
      <Column field="descripcion" header="Descripción" sortable />
      <Column field="numero_serie" header="Nro. Serie" sortable />
      <Column field="marca" header="Marca" sortable>
        <template #body="{ data }">{{ data.marca?.nombre || '—' }}</template>
      </Column>
      <Column field="estado" header="Estado" style="width:160px">
        <template #body="{ data }">{{ data.estado?.nombre || '—' }}</template>
      </Column>
      <Column field="tractivo" header="Tractivo (entidad)" style="width:170px">
        <template #body="{ data }">{{ data.tractivo?.descripcion || data.tractivo?.codigo || '—' }}</template>
      </Column>
      <Column field="fecha_baja" header="Fecha baja" style="width:130px">
        <template #body="{ data }">{{ data.fecha_baja ?? '—' }}</template>
      </Column>
      <Column header="Acciones" style="width:160px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" title="Editar" @click="openEdit(data)" />
          <Button icon="pi pi-trash" text rounded severity="danger" title="Eliminar" @click="destroy(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showForm" :header="editing ? 'Editar agregado' : 'Nuevo agregado'" :style="{ width: '600px' }" modal>
      <div class="grid grid-cols-2 gap-3 mt-2">
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Código</label><InputText v-model="form.codigo" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Nro. Serie</label><InputText v-model="form.numero_serie" /></div>
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Descripción</label><InputText v-model="form.descripcion" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Marca</label><Select v-model="form.id_marca" :options="marcasOptions" optionLabel="nombre" optionValue="id" class="w-full" showClear placeholder="Seleccione" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Estado</label><Select v-model="form.id_estado" :options="estadosOptions" optionLabel="nombre" optionValue="id" class="w-full" showClear placeholder="Seleccione" /></div>
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Tractivo (define la entidad)</label><Select v-model="form.id_tractivo" :options="tractivosOptions" optionLabel="descripcion" optionValue="id" class="w-full" placeholder="Seleccione tractivo*" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha instalado</label><InputText type="date" v-model="form.fecha_instalado" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Fecha baja</label><InputText type="date" v-model="form.fecha_baja" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Km acumulados</label><InputText type="number" v-model="form.km_acumulados" /></div>
        <div class="flex flex-col gap-1"><label class="text-sm font-medium">Km retirarse</label><InputText type="number" v-model="form.km_retirarse" /></div>
        <div class="flex flex-col gap-1 col-span-2"><label class="text-sm font-medium">Notas</label><InputText v-model="form.notas" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-check" @click="submit" />
      </template>
    </Dialog>
  </AppLayout>
</template>
