<template><AppLayout><Card><template #title>Distancias</template><template #content>
<DataTable :value="distancias.data" paginator :rows="20" stripedRows class="p-datatable-sm">
<Column field="origen.nombre" header="Origen" />
<Column field="destino.nombre" header="Destino" />
<Column field="distancia_km" header="KM" />
<Column header="Acciones" style="width:120px"><template #body="{ data }"><Button icon="pi pi-pencil" text rounded severity="info" /><Button icon="pi pi-trash" text rounded severity="danger" /></template></Column>
</DataTable></template></Card></AppLayout></template>
<script setup>import AppLayout from '@/Layouts/AppLayout.vue'; const props = defineProps({ distancias: Object });</script>
