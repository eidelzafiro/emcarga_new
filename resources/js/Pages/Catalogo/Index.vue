<script setup>
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Paginator from 'primevue/paginator'
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
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({ items: Object, filters: Object, catalogConfig: Object })
const tipo = computed(() => props.catalogConfig?.tipo)
// Tipos de equipos: vista de tarjetas con imagen grande + subida de archivo
const esEquipos = computed(() => tipo.value === 'tipos_equipos' || props.catalogConfig?.route === 'tipos-equipos')
const toast = useToast()
const confirmDialog = useConfirm()
const search = ref(props.filters?.search || '')
const filterModel = ref({})
const showForm = ref(false)
const editing = ref(null)
const continuar = ref(false)
let searchTimer = null

const catalogFilters = computed(() => props.catalogConfig?.filters || {})

const baseForm = () => ({
  codigo: props.catalogConfig?.codigoManual !== false ? '' : undefined,
  nombre: '',
  activo: true,
})

const form = ref(baseForm())

const aplicaFiltro = () => {
  router.get(route(`${props.catalogConfig.route}.index`, { tipo: props.catalogConfig.tipo }), {
    search: search.value,
    id_marca: filterModel.value.id_marca,
    id_modelo: filterModel.value.id_modelo,
    id_pais: filterModel.value.id_pais,
    tipo_equipo: filterModel.value.tipo_equipo ?? filterModel.value.id_tipo_equipo,
    cantidad_vehiculos: filterModel.value.cantidad_vehiculos,
  }, { preserveState: true, replace: true })
}

const onSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => aplicaFiltro(), 350)
}

watch(search, () => {
  onSearch()
})

watch(catalogFilters, (f) => {
  const actual = {}
  Object.keys(f).forEach((k) => {
    const cfg = f[k]
    actual[cfg.key] = props.filters?.[cfg.key] || null
  })
  filterModel.value = actual
}, { immediate: true })

const onPage = (event) => {
  router.get(route(`${props.catalogConfig.route}.index`, { tipo: props.catalogConfig.tipo }), {
    page: event.page + 1,
    search: search.value,
    id_marca: filterModel.value.id_marca,
    id_modelo: filterModel.value.id_modelo,
    id_pais: filterModel.value.id_pais,
    tipo_equipo: filterModel.value.tipo_equipo ?? filterModel.value.id_tipo_equipo,
    cantidad_vehiculos: filterModel.value.cantidad_vehiculos,
  }, { preserveState: true, replace: true })
}

const allFields = computed(() => {
  const base = props.catalogConfig?.fields || {}
  const extra = props.catalogConfig?.extra || {}
  const merged = { ...base }
  Object.keys(extra).forEach((k) => { if (!merged[k]) merged[k] = extra[k] })
  return merged
})

const activosCount = computed(() => {
  if (!props.items?.data) return null
  return props.items.data.filter(i => i.activo).length
})

// Mostrar columna de miniaturas solo si algún ítem tiene imagen (ej. tipos_equipos)
const tieneImagenes = computed(() => (props.items?.data || []).some(i => i.imagen))

const gridFields = computed(() => {
  const result = {}
  if (props.catalogConfig?.codigoManual !== false) {
    result.codigo = { label: 'Código', type: 'text' }
  }
  const permitidas = props.catalogConfig?.gridOnly
  const orden = permitidas?.length ? permitidas : Object.keys(allFields.value)
  orden.forEach((k) => {
    if (k === 'activo' || !allFields.value[k]) return
    result[k] = allFields.value[k]
  })
  return result
})

function typeToComponent(type) {
  if (!type || type === 'text') return 'InputText'
  return type
}

function getSelectLabel(options, value) {
  if (!options || value === null || value === undefined) return ''
  const opt = options.find(o => o.value === value)
  return opt ? opt.label : value
}

function getFormFields() {
  const fields = { ...(props.catalogConfig?.extra || {}) }
  Object.entries(props.catalogConfig?.fields || {}).forEach(([k, v]) => {
    if (k !== 'nombre' && k !== 'codigo') fields[k] = v
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
  limpiarArchivo()
  limpiarLogo()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  continuar.value = false
  const f = { ...baseForm() }
  if (props.catalogConfig?.codigoManual !== false) f.codigo = item.codigo
  f.nombre = item.nombre
  f.activo = Boolean(item.activo)
  Object.entries(getFormFields()).forEach(([k, v]) => {
    if (v.type === 'boolean') f[k] = Boolean(item[k])
    else if (v.type === 'number') f[k] = item[k] ?? null
    else f[k] = item[k] ?? ''
  })
  form.value = f
  previewImagen.value = esEquipos.value ? (item.imagen || null) : null
  archivoImagen.value = null
  errorArchivo.value = ''
  previewLogo.value = (tipo.value === 'marcas') ? (item.logo || null) : null
  archivoLogo.value = null
  errorLogo.value = ''
  showForm.value = true
}

// ── Imagen para tipos de equipos ──────────────────────────────────────
const archivoImagen = ref(null)
const previewImagen = ref(null)
const errorArchivo = ref('')
const EXTENSIONES_OK = ['image/jpeg', 'image/png', 'image/webp']

function limpiarArchivo() {
  archivoImagen.value = null
  previewImagen.value = null
  errorArchivo.value = ''
}

// ── Logo para marcas ──────────────────────────────────────────────────
const archivoLogo = ref(null)
const previewLogo = ref(null)
const errorLogo = ref('')
const EXT_LOGO = ['image/jpeg', 'image/png', 'image/webp']

function limpiarLogo() {
  archivoLogo.value = null
  previewLogo.value = null
  errorLogo.value = ''
}

function seleccionarLogo(evento) {
  const file = evento.target.files?.[0]
  evento.target.value = ''
  if (!file) return
  if (!EXT_LOGO.includes(file.type)) {
    errorLogo.value = 'Formatos permitidos: JPG, PNG o WEBP.'
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    errorLogo.value = 'El logo no puede superar 2 MB.'
    return
  }
  errorLogo.value = ''
  archivoLogo.value = file
  previewLogo.value = URL.createObjectURL(file)
}

function seleccionarArchivo(evento) {
  const file = evento.target.files?.[0]
  evento.target.value = ''
  if (!file) return
  if (!EXTENSIONES_OK.includes(file.type)) {
    errorArchivo.value = 'Formatos permitidos: JPG, PNG o WEBP.'
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    errorArchivo.value = 'La imagen no puede superar 2 MB.'
    return
  }
  errorArchivo.value = ''
  archivoImagen.value = file
  previewImagen.value = URL.createObjectURL(file)
}

function confirmarBorrado(item) {
  confirmDialog.require({
    message: `¿Eliminar "${item.nombre}"? Esta acción no se puede deshacer.`,
    header: 'Eliminar registro',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route(`${props.catalogConfig.route}.destroy`, { tipo: props.catalogConfig.tipo, id: item.id }), {
        onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminado', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
      })
    },
  })
}

function submit(continuarActivo = false) {  const rt = props.catalogConfig.route
  const url = editing.value ? route(`${rt}.update`, { tipo: tipo.value, id: editing.value.id }) : route(`${rt}.store`, { tipo: tipo.value })

  // Tipos de equipos: FormData (permite adjuntar la imagen del equipo).
  if (esEquipos.value) {
    if (errorArchivo.value) return
    const fd = new FormData()
    fd.append('nombre', form.value.nombre || '')
    fd.append('activo', form.value.activo ? '1' : '0')
    if (form.value.codigo) fd.append('codigo', form.value.codigo)
    // El path manual solo aplica al crear sin archivo nuevo
    if (!archivoImagen.value && !editing.value && form.value.imagen) {
      fd.append('imagen', form.value.imagen)
    }
    if (archivoImagen.value) fd.append('imagen_archivo', archivoImagen.value)
    if (continuarActivo) fd.append('_continuar', '1')
    if (editing.value) fd.append('_method', 'PUT')
    router.post(url, fd, {
      onSuccess: () => {
        toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 })
        showForm.value = false
        continuar.value = false
        limpiarArchivo()
      },
      onError: (e) => {
        errorArchivo.value = e.imagen_archivo || ''
        toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 })
      },
    })
    return
  }

  // Marcas: FormData para permitir subir el logo como archivo.
  if (tipo.value === 'marcas') {
    if (errorLogo.value) return
    const fd = new FormData()
    fd.append('nombre', form.value.nombre || '')
    fd.append('activo', form.value.activo ? '1' : '0')
    if (form.value.codigo) fd.append('codigo', form.value.codigo)
    fd.append('id_pais', form.value.id_pais ?? '')
    if (archivoLogo.value) {
      fd.append('logo_archivo', archivoLogo.value)
    }
    if (continuarActivo) fd.append('_continuar', '1')
    if (editing.value) fd.append('_method', 'PUT')
    router.post(url, fd, {
      onSuccess: () => {
        toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 })
        showForm.value = false
        continuar.value = false
        limpiarLogo()
      },
      onError: (e) => {
        errorLogo.value = e.logo_archivo || ''
        toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 })
      },
    })
    return
  }

  const payload = { ...form.value, _continuar: continuarActivo }
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
  <AppLayout :title="catalogConfig?.title || 'Catálogo'">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button icon="pi pi-arrow-left" severity="secondary" text rounded class="mr-2" @click="router.visit(route('catalogo.gestionar'))" v-tooltip="'Volver a catálogos'" />
          <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
          <span v-if="items.total !== undefined" class="ml-3 text-xs text-gray-500 dark:text-gray-400">
            {{ items.total }} registros
            <span v-if="activosCount !== null" class="ml-1">· {{ activosCount }} activos</span>
          </span>
        </template>
        <template #end>
          <div class="flex items-center gap-2 flex-wrap">
            <template v-for="(cfg, key) in catalogFilters" :key="key">
              <Select v-model="filterModel[cfg.key]" :options="cfg.options" optionLabel="label" optionValue="value"
                      class="w-48" :showClear="true" :filter="true" filterPlaceholder="Buscar..." :placeholder="cfg.label" @change="aplicaFiltro" />
            </template>
            <InputText v-model="search" placeholder="Buscar..." />
          </div>
        </template>
      </Toolbar>

      <!-- Tipos de equipos: tarjetas con imagen grande -->
      <div v-if="esEquipos" class="grid gap-4" :class="'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'">
        <div v-for="item in items.data" :key="item.id"
             class="relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm hover:shadow-md transition-shadow group">
          <a :href="item.imagen || '#'" target="_blank" rel="noopener" class="block bg-gray-100 dark:bg-gray-800">
            <img v-if="item.imagen" :src="item.imagen" :alt="item.nombre"
                 class="w-full h-52 object-cover group-hover:scale-[1.03] transition-transform duration-200 cursor-zoom-in" />
            <div v-else class="w-full h-52 flex items-center justify-center">
              <i class="pi pi-image text-5xl text-gray-300 dark:text-gray-600" />
            </div>
          </a>
          <span v-if="!item.activo"
                class="absolute top-2 right-2 px-2 py-0.5 rounded-md text-xs font-semibold bg-red-500/90 text-white">Inactivo</span>

          <div class="p-3 text-center border-t border-gray-100 dark:border-gray-800">
            <p class="font-semibold text-gray-800 dark:text-gray-100 truncate" :title="item.nombre">{{ item.nombre }}</p>
          </div>

          <div class="absolute bottom-[52px] right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <Button icon="pi pi-pencil" rounded severity="info" size="small" @click="openEdit(item)" v-tooltip.top="'Editar'" />
            <Button icon="pi pi-trash" rounded severity="danger" size="small" @click="confirmarBorrado(item)" v-tooltip.top="'Eliminar'" />
          </div>
        </div>
        <div v-if="items.data?.length === 0" class="col-span-full p-8 text-center text-gray-400 dark:text-gray-500">
          <i class="pi pi-inbox text-3xl mb-2 block" /> Sin registros
        </div>
      </div>

      <Paginator v-if="esEquipos && items.total > items.per_page"
                 :rows="items.per_page" :totalRecords="items.total"
                 :first="(items.current_page - 1) * items.per_page"
                 @page="onPage"
                 paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                 currentPageReportTemplate="Total: {totalRecords} registros" />

      <DataTable v-if="!esEquipos" :value="items.data" striped-rows paginator :rows="20" :total-records="items.total"
                 :lazy="true" :first="(items.current_page - 1) * items.per_page" @page="onPage" paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport" currentPageReportTemplate="Total: {totalRecords} registros">
        <Column v-if="tieneImagenes" header="Imagen" :style="{ width: '90px' }">
          <template #body="{ data }">
            <a v-if="data.imagen" :href="data.imagen" target="_blank" rel="noopener">
              <img :src="data.imagen" :alt="data.nombre"
                   class="w-14 h-10 object-cover rounded border border-gray-200 dark:border-gray-700 cursor-zoom-in hover:opacity-80 transition-opacity" />
            </a>
            <i v-else class="pi pi-image text-gray-300" />
          </template>
        </Column>
        <Column v-if="catalogConfig?.codigoManual !== false" field="codigo" header="Código" sortable />
        <Column v-if="!catalogConfig?.hideNombre" field="nombre" header="Nombre" sortable />
        <template v-for="(cfg, key) in gridFields" :key="key">
          <Column v-if="key !== 'nombre' && key !== 'codigo' && key !== 'activo'" :field="key" :header="cfg.label">
            <template #body="{ data }">
              <img v-if="cfg.type === 'logo' && data[key]" :src="data[key]" :alt="data.nombre"
                   class="w-14 h-10 object-cover rounded border border-gray-200 dark:border-gray-700 cursor-zoom-in hover:opacity-80 transition-opacity" />
              <span v-else-if="cfg.type === 'select' && cfg.options">{{ getSelectLabel(cfg.options, data[key]) }}</span>
              <span v-else-if="cfg.type === 'boolean'">
                <i :class="data[key] ? 'pi pi-check text-green-600' : 'pi pi-times text-red-500'" />
              </span>
              <span v-else>{{ data[key] }}</span>
            </template>
          </Column>
        </template>
        <Column v-if="['modelos', 'marcas'].includes(catalogConfig?.tipo)" field="usos" header="Cantidad de usos" />
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
                @click="confirmarBorrado(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="editing ? `Editar ${catalogConfig?.title}` : `Nuevo ${catalogConfig?.title}`" modal :style="{ width: catalogConfig?.extra && Object.keys(catalogConfig.extra).length > 6 ? '800px' : '550px' }">
      <form @submit.prevent="submit(false)" class="space-y-4 overflow-y-auto max-h-[70vh]">
        <div class="grid grid-cols-2 gap-4">
          <div v-if="catalogConfig?.codigoManual !== false">
            <label class="block mb-1 font-medium">Código</label>
            <InputText v-model="form.codigo" class="w-full" required />
          </div>
          <div :class="(catalogConfig?.fields || {}).nombre?.type === 'textarea' ? 'col-span-2' : ''">
            <label class="block mb-1 font-medium">Nombre</label>
            <InputText v-if="(catalogConfig?.fields || {}).nombre?.type !== 'textarea'" v-model="form.nombre" class="w-full" required />
            <Textarea v-else v-model="form.nombre" class="w-full" :rows="(catalogConfig?.fields || {}).nombre?.rows || 3" required />
          </div>
          <!-- Imagen del equipo: subida con validación y previsualización -->
          <div v-if="esEquipos" class="col-span-2">
            <label class="block mb-1 font-medium">Imagen del equipo</label>
            <div class="flex items-start gap-4">
              <div class="w-32 h-24 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                <img v-if="previewImagen" :src="previewImagen" alt="Vista previa" class="w-full h-full object-cover" />
                <i v-else class="pi pi-image text-3xl text-gray-300 dark:text-gray-600" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors w-fit">
                  <i class="pi pi-upload" />
                  {{ previewImagen ? 'Cambiar imagen' : 'Seleccionar imagen' }}
                  <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="seleccionarArchivo" />
                </label>
                <small v-if="errorArchivo" class="text-red-500">{{ errorArchivo }}</small>
                <small v-else class="text-xs text-gray-400">JPG, PNG o WEBP · máx. 2 MB · 1024×1024</small>
                <Button v-if="previewImagen && archivoImagen" label="Quitar" icon="pi pi-times" text severity="danger" size="small" class="w-fit" @click="limpiarArchivo" />
              </div>
            </div>
          </div>

          <template v-for="(cfg, key) in (catalogConfig?.fields || {})" :key="key">
            <div v-if="key !== 'nombre' && key !== 'codigo' && key !== 'activo' && !cfg.noForm && !(esEquipos && key === 'imagen')" :class="cfg.type === 'textarea' ? 'col-span-2' : ''">
              <label class="block mb-1 font-medium">{{ cfg.label }}</label>
              <InputNumber v-if="cfg.type === 'number'" v-model="form[key]" class="w-full" />
              <Textarea v-else-if="cfg.type === 'textarea'" v-model="form[key]" class="w-full" :rows="3" />
              <Select v-else-if="cfg.type === 'select' && cfg.options" v-model="form[key]" :options="cfg.options" optionLabel="label" optionValue="value" placeholder="Seleccionar..." class="w-full" :showClear="true" />
              <div v-else-if="cfg.type === 'boolean'" class="flex items-center gap-2 pt-2">
                <ToggleSwitch v-model="form[key]" :inputId="'fld-' + key" />
                <label :for="'fld-' + key" class="text-sm">{{ cfg.label }}</label>
              </div>
              <template v-else>
                <div v-if="cfg.type === 'logo'" class="flex items-start gap-4 col-span-2">
                  <div class="w-32 h-24 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                    <img v-if="previewLogo" :src="previewLogo" alt="Vista previa" class="w-full h-full object-contain" />
                    <img v-else-if="form[key]" :src="form[key]" :alt="form.nombre" class="w-full h-full object-contain" />
                    <i v-else class="pi pi-image text-3xl text-gray-300 dark:text-gray-600" />
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors w-fit">
                      <i class="pi pi-upload" />
                      {{ previewLogo ? 'Cambiar logo' : 'Seleccionar logo' }}
                      <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="seleccionarLogo" />
                    </label>
                    <small v-if="errorLogo" class="text-red-500">{{ errorLogo }}</small>
                    <small v-else class="text-xs text-gray-400">JPG, PNG o WEBP · máx. 2 MB</small>
                    <Button v-if="previewLogo && archivoLogo" label="Quitar" icon="pi pi-times" text severity="danger" size="small" class="w-fit" @click="limpiarLogo" />
                  </div>
                </div>
                <InputText v-else v-model="form[key]" class="w-full" :type="cfg.type === 'email' ? 'email' : 'text'" />
              </template>
            </div>
          </template>
          <template v-for="(cfg, key) in (catalogConfig?.extra || {})" :key="'x-' + key">
            <div v-if="!(catalogConfig?.fields || {})[key] && key !== 'activo' && !cfg.noForm && !(esEquipos && (key === 'imagen' || key === 'imagen_fuente'))" :class="cfg.type === 'textarea' ? 'col-span-2' : ''">
              <label class="block mb-1 font-medium">{{ cfg.label }}</label>
              <InputNumber v-if="cfg.type === 'number'" v-model="form[key]" class="w-full" />
              <Select v-else-if="cfg.type === 'select' && cfg.options" v-model="form[key]" :options="cfg.options" optionLabel="label" optionValue="value" placeholder="Seleccionar..." class="w-full" :showClear="true" />
              <div v-else-if="cfg.type === 'boolean'" class="flex items-center gap-2 pt-2">
                <ToggleSwitch v-model="form[key]" :inputId="'x-fld-' + key" />
                <label :for="'x-fld-' + key" class="text-sm">{{ cfg.label }}</label>
              </div>
              <template v-else>
                <div v-if="cfg.type === 'logo'" class="flex items-start gap-4 col-span-2">
                  <div class="w-32 h-24 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                    <img v-if="previewLogo" :src="previewLogo" alt="Vista previa" class="w-full h-full object-contain" />
                    <img v-else-if="form[key]" :src="form[key]" :alt="form.nombre" class="w-full h-full object-contain" />
                    <i v-else class="pi pi-image text-3xl text-gray-300 dark:text-gray-600" />
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors w-fit">
                      <i class="pi pi-upload" />
                      {{ previewLogo ? 'Cambiar logo' : 'Seleccionar logo' }}
                      <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="seleccionarLogo" />
                    </label>
                    <small v-if="errorLogo" class="text-red-500">{{ errorLogo }}</small>
                    <small v-else class="text-xs text-gray-400">JPG, PNG o WEBP · máx. 2 MB</small>
                    <Button v-if="previewLogo && archivoLogo" label="Quitar" icon="pi pi-times" text severity="danger" size="small" class="w-fit" @click="limpiarLogo" />
                  </div>
                </div>
                <InputText v-else v-model="form[key]" class="w-full" :type="cfg.type === 'email' ? 'email' : 'text'" />
              </template>
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
