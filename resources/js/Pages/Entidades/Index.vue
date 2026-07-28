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
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Checkbox from 'primevue/checkbox'
import DatePicker from 'primevue/datepicker'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ items: Object, filters: Object, provincias: Array, municipios: Array, sistemas: Array, entidadesPadre: Array })
const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)
const tabActivo = ref(0)
const dt = ref()

const form = ref(emptyForm())

function emptyForm() {
  return {
    codigo: '',
    nombre: '',
    parent_id: null,
    es_matriz: false,
    licencia_vencimiento: null,
    licencia_activa: true,
    nit: '',
    cta_unica: '',
    agencia: '',
    cliente_fincimex_mn: '',
    abreviatura: '',
    cta_mn: '',
    direccion: '',
    notas_fact: '',
    email: '',
    talon_versat: '',
    mora_dias: null,
    mora_porciento: null,
    id_cajera: null,
    id_parqueo: null,
    pass_dias: 120,
    pass_cant_h: 2,
    almacenaje: null,
    minutos: false,
    interruptos: false,
    lugares: false,
    oper_carga: false,
    disponible: false,
    tipo_planificacion: false,
    tasas_aforo: false,
    requisitos: false,
    matriz: false,
    id_provincia: null,
    id_municipio: null,
    id_sistema: null,
    activo: true,
  }
}

watch(search, () => {
  router.get(route('entidades.index'), { search: search.value }, { preserveState: true, replace: true })
})

function openCreate() {
  editing.value = null
  form.value = emptyForm()
  showForm.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    codigo: item.codigo ?? '',
    nombre: item.nombre ?? '',
    parent_id: item.parent_id ?? null,
    es_matriz: Boolean(item.es_matriz),
    licencia_vencimiento: item.licencia_vencimiento ?? null,
    licencia_activa: item.licencia_activa === false ? false : true,
    nit: item.nit ?? '',
    cta_unica: item.cta_unica ?? '',
    agencia: item.agencia ?? '',
    cliente_fincimex_mn: item.cliente_fincimex_mn ?? '',
    abreviatura: item.abreviatura ?? '',
    cta_mn: item.cta_mn ?? '',
    direccion: item.direccion ?? '',
    notas_fact: item.notas_fact ?? '',
    email: item.email ?? '',
    talon_versat: item.talon_versat ?? '',
    mora_dias: item.mora_dias ?? null,
    mora_porciento: item.mora_porciento ?? null,
    id_cajera: item.id_cajera ?? null,
    id_parqueo: item.id_parqueo ?? null,
    pass_dias: item.pass_dias ?? 120,
    pass_cant_h: item.pass_cant_h ?? 2,
    almacenaje: item.almacenaje ?? null,
    minutos: Boolean(item.minutos),
    interruptos: Boolean(item.interruptos),
    lugares: Boolean(item.lugares),
    oper_carga: Boolean(item.oper_carga),
    disponible: Boolean(item.disponible),
    tipo_planificacion: Boolean(item.tipo_planificacion),
    tasas_aforo: Boolean(item.tasas_aforo),
    requisitos: Boolean(item.requisitos),
    matriz: Boolean(item.matriz),
    id_provincia: item.id_provincia ?? null,
    id_municipio: item.id_municipio ?? null,
    id_sistema: item.id_sistema ?? null,
    activo: item.activo === false ? false : true,
  }
  showForm.value = true
}

function submit() {
  const url = editing.value ? route('entidades.update', editing.value.id) : route('entidades.store')
  const method = editing.value ? 'put' : 'post'
  const payload = {
    ...form.value,
    minutos: form.value.minutos ? 1 : 0,
    interruptos: form.value.interruptos ? 1 : 0,
    lugares: form.value.lugares ? 1 : 0,
    oper_carga: form.value.oper_carga ? 1 : 0,
    disponible: form.value.disponible ? 1 : 0,
    tipo_planificacion: form.value.tipo_planificacion ? 1 : 0,
    tasas_aforo: form.value.tasas_aforo ? 1 : 0,
    requisitos: form.value.requisitos ? 1 : 0,
    matriz: form.value.matriz ? 1 : 0,
  }
  router[method](url, payload, {
    onSuccess: () => { showForm.value = false; toast.add({ severity: 'success', summary: editing.value ? 'Actualizado' : 'Creado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

function destroyItem(id) {
  if (confirm('¿Eliminar esta entidad?')) {
    router.delete(route('entidades.destroy', id), {
      onSuccess: () => toast.add({ severity: 'success', summary: 'Eliminado', life: 3000 }),
    })
  }
}

function exportExcel() {
  dt.value.exportCSV()
}

function printGrid() {
  window.print()
}
</script>

<template>
  <AppLayout title="Entidades">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <Button label="Nuevo" icon="pi pi-plus" severity="success" @click="openCreate" />
        </template>
        <template #end>
          <div class="flex gap-2">
            <Button icon="pi pi-print" severity="info" outlined @click="printGrid" v-tooltip.left="'Imprimir'" />
            <Button icon="pi pi-file-excel" severity="success" outlined @click="exportExcel" v-tooltip.left="'Exportar a Excel'" />
            <InputText v-model="search" placeholder="Buscar..." />
          </div>
        </template>
      </Toolbar>

      <DataTable ref="dt" :value="items.data" striped-rows paginator :rows="20" :total-records="items.total">
        <Column field="codigo" header="Código" sortable>
          <template #body="{ data }">
            <span :class="data.codigo ? '' : 'text-surface-400 italic'">{{ data.codigo ?? 'Sin código' }}</span>
          </template>
        </Column>
        <Column field="nombre" header="Nombre" sortable />
        <Column field="abreviatura" header="Siglas" />
        <Column field="nit" header="NIT" />
        <Column header="Activo" style="width: 100px">
          <template #body="{ data }">
            <i v-if="data.activo !== undefined" :class="data.activo ? 'pi pi-check text-green-600' : 'pi pi-times text-red-500'" />
          </template>
        </Column>
        <Column header="Acciones" :exportable="false" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" rounded text severity="info" @click="openEdit(data)" />
              <Button icon="pi pi-trash" rounded text severity="danger" @click="destroyItem(data.id)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" header="DATOS DE LA ENTIDAD" modal :style="{ width: '750px' }" :closable="true">
      <Tabs v-model:value="tabActivo" :default-value="0">
        <TabList>
          <Tab :value="0">DATOS</Tab>
          <Tab :value="1">CONFIGURACIONES</Tab>
        </TabList>
        <TabPanels>
          <TabPanel :value="0">
          <div class="form-grid">
            <div class="form-row">
              <label class="form-label">NOMBRE</label>
              <InputText v-model="form.nombre" class="w-full" required />
            </div>
            <div class="form-row form-row-split">
              <div class="form-field">
                <label class="form-label">CODIGO</label>
                <InputText v-model="form.codigo" class="w-full" />
              </div>
              <div class="form-field">
                <label class="form-label">ENTIDAD PADRE</label>
                <Select v-model="form.parent_id" :options="entidadesPadre.filter(e => !editing || e.id !== editing.id)" optionLabel="abreviatura" optionValue="id" placeholder="Ninguna (raíz)" class="w-full" :showClear="true" />
              </div>
            </div>
            <div class="form-row">
              <div class="flex items-center gap-2" style="padding-left: 130px;">
                <Checkbox v-model="form.es_matriz" :binary="true" inputId="chk_es_matriz" />
                <label for="chk_es_matriz" class="font-medium text-sm">ES MATRIZ (ve todas las entidades)</label>
              </div>
            </div>
            <div class="form-row form-row-split">
              <div class="form-field">
                <label class="form-label">NIT</label>
                <InputText v-model="form.nit" class="w-full" />
              </div>
              <div class="form-field">
                <label class="form-label">CTA UNICA</label>
                <InputText v-model="form.cta_unica" class="w-full" />
              </div>
              <div class="form-field">
                <label class="form-label">AGENCIA</label>
                <InputText v-model="form.agencia" class="w-full" />
              </div>
            </div>
            <div class="form-row form-row-split">
              <div class="form-field">
                <label class="form-label">FINCIMEX MN</label>
                <InputText v-model="form.cliente_fincimex_mn" class="w-full" />
              </div>
              <div class="form-field">
                <label class="form-label">SIGLAS</label>
                <InputText v-model="form.abreviatura" class="w-full" />
              </div>
            </div>
            <div class="form-row form-row-split">
              <div class="form-field">
                <label class="form-label">CTA MN</label>
                <InputText v-model="form.cta_mn" class="w-full" />
              </div>
              <div class="form-field">
                <label class="form-label">PROVINCIA</label>
                <Select v-model="form.id_provincia" :options="provincias" optionLabel="nombre" optionValue="id" placeholder="Seleccionar..." class="w-full" :showClear="true" />
              </div>
            </div>
            <div class="form-row form-row-split">
              <div class="form-field">
                <label class="form-label">MUNICIPIO</label>
                <Select v-model="form.id_municipio" :options="municipios" optionLabel="nombre" optionValue="id" placeholder="Seleccionar..." class="w-full" :showClear="true" />
              </div>
              <div class="form-field">
                <label class="form-label">SISTEMA</label>
                <Select v-model="form.id_sistema" :options="sistemas" optionLabel="nombre" optionValue="id" placeholder="Seleccionar..." class="w-full" :showClear="true" />
              </div>
            </div>
            <div class="form-row">
              <label class="form-label">DIRECCION</label>
              <InputText v-model="form.direccion" class="w-full" />
            </div>
            <div class="form-row">
              <label class="form-label">NOTAS EN LA FACTURA</label>
              <Textarea v-model="form.notas_fact" class="w-full" :rows="3" />
            </div>
            <div class="form-row form-row-triple">
              <div class="form-field">
                <label class="form-label">TALON VERSAT</label>
                <InputText v-model="form.talon_versat" />
              </div>
              <div class="form-field">
                <label class="form-label">MORA DIAS</label>
                <InputNumber v-model="form.mora_dias" :min="0" />
              </div>
              <div class="form-field">
                <label class="form-label">MORA %</label>
                <InputNumber v-model="form.mora_porciento" :min="0" :max="100" />
              </div>
            </div>
            <div class="form-row">
              <label class="form-label">EMAIL FACTURACION</label>
              <InputText v-model="form.email" class="w-full" type="email" />
            </div>
            <div class="form-row form-row-split">
              <div class="form-field">
                <label class="form-label">CAJERA</label>
                <Select v-model="form.id_cajera" :options="[]" placeholder="Seleccionar..." class="w-full" :showClear="true" />
              </div>
              <div class="form-field">
                <label class="form-label">PARQUEO</label>
                <Select v-model="form.id_parqueo" :options="[]" placeholder="Seleccionar..." class="w-full" :showClear="true" />
              </div>
            </div>
          </div>
        </TabPanel>

          <TabPanel :value="1">
          <div class="config-grid">
            <div class="config-row config-row-nums">
              <div class="config-num-field">
                <label class="config-label">(PASSWORD) CANTIDAD DE DIAS PARA CAMBIAR</label>
                <InputNumber v-model="form.pass_dias" class="config-input" :min="0" />
              </div>
              <div class="config-num-field">
                <label class="config-label">(PASSWORD) CANTIDAD PARA GUARDAR EN HISTORICO</label>
                <InputNumber v-model="form.pass_cant_h" class="config-input" :min="0" />
              </div>
              <div class="config-num-field">
                <label class="config-label">TASA DE PAGO DEL ALMACENAJE (EMCARGA)</label>
                <InputNumber v-model="form.almacenaje" class="config-input" :min="0" :max="999.9999" />
              </div>
            </div>

            <div class="config-row-license">
              <div class="config-license-field">
                <label class="config-label">LICENCIA VENCE</label>
                <DatePicker v-model="form.licencia_vencimiento" dateFormat="dd/mm/yy" class="w-full" :showClear="true" />
              </div>
              <div class="config-license-field">
                <label class="config-label">LICENCIA ACTIVA</label>
                <Checkbox v-model="form.licencia_activa" :binary="true" />
              </div>
            </div>

            <div class="config-checks-grid">
              <div class="config-check-item">
                <Checkbox v-model="form.minutos" :binary="true" inputId="chk_minutos" />
                <label for="chk_minutos" class="config-label-check">NRO NOMINA VERSAT?</label>
              </div>
              <div class="config-check-item">
                <Checkbox v-model="form.interruptos" :binary="true" inputId="chk_interruptos" />
                <label for="chk_interruptos" class="config-label-check">INTERRUPCION DE CHOFERES POR INCIDENCIAS?</label>
              </div>
              <div class="config-check-item">
                <Checkbox v-model="form.lugares" :binary="true" inputId="chk_lugares" />
                <label for="chk_lugares" class="config-label-check">LOS OPERATIVOS CREAN LUGARES?</label>
              </div>
              <div class="config-check-item">
                <Checkbox v-model="form.oper_carga" :binary="true" inputId="chk_oper_carga" />
                <label for="chk_oper_carga" class="config-label-check">LOS OPERATIVOS NO PLANIFICAN CARGA GENERAL?</label>
              </div>
              <div class="config-check-item">
                <Checkbox v-model="form.disponible" :binary="true" inputId="chk_disponible" />
                <label for="chk_disponible" class="config-label-check">LOS OPERATIVOS PASAN EL CHIP DE COMBUSTIBLE?</label>
              </div>
              <div class="config-check-item">
                <Checkbox v-model="form.tipo_planificacion" :binary="true" inputId="chk_tipo_planif" />
                <label for="chk_tipo_planif" class="config-label-check">TIEMPOS DE LA HOJA DE RUTA EN MINUTOS?</label>
              </div>
              <div class="config-check-item">
                <Checkbox v-model="form.tasas_aforo" :binary="true" inputId="chk_tasas_aforo" />
                <label for="chk_tasas_aforo" class="config-label-check">BLOQUEAR TASAS DE SALARIO EN EL AFORO?</label>
              </div>
              <div class="config-check-item">
                <Checkbox v-model="form.requisitos" :binary="true" inputId="chk_requisitos" />
                <label for="chk_requisitos" class="config-label-check">LIMITAR EMISION DOCUMENTOS PRIMARIOS SIN REQUISITOS?</label>
              </div>
              <div class="config-check-item">
                <Checkbox v-model="form.matriz" :binary="true" inputId="chk_matriz" />
                <label for="chk_matriz" class="config-label-check">MATRIZ</label>
              </div>
            </div>
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>

      <div class="flex gap-2 justify-end mt-4">
        <Button label="CANCELAR" severity="secondary" @click="showForm = false" />
        <Button label="GUARDAR" icon="pi pi-save" @click="submit" />
      </div>
    </Dialog>
  </AppLayout>
</template>

<style scoped>
.form-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.form-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.form-row-split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-label {
  font-weight: 700;
  font-size: 0.85rem;
  white-space: nowrap;
  min-width: 130px;
  text-align: left;
}

.form-row .form-label {
  min-width: 130px;
  flex-shrink: 0;
}

.form-row :deep(.p-inputtext),
.form-row :deep(.p-inputnumber),
.form-row :deep(.p-inputwrapper) {
  width: 100%;
}

.form-row-triple {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
}

.config-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.config-row-nums {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 16px;
}

.config-num-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.config-input {
  width: 100%;
}

.config-checks-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px 24px;
}

.config-check-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.config-label {
  font-weight: 700;
  font-size: 0.8rem;
}

.config-label-check {
  font-weight: 600;
  font-size: 0.82rem;
  cursor: pointer;
}

.config-row-license {
  display: flex;
  align-items: center;
  gap: 24px;
  border-top: 1px solid #e5e7eb;
  padding-top: 12px;
}

.config-license-field {
  display: flex;
  align-items: center;
  gap: 8px;
}

.config-license-field .config-label {
  min-width: 120px;
}

@media print {
  .p-toolbar,
  .p-paginator,
  .p-dialog,
  .p-column-header-content .p-sortable-column-icon {
    display: none !important;
  }
  .card {
    box-shadow: none !important;
    padding: 0 !important;
  }
  .p-datatable-table {
    font-size: 10px;
  }
}
</style>
