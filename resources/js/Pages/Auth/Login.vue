<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// Usuários de teste por perfil (criados pelo seeder). Facilitam a bateria de testes.
const testUsers = [
    { role: 'Administrador', email: 'admin@rsvp.test' },
    { role: 'Gestor', email: 'gestor@rsvp.test' },
    { role: 'Operador', email: 'operador@rsvp.test' },
    { role: 'Leitor', email: 'leitor@rsvp.test' },
];

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

function useCredentials(email) {
    form.email = email;
    form.password = 'password';
}
</script>

<template>
    <GuestLayout>
        <Head title="Entrar" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="E-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Senha" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Manter conectado</span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                >
                    Esqueceu a senha?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Entrar
                </PrimaryButton>
            </div>
        </form>

        <!-- Usuários de teste por perfil (ambiente de demonstração) -->
        <div class="mt-6 rounded-lg border border-sky-100 bg-sky-50 p-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-sky-800">
                Usuários de teste · senha: <code class="font-mono">password</code>
            </p>
            <ul class="space-y-1">
                <li
                    v-for="u in testUsers"
                    :key="u.email"
                    class="flex items-center justify-between text-sm"
                >
                    <span class="text-gray-700">
                        <strong>{{ u.role }}</strong> — <span class="font-mono text-gray-600">{{ u.email }}</span>
                    </span>
                    <button
                        type="button"
                        @click="useCredentials(u.email)"
                        class="rounded border border-sky-300 px-2 py-0.5 text-xs font-medium text-sky-700 hover:bg-sky-100"
                    >
                        Usar
                    </button>
                </li>
            </ul>
        </div>
    </GuestLayout>
</template>
