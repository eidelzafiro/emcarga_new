<template>
  <div class="min-h-screen flex">
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-emerald-900 via-emerald-800 to-gray-900 relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-20 left-10 w-72 h-72 bg-emerald-400 rounded-full blur-3xl" />
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-emerald-300 rounded-full blur-3xl" />
      </div>
      <div class="relative z-10 flex flex-col justify-between p-12 w-full">
        <div>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center">
              <span class="text-white font-bold text-lg">{{ appName.charAt(0).toUpperCase() }}</span>
            </div>
            <span class="text-white font-semibold text-lg">{{ appName }}</span>
          </div>
        </div>
        <div>
          <h1 class="text-4xl font-bold text-white leading-tight">
            Sistema de Gestión<br />Empresarial
          </h1>
          <p class="text-emerald-200/80 mt-4 text-lg max-w-md">
            Control de flota, facturación, RRHH y más. Todo en un solo lugar.
          </p>
          <div class="mt-8 flex items-center gap-6 text-emerald-200/60 text-sm">
            <span class="flex items-center gap-2">
              <i class="pi pi-shield" /> Seguro
            </span>
            <span class="flex items-center gap-2">
              <i class="pi pi-sync" /> Tiempo real
            </span>
            <span class="flex items-center gap-2">
              <i class="pi pi-lock" /> Encriptado
            </span>
          </div>
        </div>
        <p class="text-emerald-200/40 text-sm">&copy; 2026 {{ appName }}. Todos los derechos reservados.</p>
      </div>
    </div>

    <div class="flex-1 flex items-center justify-center px-6 py-12 bg-white">
      <div class="w-full max-w-sm">
        <div class="lg:hidden flex items-center justify-center mb-8">
          <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-xl">
            {{ appName.charAt(0).toUpperCase() }}
          </div>
        </div>

        <div class="text-center lg:text-left mb-8">
          <h2 class="text-2xl font-bold text-gray-900">Iniciar sesión</h2>
          <p class="text-gray-500 mt-1">Ingresa tus credenciales para acceder</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1.5">
              Usuario
            </label>
            <div class="relative">
              <i class="pi pi-user absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
              <input
                id="username"
                v-model="form.username"
                type="text"
                required
                autofocus
                autocomplete="username"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.username }"
                placeholder="nombre de usuario"
              />
            </div>
            <small v-if="form.errors.username" class="text-red-500 text-xs mt-1 block">{{ form.errors.username }}</small>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
              Contraseña
            </label>
            <div class="relative">
              <i class="pi pi-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="current-password"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.password }"
                placeholder="••••••••"
              />
            </div>
            <small v-if="form.errors.password" class="text-red-500 text-xs mt-1 block">{{ form.errors.password }}</small>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <i v-if="form.processing" class="pi pi-spin pi-spinner" />
            {{ form.processing ? 'Entrando…' : 'Entrar' }}
          </button>

          <div v-if="form.recentlySuccessful" class="flex items-center gap-2 text-emerald-600 text-sm justify-center">
            <i class="pi pi-check-circle" />
            <span>Sesión iniciada correctamente</span>
          </div>
        </form>

        <p class="text-center text-gray-400 text-xs mt-8 lg:hidden">
          {{ appName }} &copy; 2026
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const appName = computed(() => usePage().props.appName || 'Zafiro');

const form = useForm({
  username: '',
  password: '',
});

const submit = () => {
  form.post(route('login.store'), {
    onFinish: () => form.reset('password'),
  });
};
</script>
