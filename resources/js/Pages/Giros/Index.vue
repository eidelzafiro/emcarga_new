<template><AppLayout><Card><template #title>Cartas Porte</template><template #content>
<DataTable :value="giros.data" paginator :rows="20" stripedRows class="p-datatable-sm">
<Column field="numero_carta_porte" header="Carta Porte" sortable />
<Column field="cliente.nombre" header="Cliente" />
<Column field="fecha_parte" header="Fecha" />
<Column field="ingreso_mt" header="Ingreso MT" />
<Column field="flete_mt" header="Flete MT" />
<Column field="estado" header="Estado"><template #body="{ data }"><Tag :value="data.estado" :severity="data.estado === 'activo' ? 'success' : 'danger'" /></template></Column>
<Column header="Acciones" style="width:120px"><template #body="{ data }"><Button icon="pi pi-pencil" text rounded severity="info" /><Button icon="pi pi-trash" text rounded severity="danger" /></template></Column>
</DataTable></template></Card></AppLayout></template>
<script setup>import AppLayout from '@/Layouts/AppLayout.vue'; const props = defineProps({ giros: Object });</script>
