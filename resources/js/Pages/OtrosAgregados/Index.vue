<template>
  <AppLayout>
    <Card>
      <template #title>Otros Agregados</template>
      <template #content>
        <DataTable :value="agregados.data" paginator :rows="20" stripedRows class="p-datatable-sm">
          <Column field="codigo" header="Código" sortable />
          <Column field="descripcion" header="Descripción" sortable />
          <Column field="numero_serie" header="N° Serie" />
          <Column field="marca.nombre" header="Marca" />
          <Column field="fecha_baja" header="Fecha Baja" />
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
const props = defineProps({ agregados: Object });
</script>
