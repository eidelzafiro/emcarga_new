<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <span class="text-xl font-bold text-indigo-600">EMCARGA</span>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <Link
                v-for="link in navigation"
                :key="link.href"
                :href="link.href"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out"
                :class="isActive(link.href) ? 'border-indigo-500' : 'border-transparent hover:border-gray-300'"
              >
                {{ link.label }}
              </Link>
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
import { computed } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permiso) => permissions.value.includes(permiso);

// NOTA: este menú se reemplaza en la Fase 4.5 por el menú dinámico
// desde base de datos filtrado por perfil. Por ahora es estático
// pero ya filtrado por permisos.
const navigation = computed(() => [
  { href: route('dashboard'), label: 'Dashboard', show: can('dashboard.ver') },
  { href: route('tractivos.index'), label: 'Flota', show: can('tractivos.ver') },
  { href: route('usuarios.index'), label: 'Usuarios', show: can('usuarios.ver') },
].filter(item => item.show));

const isActive = (href) => {
  return page.url === href;
};
</script>
