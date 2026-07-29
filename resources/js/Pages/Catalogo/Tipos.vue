<script setup>
import { route } from 'ziggy-js'
import { router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Panel from 'primevue/panel'
import Button from 'primevue/button'

const props = defineProps({ grupos: Object })

const page = usePage()
const permissions = computed(() => page.props.auth?.permissions ?? [])
function can(permiso) {
  return permissions.value.includes(permiso)
}

function goTo(tipo) {
  window.location.href = route('catalogo.index', { tipo })
}
function irGestionar() {
  router.visit(route('catalogo.gestionar'))
}
</script>

<template>
  <AppLayout title="Catálogos">
    <div class="card">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Catálogos del Sistema</h1>
        <Button
          v-if="can('catalogo.editar')"
          icon="pi pi-cog"
          label="Gestionar tipos"
          severity="secondary"
          size="small"
          @click="irGestionar"
        />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Panel v-for="(tipos, grupo) in grupos" :key="grupo" :header="grupo" toggleable>
          <div class="flex flex-col gap-2">
            <Button
              v-for="item in tipos"
              :key="item.tipo"
              :label="item.titulo"
              severity="secondary"
              text
              class="justify-start"
              @click="goTo(item.tipo)"
            />
          </div>
        </Panel>
      </div>
    </div>
  </AppLayout>
</template>
