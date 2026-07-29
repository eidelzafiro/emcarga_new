<template>
  <AppLayout title="Gestión de tipos de catálogo">
    <Card>
      <template #title>Gestión de tipos de catálogo</template>
      <template #content>
        <p class="text-sm text-surface-500 mb-4">
          Organiza los tipos, asígnalos a una agrupación y decide cuáles mantener activos.
        </p>

        <DataTable
          :value="tipos"
          :globalFilterFields="['tipo', 'titulo', 'agrupacion']"
          stripedRows
          showGridlines
          scrollable
          scrollHeight="flex"
          size="small"
          :pt="{ thead: { class: 'sticky top-0' } }"
        >
          <template #header>
            <div class="flex flex-wrap items-center gap-2">
              <IconField>
                <InputIcon><i class="pi pi-search" /></InputIcon>
                <InputText v-model="filtro" placeholder="Buscar…" class="w-48" />
              </IconField>
              <span class="text-sm text-surface-400 ml-auto">{{ tipos.length }} tipos</span>
            </div>
          </template>

          <Column field="tipo" header="Tipo" sortable style="min-width:12rem" />
          <Column field="titulo" header="Título" sortable style="min-width:14rem" />

          <Column field="agrupacion" header="Agrupación" sortable style="min-width:10rem">
            <template #body="{ data }">
              <Select
                v-model="data.agrupacion"
                :options="opcionesAgrupacion"
                :allowEmpty="false"
                class="w-40"
                size="small"
                @change="guardar(data)"
              />
            </template>
          </Column>

          <Column header="Registros" style="min-width:6rem" class="text-center">
            <template #body="{ data }">
              <Badge :value="data.items_count" :severity="data.items_count > 0 ? 'info' : 'contrast'" />
            </template>
          </Column>

          <Column field="activo" header="Activo" style="min-width:6rem" class="text-center">
            <template #body="{ data }">
              <ToggleSwitch v-model="data.activo" @update:modelValue="guardar(data)" />
            </template>
          </Column>

          <Column header="" style="min-width:5rem" class="text-center">
            <template #body="{ data }">
              <Button
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                v-tooltip.left="'Editar registros'"
                @click="irAEditar(data.tipo)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'

const props = defineProps({
  tipos: Array,
})

const filtro = ref('')

const opcionesAgrupacion = [
  'Técnica',
  'Comercial',
  'RRHH',
  'Contabilidad',
]

function guardar(data) {
  router.put(route('catalogo.update-tipo', data.tipo), {
    agrupacion: data.agrupacion,
    activo: data.activo,
  }, {
    preserveScroll: true,
  })
}

function irAEditar(tipo) {
  router.visit(route('catalogo.index', { tipo }))
}
</script>
