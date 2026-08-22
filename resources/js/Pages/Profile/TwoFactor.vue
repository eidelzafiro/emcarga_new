<template>
  <div class="max-w-2xl mx-auto p-6">
    <div class="bg-white rounded-2xl shadow p-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-1">Verificación en dos pasos (2FA)</h1>
      <p class="text-gray-500 mb-6">
        Protege tu cuenta privilegiada con un código temporal de un autenticador.
      </p>

      <!-- Ya habilitado -->
      <div v-if="enabled && !secret" class="space-y-4">
        <div class="flex items-center gap-2 text-green-700 bg-green-50 border border-green-200 rounded-lg p-3">
          <i class="pi pi-check-circle" />
          <span>2FA está activado en tu cuenta.</span>
        </div>
        <form @submit.prevent="disable">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold disabled:opacity-60"
          >
            Deshabilitar 2FA
          </button>
        </form>
      </div>

      <!-- No habilitado: botón para iniciar -->
      <div v-else-if="!secret" class="space-y-4">
        <div class="flex items-center gap-2 text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
          <i class="pi pi-exclamation-triangle" />
          <span>2FA no está activado. Se recomienda encarecidamente para perfiles privilegiados.</span>
        </div>
        <form @submit.prevent="start">
          <button
            type="submit"
            :disabled="startForm.processing"
            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold disabled:opacity-60"
          >
            Habilitar 2FA
          </button>
        </form>
      </div>

      <!-- Configurando: mostrar secreto + recuperación + confirmar -->
      <div v-else class="space-y-5">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm font-medium text-blue-900 mb-1">1. Añade esta cuenta a tu autenticador</p>
          <p class="text-xs text-blue-700 break-all">{{ otpauth }}</p>
          <p class="text-xs text-blue-700 mt-2">Clave manual: <span class="font-mono">{{ secret }}</span></p>
        </div>

        <div class="bg-gray-50 border rounded-lg p-4">
          <p class="text-sm font-medium text-gray-700 mb-1">2. Guarda tus códigos de recuperación</p>
          <p class="text-xs text-gray-500 mb-2">Úsalos si pierdes el dispositivo. Son de un solo uso.</p>
          <ul class="grid grid-cols-2 gap-1 text-sm font-mono text-gray-700">
            <li v-for="c in recovery" :key="c">{{ c }}</li>
          </ul>
        </div>

        <form @submit.prevent="confirm" class="space-y-3">
          <p class="text-sm font-medium text-gray-700">3. Ingresa el código del autenticador para confirmar</p>
          <input
            v-model="confirmForm.code"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="000000"
            class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 text-center text-2xl tracking-widest"
            :class="{ 'border-red-400': confirmForm.errors.code }"
          />
          <p v-if="confirmForm.errors.code" class="text-red-500 text-sm">{{ confirmForm.errors.code }}</p>
          <button
            type="submit"
            :disabled="confirmForm.processing"
            class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold disabled:opacity-60"
          >
            Confirmar y activar
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps({
  otpauth: { type: String, default: '' },
  secret: { type: String, default: '' },
  recovery: { type: Array, default: () => [] },
  enabled: { type: Boolean, default: false },
});

const startForm = useForm({});
const confirmForm = useForm({ code: '' });
const form = useForm({});

const start = () => {
  startForm.get(route('two-factor.enable'));
};

const confirm = () => {
  confirmForm.post(route('two-factor.confirm'), {
    onFinish: () => confirmForm.reset('code'),
  });
};

const disable = () => {
  form.post(route('two-factor.disable'));
};
</script>
