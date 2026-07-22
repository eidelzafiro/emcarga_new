<template>
  <AppLayout>
    <div class="space-y-6">
      <Card>
        <template #title>
          Bienvenido, {{ user?.name }}
        </template>
        <template #content>
          <p class="text-surface-600 leading-relaxed">
            Sistema de gestión EMCARGA — Panel de control
          </p>
        </template>
      </Card>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card v-for="kpi in kpis" :key="kpi.label">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-surface-500 font-medium">{{ kpi.label }}</p>
                <p class="text-2xl font-bold text-surface-900 mt-1">{{ kpi.valor }}</p>
              </div>
              <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="kpi.color">
                <i :class="kpi.icono" class="text-white text-lg" />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const user = page.props.auth?.user;

const kpis = [
  { label: 'Vehículos activos', valor: '—', icono: 'pi pi-truck', color: 'bg-emerald-500' },
  { label: 'Conductores', valor: '—', icono: 'pi pi-users', color: 'bg-blue-500' },
  { label: 'Órdenes hoy', valor: '—', icono: 'pi pi-clipboard', color: 'bg-amber-500' },
  { label: 'Ingresos del mes', valor: '—', icono: 'pi pi-dollar', color: 'bg-violet-500' },
];
</script>
