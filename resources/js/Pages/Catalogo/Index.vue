<script setup>
import { ref, watch, computed } from 'vue'
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
import ToggleSwitch from 'primevue/toggleswitch'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object, catalogConfig: Object })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

const baseForm = () => ({
  codigo: props.catalogConfig?.codigoManual !== false ? '' : undefined,
  nombre: '',
  activo: true,
})

const form = ref(baseForm())

watch(search, () => {
  router.get(route(`${props.catalogConfig.route}.index`), { search: search.value }, { preserveState: true, replace: true })
})

const allFields = computed(() => {
  const base = props.catalogConfig?.fields || {}
  const extra = props.catalogConfig?.extra || {}
  const merged = { ...base }
  Object.keys(extra).forEach((k) => { if (!merged[k]) merged[k] = extra[k] })
  return merged
})

const gridFields = computed(() => {
  const result = {}
  if (props.catalogConfig?.codigoManual !== false) {
    result.codigo = { label: 'Código', type: 'text' }
  }
  Object.entries(allFields.value).forEach(([k, v]) => {
    if (k !== 'activo') result[k] = v
  })
  return result
})

function typeToComponent(type) {
  if (!type || type === 'text') return 'InputText'
  return type
}

function openCreate() {
  editing.value = null
  const f = { ...baseForm() }
  Object.entries(props.catalogConfig?.extra || {}).forEach(([k, v]) => {
    if (v.type === 'number') f[k] = null
    else if (v.type === 'boolean') f[k] = false
    else f[k] = ''
  })
  form.value = f
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  const f = { ...baseForm() }
  if (props.catalogConfig?.codigoManual !== false) f.codigo = item.codigo
  f.nombre = item.nombre
  f.activo = Boolean(item.activo)
  Object.entries(props.catalogConfig?.extra || {}).forEach(([k]) => {
    f[k] = item[k] ?? (gridFields.value[k]?.type === 'number' ? null : '')
  })
  form.value = f
  showForm.value = true
}

function submit() {
  const rt = props.catalogConfig.route
  const url = editing.value ? route(`${rt}.update`, editing.value.id) : route(`${rt}.store`)
  const method = editing.value ? 'put' : 'post'
  router[method](url, form.value, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}
</script>

<template>
  <AppLayout :title="catalogConfig?.title || 'Catálogo'">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
        </template>
        <template #end>
          <InputText v-model="search" placeholder="Buscar..." />
        </template>
      </Toolbar>

      <DataTable :value="items.data" striped-rows paginator :rows="20" :total-records="items.total">
        <Column v-if="catalogConfig?.codigoManual !== false" field="codigo" header="Código" sortable />
        <Column field="nombre" header="Nombre" sortable />
        <Column v-for="(cfg, key) in gridFields" :key="key" v-if="key !== 'nombre' && key !== 'codigo' && key !== 'activo'" :field="key" :header="cfg.label" />
        <Column field="activo" header="Activo" :style="{ width: '100px' }">
          <template #body="{ data }">
            <i v-if="data.activo !== undefined" :class="data.activo ? 'pi pi-check text-green-600' : 'pi pi-times text-red-500'" />
          </template>
        </Column>
        <Column header="Acciones" :style="{ width: '120px' }">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger"
                @click="router.delete(route(`${catalogConfig.route}.destroy`, data.id))" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? `Editar ${catalogConfig?.title}` : `Nuevo ${catalogConfig?.title}`" modal :style="{ width: catalogConfig?.extra && Object.keys(catalogConfig.extra).length > 6 ? '800px' : '550px' }">
      <form @submit.prevent="submit" class="space-y-4 overflow-y-auto max-h-[70vh]">
        <div class="grid grid-cols-2 gap-4">
          <div v-if="catalogConfig?.codigoManual !== false">
            <label class="block mb-1 font-medium">Código</label>
            <InputText v-model="form.codigo" class="w-full" required />
          </div>
          <div>
            <label class="block mb-1 font-medium">Nombre</label>
            <InputText v-model="form.nombre" class="w-full" required />
          </div>
          <template v-for="(cfg, key) in (catalogConfig?.fields || {})" :key="key">
            <div v-if="key !== 'nombre' && key !== 'codigo' && key !== 'activo'" :class="cfg.type === 'textarea' ? 'col-span-2' : ''">
              <label class="block mb-1 font-medium">{{ cfg.label }}</label>
              <InputNumber v-if="cfg.type === 'number'" v-model="form[key]" class="w-full" />
              <Textarea v-else-if="cfg.type === 'textarea'" v-model="form[key]" class="w-full" :rows="3" />
              <Select v-else-if="cfg.type === 'select' && cfg.options" v-model="form[key]" :options="cfg.options" optionLabel="label" optionValue="value" placeholder="Seleccionar..." class="w-full" :showClear="true" />
              <InputText v-else v-model="form[key]" class="w-full" :type="cfg.type === 'email' ? 'email' : 'text'" />
            </div>
          </template>
          <template v-for="(cfg, key) in (catalogConfig?.extra || {})" :key="'x-' + key">
            <div v-if="!(catalogConfig?.fields || {})[key] && key !== 'activo'" :class="cfg.type === 'textarea' ? 'col-span-2' : ''">
              <label class="block mb-1 font-medium">{{ cfg.label }}</label>
              <InputNumber v-if="cfg.type === 'number'" v-model="form[key]" class="w-full" />
              <Select v-else-if="cfg.type === 'select' && cfg.options" v-model="form[key]" :options="cfg.options" optionLabel="label" optionValue="value" placeholder="Seleccionar..." class="w-full" :showClear="true" />
              <InputText v-else v-model="form[key]" class="w-full" :type="cfg.type === 'email' ? 'email' : 'text'" />
            </div>
          </template>
        </div>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="form.activo" inputId="activo" />
          <label for="activo" class="font-medium">Activo</label>
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showForm = false" />
          <Button label="Guardar" type="submit" icon="pi pi-save" />
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
