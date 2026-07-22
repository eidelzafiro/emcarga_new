<template>
  <AppLayout>
    <Card>
      <template #title>Órdenes de Taller</template>
      <template #content>
        <DataTable :value="ordenes.data" paginator :rows="20" :totalRecords="ordenes.total" stripedRows class="p-datatable-sm">
          <Column field="numero" header="N° Orden" sortable />
          <Column field="tractivo.descripcion" header="Vehículo" />
          <Column field="tipoMantenimiento.nombre" header="Tipo Mtto." />
          <Column field="fecha_ingreso" header="Ingreso" />
          <Column field="fecha_salida_estimada" header="Salida Est." />
          <Column field="kilometraje" header="KM" />
          <Column field="estado" header="Estado">
            <template #body="{ data }">
              <Tag :value="data.estado || 'abierta'" :severity="data.estado === 'cerrada' ? 'success' : data.estado === 'cancelada' ? 'danger' : 'info'" />
            </template>
          </Column>
          <Column header="Acciones" style="width:120px">
            <template #body="{ data }">
              <Button icon="pi pi-pencil" text rounded severity="info" />
              <Button icon="pi pi-trash" text rounded severity="danger" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </AppLayout>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
const props = defineProps({ ordenes: Object });
</script>
