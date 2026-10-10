<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    event: Object,
    metrics: Object,
    can: Object,
});

const cards = computed(() => [
    { label: 'Confirmados', value: props.metrics.confirmed_hosts, hint: 'Titulares' },
    { label: 'Acompanhantes', value: props.metrics.confirmed_companions, hint: 'Vinculados' },
    { label: 'Representantes', value: props.metrics.confirmed_representatives, hint: 'Substituições' },
    { label: 'Não confirmados', value: props.metrics.not_confirmed, hint: 'Pendentes ou recusados' },
    { label: 'Presenças', value: props.metrics.present, hint: 'Credenciados' },
]);

function rate(value) {
    return value === null ? 'Indisponível' : `${value}%`;
}
</script>

<template>
    <Head :title="`Painel · ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ event.name }}
                </h2>
                <Link
                    :href="route('guests.index', event.id)"
                    class="rounded-md bg-sky-700 px-3 py-2 text-sm font-medium text-white hover:bg-sky-800"
                >
                    Ver convidados
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Aviso de conflitos pendentes (CA-06) -->
                <div
                    v-if="metrics.has_pending_conflicts"
                    role="alert"
                    class="rounded-md border-l-4 border-amber-500 bg-amber-50 p-4 text-sm text-amber-800"
                >
                    Há conflitos de vínculo (titular e representante confirmados) pendentes de resolução.
                    Os quantitativos definitivos dependem dessa resolução.
                </div>

                <!-- Indicadores principais -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <div
                        v-for="card in cards"
                        :key="card.label"
                        class="rounded-lg bg-white p-5 shadow"
                    >
                        <p class="text-sm font-medium text-gray-500">{{ card.label }}</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">{{ card.value }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ card.hint }}</p>
                    </div>
                </div>

                <!-- Participação prevista e taxas -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="rounded-lg bg-white p-5 shadow">
                        <p class="text-sm font-medium text-gray-500">Participação prevista</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">
                            {{ metrics.expected_participants }}
                            <span class="text-lg font-normal text-gray-400"> / {{ metrics.estimated_audience }}</span>
                        </p>
                        <p class="mt-1 text-xs text-gray-400">Público estimado: {{ metrics.estimated_audience }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow">
                        <p class="text-sm font-medium text-gray-500">Taxa de confirmação</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">{{ rate(metrics.confirmation_rate) }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow">
                        <p class="text-sm font-medium text-gray-500">Taxa de comparecimento</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">{{ rate(metrics.attendance_rate) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
