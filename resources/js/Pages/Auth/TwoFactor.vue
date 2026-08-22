<template>
  <div class="min-h-screen flex items-center justify-center px-6 py-12 bg-gradient-to-br from-blue-950 via-blue-900 to-gray-900">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl p-8">
      <div class="flex items-center gap-3 mb-6">
        <img src="/images/zafiro-icon.png" alt="Zafiro" class="w-9 h-9 rounded-lg" />
        <span class="font-semibold text-lg text-gray-900">EMCARGA</span>
      </div>

      <h2 class="text-2xl font-bold text-gray-900">Verificación en dos pasos</h2>
      <p class="text-gray-500 mt-1 mb-6">
        Ingrese el código de 6 dígitos de su aplicación autenticadora.
      </p>

      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label for="code" class="block text-sm font-medium text-gray-700 mb-1.5">
            Código
          </label>
          <input
            id="code"
            v-model="form.code"
            type="text"
            inputmode="numeric"
            maxlength="6"
            autocomplete="one-time-code"
            placeholder="000000"
            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center text-2xl tracking-widest"
            :class="{ 'border-red-400': form.errors.code }"
          />
          <p v-if="form.errors.code" class="text-red-500 text-sm mt-1.5">{{ form.errors.code }}</p>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition disabled:opacity-60"
        >
          {{ form.processing ? 'Verificando…' : 'Verificar' }}
        </button>
      </form>

      <button
        @click="cancel"
        class="w-full mt-3 text-sm text-gray-400 hover:text-gray-600"
      >
        Cancelar e iniciar sesión de nuevo
      </button>
    </div>
  </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const form = useForm({
  code: '',
});

const submit = () => {
  form.post(route('two-factor.store'), {
    onFinish: () => form.reset('code'),
  });
};

const cancel = () => {
  router.visit(route('logout'), { method: 'post' });
};
</script>
