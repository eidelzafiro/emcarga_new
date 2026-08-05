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
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object, catalogConfig: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const continuar = ref(false)

const baseForm = () => ({ nombre: '', activo: true })

const form = ref(baseForm())

watch(search, () => {
  router.get(route(`${props.catalogConfig.route}.index`), { search: search.value }, { preserveState: true, replace: true })
})

const onPage = (event) => {
  router.get(route(`${props.catalogConfig.route}.index`), { page: event.page + 1, search: search.value }, { preserveState: true, replace: true })
}

const allFields = computed(() => props.catalogConfig?.fields || {})

const gridFields = computed(() => props.catalogConfig?.gridFields || {})

const activosCount = computed(() => {
  if (!props.items?.data) return null
  return props.items.data.filter(i => i.activo).length
})

function getSelectLabel(options, value) {
  if (!options || value === null || value === undefined) return ''
  const opt = options.find(o => o.value === value)
  return opt ? opt.label : value
}

function getFormFields() {
  const fields = {}
  Object.entries(allFields.value).forEach(([k, v]) => {
    if (k !== 'nombre') fields[k] = v
  })
  return fields
}

function openCreate() {
  editing.value = null
  continuar.value = false
  const f = { ...baseForm() }
  Object.entries(getFormFields()).forEach(([k, v]) => {
    if (v.type === 'number') f[k] = null
    else if (v.type === 'boolean') f[k] = false
    else f[k] = ''
  })
  form.value = f
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  continuar.value = false
  const f = { ...baseForm() }
  f.nombre = item.nombre
  f.activo = Boolean(item.activo)
  Object.entries(getFormFields()).forEach(([k, v]) => {
    if (v.type === 'boolean') f[k] = Boolean(item[k])
    else if (v.type === 'number') f[k] = item[k] ?? null
    else f[k] = item[k] ?? ''
  })
  form.value = f
  showForm.value = true
}

function submit(continuarActivo = false) {
  const rt = props.catalogConfig.route
  const payload = { ...form.value, _continuar: continuarActivo }
  const url = editing.value ? route(`${rt}.update`, { id: editing.value.id }) : route(`${rt}.store`)
  const method = editing.value ? 'put' : 'post'
  router[method](url, payload, {
    onSuccess: () => {
      toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 })
      if (continuarActivo && !editing.value) {
        const f = { ...baseForm() }
        Object.entries(getFormFields()).forEach(([k, v]) => {
          if (v.type === 'number') f[k] = null
          else if (v.type === 'boolean') f[k] = false
          else f[k] = ''
        })
        form.value = f
        continuar.value = true
      } else {
        showForm.value = false
        continuar.value = false
      }
    },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}
</script>

<template>
  <AppLayout :title="catalogConfig?.title || 'Cargos'">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
          <span v-if="items.total !== undefined" class="ml-3 text-xs text-gray-500 dark:text-gray-400">
            {{ items.total }} registros
            <span v-if="activosCount !== null" class="ml-1">· {{ activosCount }} activos</span>
          </span>
        </template>
        <template #end>
          <InputText v-model="search" placeholder="Buscar..." />
        </template>
      </Toolbar>

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total"
        :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" class="text-sm" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column field="nombre" header="Nombre" sortable />
        <template v-for="(cfg, key) in gridFields" :key="key">
          <Column :field="key" :header="cfg.label">
            <template #body="{ data }">
              <span v-if="cfg.type === 'select' && cfg.options">{{ getSelectLabel(cfg.options, data[key]) }}</span>
              <span v-else-if="cfg.type === 'boolean'">
                <i :class="data[key] ? 'pi pi-check text-green-600' : 'pi pi-times text-red-500'" />
              </span>
              <span v-else>{{ data[key] }}</span>
            </template>
          </Column>
        </template>
        <Column field="activo" header="Activo" style="width:80px">
          <template #body="{ data }">
            <i :class="data.activo ? 'pi pi-check text-green-600' : 'pi pi-times text-red-500'" />
          </template>
        </Column>
        <Column header="Acciones" style="width:100px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger"
                @click="router.delete(route(`${catalogConfig.route}.destroy`, { id: data.id }))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? `Editar ${catalogConfig?.title}` : `Nuevo ${catalogConfig?.title}`" modal style="width:650px">
      <form @submit.prevent="submit(false)" class="space-y-4 overflow-y-auto max-h-[70vh]">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block mb-1 font-medium">Nombre del Cargo</label>
            <InputText v-model="form.nombre" class="w-full" required />
          </div>
          <template v-for="(cfg, key) in allFields" :key="key">
            <div v-if="key !== 'nombre' && key !== 'activo'" :class="cfg.type === 'textarea' ? 'col-span-2' : ''">
              <label class="block mb-1 font-medium">{{ cfg.label }}</label>
              <InputNumber v-if="cfg.type === 'number'" v-model="form[key]" class="w-full" />
              <Select v-else-if="cfg.type === 'select' && cfg.options" v-model="form[key]" :options="cfg.options" optionLabel="label" optionValue="value" placeholder="Seleccionar..." class="w-full" :showClear="!cfg.required" />
              <div v-else-if="cfg.type === 'boolean'" class="flex items-center gap-2 pt-2">
                <ToggleSwitch v-model="form[key]" :inputId="'fld-' + key" />
                <label :for="'fld-' + key" class="text-sm">{{ cfg.label }}</label>
              </div>
              <InputText v-else v-model="form[key]" class="w-full" />
            </div>
          </template>
        </div>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="form.activo" inputId="activo" />
          <label for="activo" class="font-medium">Activo</label>
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button v-if="!editing" label="Guardar y continuar" type="button" icon="pi pi-save" @click="submit(true)" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
