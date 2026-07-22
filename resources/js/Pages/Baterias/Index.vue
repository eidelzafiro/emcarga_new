<template>
  <AppLayout>
    <Card>
      <template #title>Baterías</template>
      <template #content>
        <DataTable :value="baterias.data" paginator :rows="20" stripedRows class="p-datatable-sm">
          <Column field="folio" header="Folio" sortable />
          <Column field="marca" header="Marca" />
          <Column field="modelo" header="Modelo" />
          <Column field="tractivo.descripcion" header="Tractivo" />
          <Column field="fecha_instalacion" header="Instalación" />
          <Column field="estado" header="Estado">
            <template #body="{ data }">
              <Tag :value="data.estado" :severity="data.estado === 'activa' ? 'success' : 'danger'" />
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
const props = defineProps({ baterias: Object });
</script>
