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
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import { useToast } from 'primevue/usetoast'
import { formatDate } from '@/Utils/date'

const props = defineProps({
  title: String,
  aforos: Object,
  tipos: Array,
  filters: Object,
  fechaOperaciones: String,
})

const toast = useToast()
const search = ref(props.filters?.search || '')
const showForm = ref(false)
const editing = ref(null)

function filaVacia() {
  return { tn_pos: 0, tn_real: 0, km_carga: 0, km_vacio: 0, km_total: 0, traf_pos: 0, traf_real: 0 }
}

const form = ref({ viajes: 1, tipo_indicadores: 1, filas: [] })

watch(search, () => reload())

function reload() {
  router.get(route('indicadores.index'), { search: search.value }, { preserveState: true, replace: true })
}

function onPage(event) {
  router.get(route('indicadores.index'), {
    page: event.page + 1,
    search: search.value,
  }, { preserveState: true, replace: true })
}

function tipoLabel(id) {
  return props.tipos?.find((t) => t.id === Number(id))?.label || '—'
}

function openEdit(item) {
  editing.value = item
  const porPos = new Map()
  for (const f of item.indicadores_filas || []) porPos.set(Number(f.posicion), f)
  const filas = []
  for (let i = 1; i <= 7; i++) {
    const f = porPos.get(i)
    filas.push(f ? {
      tn_pos: Number(f.tn_pos) || 0,
      tn_real: Number(f.tn_real) || 0,
      km_carga: Number(f.km_carga) || 0,
      km_vacio: Number(f.km_vacio) || 0,
      km_total: Number(f.km_total) || 0,
      traf_pos: Number(f.traf_pos) || 0,
      traf_real: Number(f.traf_real) || 0,
    } : filaVacia())
  }
  form.value = {
    viajes: Number(item.viajes) || 1,
    tipo_indicadores: Number(item.tipo_indicadores) || 1,
    filas,
  }
  showForm.value = true
}

const n = (v) => Number(v) || 0
const r2 = (v) => Math.round((Number(v) + Number.EPSILON) * 100) / 100

// Totales en vivo (misma lógica que AforoCotizadorService::calcularIndicadores).
const totales = computed(() => {
  const filas = form.value.filas || []
  const tipo = Number(form.value.tipo_indicadores) || 1
  const viajes = Number(form.value.viajes) || 1

  if (tipo === 4) {
    const f = filas[0] || filaVacia()
    return {
      tn_pos: n(f.tn_pos), tn_real: n(f.tn_real),
      km_carga: n(f.km_carga), km_vacio: n(f.km_vacio),
      km_total: r2(n(f.km_carga) + n(f.km_vacio)),
      traf_pos: n(f.traf_pos), traf_real: n(f.traf_real),
    }
  }

  const suma = (campo) => filas.reduce((s, f) => s + n(f[campo]), 0)
  const km_carga = tipo === 3 ? Math.max(0, ...filas.map((f) => n(f.km_carga))) : suma('km_carga')
  const km_vacio = suma('km_vacio')

  let traf_pos = 0
  let traf_real = 0
  for (const f of filas) {
    if (viajes > 0 && tipo === 2) {
      traf_pos += (n(f.tn_pos) / viajes) * n(f.km_carga)
      traf_real += (n(f.tn_real) / viajes) * n(f.km_carga)
    } else {
      traf_pos += n(f.tn_pos) * n(f.km_carga)
      traf_real += n(f.tn_real) * n(f.km_carga)
    }
  }

  return {
    tn_pos: r2(suma('tn_pos')),
    tn_real: r2(suma('tn_real')),
    km_carga: r2(km_carga),
    km_vacio: r2(km_vacio),
    km_total: r2(km_carga + km_vacio),
    traf_pos: r2(traf_pos),
    traf_real: r2(traf_real),
  }
})

function submit() {
  const payload = {
    viajes: Number(form.value.viajes) || 1,
    tipo_indicadores: Number(form.value.tipo_indicadores) || 1,
    indicadores_filas: form.value.filas,
  }
  router.put(route('indicadores.update', editing.value.id), payload, {
    onSuccess: () => {
      showForm.value = false
      toast.add({ severity: 'success', summary: 'Indicadores guardados', life: 3000 })
    },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

const campos = [
  { key: 'tn_pos', label: 'TN Pos' },
  { key: 'tn_real', label: 'TN Real' },
  { key: 'km_carga', label: 'KM Carga' },
  { key: 'km_vacio', label: 'KM Vacío' },
  { key: 'km_total', label: 'KM Total' },
  { key: 'traf_pos', label: 'Tráfico Pos' },
  { key: 'traf_real', label: 'Tráfico Real' },
]
</script>

<template>
  <AppLayout :title="title">
    <div class="card">
      <Toolbar class="mb-4">
        <template #start>
          <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">Indicadores de Explotación</h2>
          <span class="text-sm text-gray-500 dark:text-gray-400 ml-3">{{ aforos.total }} aforos</span>
        </template>
        <template #end>
          <InputText v-model="search" placeholder="Buscar CP o cliente..." />
        </template>
      </Toolbar>

      <DataTable
        :value="aforos.data"
        striped-rows
        size="small"
        paginator
        :rows="aforos.per_page"
        :total-records="aforos.total"
        :lazy="true"
        :first="(aforos.current_page - 1) * aforos.per_page"
        @page="onPage"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        currentPageReportTemplate="Total: {totalRecords} registros"
        responsiveLayout="scroll"
      >
        <template #empty>No hay aforos para el período.</template>
        <Column header="Fecha">
          <template #body="{ data }">{{ formatDate(data.fecha_parte) }}</template>
        </Column>
        <Column header="CP">
          <template #body="{ data }" class="font-bold">{{ data.carta_porte?.numero }}</template>
        </Column>
        <Column header="Cliente">
          <template #body="{ data }">{{ data.carta_porte?.cliente?.nombre || '—' }}</template>
        </Column>
        <Column header="Equipo">
          <template #body="{ data }">{{ data.carta_porte?.tractivo?.codigo || '—' }}</template>
        </Column>
        <Column header="Viajes">
          <template #body="{ data }">{{ data.viajes }}</template>
        </Column>
        <Column header="Tipo">
          <template #body="{ data }">
            <Tag :value="tipoLabel(data.tipo_indicadores)" severity="info" class="text-[10px]" />
          </template>
        </Column>
        <Column header="TN Pos">
          <template #body="{ data }">{{ Number(data.tn_pos_total).toLocaleString() }}</template>
        </Column>
        <Column header="TN Real">
          <template #body="{ data }">{{ Number(data.tn_real_total).toLocaleString() }}</template>
        </Column>
        <Column header="KM Carga">
          <template #body="{ data }">{{ Number(data.km_carga_total).toLocaleString() }}</template>
        </Column>
        <Column header="KM Vacío">
          <template #body="{ data }">{{ Number(data.km_vacio_total).toLocaleString() }}</template>
        </Column>
        <Column header="KM Total">
          <template #body="{ data }">{{ Number(data.km_total_total).toLocaleString() }}</template>
        </Column>
        <Column header="Tráf. Pos">
          <template #body="{ data }">{{ Number(data.traf_pos_total).toLocaleString() }}</template>
        </Column>
        <Column header="Tráf. Real">
          <template #body="{ data }">{{ Number(data.traf_real_total).toLocaleString() }}</template>
        </Column>
        <Column header="Acciones" style="width: 90px">
          <template #body="{ data }">
            <Button icon="pi pi-pencil" rounded text severity="warn" v-tooltip="'Editar indicadores'" @click="openEdit(data)" />
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="showForm" :header="`Indicadores — CP ${editing?.carta_porte?.numero || ''}`" modal style="width: 960px" :maximizable="true">
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block mb-1 font-medium">Viajes</label>
          <InputNumber v-model="form.viajes" :min="1" :max-fraction-digits="0" class="w-full" />
        </div>
        <div>
          <label class="block mb-1 font-medium">Tipo de indicadores</label>
          <Select v-model="form.tipo_indicadores" :options="tipos" optionLabel="label" optionValue="id" class="w-full" />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm border-collapse">
          <thead>
            <tr class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
              <th class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-center">#</th>
              <th v-for="c in campos" :key="c.key" class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-center whitespace-nowrap">{{ c.label }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(fila, i) in form.filas" :key="i">
              <td class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-center font-bold">{{ i + 1 }}</td>
              <td v-for="c in campos" :key="c.key" class="border border-gray-200 dark:border-gray-600 px-1 py-0.5">
                <InputNumber v-model="fila[c.key]" :min="0" :max-fraction-digits="2" :show-buttons="false" inputClass="w-24 text-right" />
              </td>
            </tr>
            <tr class="bg-blue-50 dark:bg-blue-950/30 font-bold">
              <td class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-center">Σ</td>
              <td class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-right">{{ totales.tn_pos.toLocaleString() }}</td>
              <td class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-right">{{ totales.tn_real.toLocaleString() }}</td>
              <td class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-right">{{ totales.km_carga.toLocaleString() }}</td>
              <td class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-right">{{ totales.km_vacio.toLocaleString() }}</td>
              <td class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-right">{{ totales.km_total.toLocaleString() }}</td>
              <td class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-right">{{ totales.traf_pos.toLocaleString() }}</td>
              <td class="border border-gray-200 dark:border-gray-600 px-2 py-1 text-right">{{ totales.traf_real.toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex gap-2 justify-end mt-4">
        <Button label="Cancelar" severity="secondary" @click="showForm = false" />
        <Button label="Guardar" icon="pi pi-save" @click="submit" />
      </div>
    </Dialog>
  </AppLayout>
</template>
