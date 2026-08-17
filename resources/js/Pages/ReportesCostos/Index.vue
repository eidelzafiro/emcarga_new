<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Toolbar from 'primevue/toolbar'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import { useToast } from 'primevue/usetoast'

const props = defineProps({ reportes: Object, tractivos: Array, filters: Object })
const toast = useToast()
const showRecalc = ref(false)
const recalcForm = ref({ id_tractivo: null, fecha: null })
const showRecalcAll = ref(false)
const recalcAllForm = ref({ fecha: null })

const recalcular = () => {
  router.post(route('reportes-costos.recalcular'), recalcForm.value, {
    onSuccess: () => { showRecalc.value = false; toast.add({ severity: 'success', summary: 'Recalculado', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

const recalcularTodos = () => {
  router.post(route('reportes-costos.recalcular-todos'), recalcAllForm.value, {
    onSuccess: () => { showRecalcAll.value = false; toast.add({ severity: 'success', summary: 'Recalculados todos', life: 3000 }) },
    onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
  })
}

const fmt = (n) => n?.toLocaleString('es-CU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>

<template>
  <AppLayout>
    <Toolbar class="mb-3">
      <template #start>
        <h2 class="text-xl font-bold m-0">Reportes Costos</h2>
      </template>
      <template #end>
        <div class="flex gap-2">
          <Button label="Recalcular tractivo" icon="pi pi-calculator" severity="info" @click="showRecalc = true" />
          <Button label="Recalcular todos" icon="pi pi-refresh" severity="success" @click="showRecalcAll = true" />
        </div>
      </template>
    </Toolbar>

    <DataTable :value="reportes.data" paginator :rows="reportes.per_page" :totalRecords="reportes.total"
      :rowsPerPageOptions="[10, 20, 50]" :first="(reportes.current_page - 1) * reportes.per_page"
      stripedRows class="p-datatable-sm"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
      currentPageReportTemplate="Total: {totalRecords} registros">
      <Column field="fecha_reporte" header="Fecha" sortable style="width:120px" />
      <Column field="tractivo.codigo" header="Tractivo" />
      <Column field="combustible_mn" header="Combustible">
        <template #body="{ data }">{{ fmt(data.combustible_mn) }}</template>
      </Column>
      <Column field="dietas" header="Dietas">
        <template #body="{ data }">{{ fmt(data.dietas) }}</template>
      </Column>
      <Column field="amortizacion_mn" header="Amortización">
        <template #body="{ data }">{{ fmt(data.amortizacion_mn) }}</template>
      </Column>
      <Column field="chapa" header="Chapa">
        <template #body="{ data }">{{ fmt(data.chapa) }}</template>
      </Column>
      <Column field="gastos_mn" header="Gastos MN">
        <template #body="{ data }">{{ fmt(data.gastos_mn) }}</template>
      </Column>
      <Column field="ingresos_mn" header="Ingresos MN">
        <template #body="{ data }">{{ fmt(data.ingresos_mn) }}</template>
      </Column>
      <Column field="utilidad_mn" header="Utilidad MN">
        <template #body="{ data }">
          <span :class="data.utilidad_mn < 0 ? 'text-red-500 font-semibold' : 'text-green-600 font-semibold'">{{ fmt(data.utilidad_mn) }}</span>
        </template>
      </Column>
      <Column field="kms_total" header="Kms">
        <template #body="{ data }">{{ fmt(data.kms_total) }}</template>
      </Column>
      <Column field="toneladas" header="Tons">
        <template #body="{ data }">{{ fmt(data.toneladas) }}</template>
      </Column>
      <Column field="costo_tn_kms" header="Costo tn/km">
        <template #body="{ data }">{{ fmt(data.costo_tn_kms) }}</template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="showRecalc" header="Recalcular costos de un tractivo" modal style="width: 420px">
      <div class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Tractivo</label>
          <Select v-model="recalcForm.id_tractivo" :options="tractivos" optionLabel="codigo" optionValue="id" placeholder="Seleccione..." class="w-full" required />
        </div>
        <div>
          <label class="block mb-1 font-medium">Mes (fecha)</label>
          <DatePicker v-model="recalcForm.fecha" dateFormat="mm/yy" view="month" class="w-full" />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showRecalc = false" />
          <Button label="Recalcular" icon="pi pi-calculator" @click="recalcular" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="showRecalcAll" header="Recalcular costos de todos los tractivos" modal style="width: 420px">
      <div class="space-y-4">
        <div>
          <label class="block mb-1 font-medium">Mes (fecha)</label>
          <DatePicker v-model="recalcAllForm.fecha" dateFormat="mm/yy" view="month" class="w-full" />
        </div>
        <div class="flex gap-2 justify-end">
          <Button label="Cancelar" severity="secondary" @click="showRecalcAll = false" />
          <Button label="Recalcular todos" icon="pi pi-refresh" @click="recalcularTodos" />
        </div>
      </div>
    </Dialog>
  </AppLayout>
</template>