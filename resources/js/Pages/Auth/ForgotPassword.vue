<template>
  <div class="min-h-screen flex">
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-950 via-blue-900 to-gray-900 relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-20 left-10 w-72 h-72 bg-blue-400 rounded-full blur-3xl" />
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-sky-300 rounded-full blur-3xl" />
      </div>
      <div class="relative z-10 flex flex-col justify-between p-12 w-full">
        <div>
          <div class="flex items-center gap-3">
            <img src="/images/zafiro-icon.png" alt="Zafiro" class="w-10 h-10 rounded-lg bg-white p-0.5 shadow-md" />
            <span class="text-white font-semibold text-lg">{{ appName }}</span>
          </div>
        </div>
        <div>
          <h1 class="text-4xl font-bold text-white leading-tight">Recuperar acceso</h1>
          <p class="text-blue-200/80 mt-4 text-lg max-w-md">
            Le enviaremos un enlace de restablecimiento al correo registrado en el sistema.
          </p>
        </div>
        <p class="text-blue-200/40 text-sm">&copy; 2026 {{ appName }}. Todos los derechos reservados.</p>
      </div>
    </div>

    <div class="flex-1 flex items-center justify-center px-6 py-12 bg-white">
      <div class="w-full max-w-sm">
        <div class="lg:hidden flex items-center justify-center mb-8">
          <img src="/images/zafiro-logo.jpg" alt="Zafiro" class="w-48 rounded-xl shadow-md" />
        </div>

        <div class="text-center lg:text-left mb-8">
          <h2 class="text-2xl font-bold text-gray-900">¿Olvidó su contraseña?</h2>
          <p class="text-gray-500 mt-1">Escriba su correo registrado para recibir un enlace de restablecimiento.</p>
        </div>

        <div v-if="$page.props.status" class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-start gap-2">
          <i class="pi pi-check-circle mt-0.5" />
          <span>{{ $page.props.status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico</label>
            <div class="relative">
              <i class="pi pi-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autofocus
                autocomplete="email"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': form.errors.email }"
                placeholder="usuario@dominio.cu"
              />
            </div>
            <small v-if="form.errors.email" class="text-red-500 text-xs mt-1 block">{{ form.errors.email }}</small>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <i v-if="form.processing" class="pi pi-spin pi-spinner" />
            {{ form.processing ? 'Enviando…' : 'Enviar enlace' }}
          </button>
        </form>

        <div class="text-center mt-6">
          <Link :href="route('login')" class="text-sm text-blue-600 hover:text-blue-800 inline-flex items-center gap-1.5">
            <i class="pi pi-arrow-left text-xs" /> Volver a iniciar sesión
          </Link>
        </div>

        <p class="text-center text-gray-400 text-xs mt-8 lg:hidden">{{ appName }} &copy; 2026</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const appName = computed(() => usePage().props.appName || 'Zafiro');

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(route('password.email'));
};
</script>
