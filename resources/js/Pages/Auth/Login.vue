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

        <div class="mb-6">
            <h2 class="text-2xl font-bold tracking-tight text-institutional-deep">Bem-vindo de volta</h2>
            <p class="mt-1 text-sm text-institutional-text/60">Acesse o painel de gestão de convidados.</p>
        </div>

        <div v-if="status" class="mb-4 rounded-lg bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700">
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
                    class="text-sm font-medium text-institutional-interaction hover:text-institutional-deep focus:outline-none focus-visible:ring-2 focus-visible:ring-institutional-interaction focus-visible:ring-offset-2"
                >
                    Esqueceu a senha?
                </Link>
            </div>

            <div class="mt-6">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Entrar
                </PrimaryButton>
            </div>
        </form>

        <!-- Usuários de teste por perfil (ambiente de demonstração) -->
        <div class="mt-6 rounded-xl border border-institutional-interaction/15 bg-institutional-neutral p-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-institutional-base">
                Usuários de teste · senha: <code class="rounded bg-white px-1 py-0.5 font-mono text-institutional-deep">password</code>
            </p>
            <ul class="space-y-1">
                <li
                    v-for="u in testUsers"
                    :key="u.email"
                    class="flex items-center justify-between text-sm"
                >
                    <span class="text-institutional-text/80">
                        <strong>{{ u.role }}</strong> — <span class="font-mono text-institutional-text/60">{{ u.email }}</span>
                    </span>
                    <button
                        type="button"
                        @click="useCredentials(u.email)"
                        class="rounded-lg border border-institutional-interaction/40 px-2.5 py-1 text-xs font-medium text-institutional-base transition-colors hover:bg-institutional-base hover:text-white"
                    >
                        Usar
                    </button>
                </li>
            </ul>
        </div>
    </GuestLayout>
</template>
