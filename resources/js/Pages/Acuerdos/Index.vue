<template><AppLayout><Card><template #title>Acuerdos Tarifarios</template><template #content>
<DataTable :value="acuerdos.data" paginator :rows="20" stripedRows class="p-datatable-sm">
<Column field="codigo" header="Código" sortable />
<Column field="descripcion" header="Descripción" />
<Column field="cliente.nombre" header="Cliente" />
<Column field="tarifa_base" header="Tarifa Base" />
<Column field="moneda" header="Moneda" />
<Column field="fecha_inicio" header="Inicio" />
<Column field="fecha_fin" header="Fin" />
<Column header="Acciones" style="width:120px"><template #body="{ data }"><Button icon="pi pi-pencil" text rounded severity="info" /><Button icon="pi pi-trash" text rounded severity="danger" /></template></Column>
</DataTable></template></Card></AppLayout></template>
<script setup>import AppLayout from '@/Layouts/AppLayout.vue'; const props = defineProps({ acuerdos: Object });</script>
