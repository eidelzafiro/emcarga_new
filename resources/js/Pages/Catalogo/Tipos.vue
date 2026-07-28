<script setup>
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import Panel from 'primevue/panel'
import Button from 'primevue/button'

const props = defineProps({ grupos: Object })

function goTo(tipo) {
  window.location.href = route('catalogo.index', { tipo })
}
</script>

<template>
  <AppLayout title="Catálogos">
    <div class="card">
      <h1 class="text-2xl font-bold mb-6">Catálogos del Sistema</h1>
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
