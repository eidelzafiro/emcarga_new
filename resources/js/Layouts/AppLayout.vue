<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <span class="text-xl font-bold text-indigo-600">EMCARGA</span>
            </div>
            <!-- Menú dinámico por perfil (Fase 4.5) -->
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <div
                v-for="item in menu"
                :key="item.label"
                class="relative flex"
              >
                <!-- Ítem con ruta -->
                <Link
                  v-if="item.url"
                  :href="item.url"
                  class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out"
                  :class="esActivo(item) ? 'border-indigo-500' : 'border-transparent hover:border-gray-300'"
                >
                  {{ item.label }}
                </Link>

                <!-- Agrupador con hijos (dropdown) -->
                <div v-else class="inline-flex items-center">
                  <button
                    class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-900 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out"
                    :class="{ 'border-indigo-500': grupoActivo(item) }"
                    @click="alternarDropdown(item.label)"
                  >
                    {{ item.label }}
                    <svg class="ml-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div
                    v-show="dropdownAbierto === item.label"
                    class="absolute top-16 z-10 w-48 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 py-1"
                  >
                    <Link
                      v-for="hijo in item.children"
                      :key="hijo.label"
                      :href="hijo.url"
                      class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700"
                      :class="{ 'bg-indigo-50 text-indigo-700': esActivo(hijo) }"
                    >
                      {{ hijo.label }}
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <div class="ml-3 relative">
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-700">{{ user?.name }}</span>
                <Link
                  :href="route('password.edit')"
                  class="text-sm text-gray-500 hover:text-gray-700"
                >
                  Cambiar contraseña
                </Link>
                <Link
                  :href="route('logout')"
                  method="post"
                  as="button"
                  class="text-sm text-gray-500 hover:text-gray-700"
                >
                  Cerrar sesión
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <header class="bg-white shadow" v-if="$slots.header">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <slot name="header" />
      </div>
    </header>

    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// Menú dinámico construido por MenuBuilder según los permisos del usuario
const menu = computed(() => page.props.menu ?? []);

const dropdownAbierto = ref(null);
const alternarDropdown = (label) => {
  dropdownAbierto.value = dropdownAbierto.value === label ? null : label;
};

const esActivo = (item) => {
  return item.url && page.url.startsWith(new URL(item.url).pathname);
};

const grupoActivo = (item) => {
  return item.children?.some((hijo) => esActivo(hijo));
};
</script>
