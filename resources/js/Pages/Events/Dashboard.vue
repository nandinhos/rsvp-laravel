<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    event: Object,
    metrics: Object,
    can: Object,
});

function action(name) {
    router.post(route(name, props.event.id), {}, { preserveScroll: true });
}

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
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ event.name }}
                </h2>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="metrics.has_pending_conflicts && can.operate"
                        :href="route('conflicts.index', event.id)"
                        class="rounded-md bg-amber-500 px-3 py-2 text-sm font-medium text-white hover:bg-amber-600"
                    >
                        Resolver conflitos
                    </Link>
                    <Link
                        v-if="can.administer"
                        :href="route('events.audit', event.id)"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Auditoria
                    </Link>
                    <button
                        v-if="can.administer && event.status !== 'encerrado'"
                        @click="action('events.close')"
                        class="rounded-md border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50"
                    >
                        Encerrar
                    </button>
                    <button
                        v-if="can.administer && event.status === 'encerrado'"
                        @click="action('events.reopen')"
                        class="rounded-md border border-emerald-300 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                    >
                        Reabrir
                    </button>
                    <Link
                        :href="route('guests.index', event.id)"
                        class="rounded-md bg-sky-700 px-3 py-2 text-sm font-medium text-white hover:bg-sky-800"
                    >
                        Ver convidados
                    </Link>
                </div>
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
                        class="stat-card group relative overflow-hidden rounded-2xl border border-institutional-deep/8 bg-white p-5 shadow-sm"
                    >
                        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-institutional-base to-institutional-interaction opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                        <p class="text-xs font-medium uppercase tracking-wide text-institutional-text/50">{{ card.label }}</p>
                        <p class="mt-1.5 text-3xl font-bold text-institutional-deep">{{ card.value }}</p>
                        <p class="mt-1 text-xs text-institutional-text/40">{{ card.hint }}</p>
                    </div>
                </div>

                <!-- Participação prevista e taxas -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="rounded-2xl border border-institutional-deep/8 bg-gradient-to-br from-institutional-deep to-institutional-base p-6 shadow-lg shadow-institutional-deep/20">
                        <p class="text-xs font-medium uppercase tracking-wide text-white/60">Participação prevista</p>
                        <p class="mt-1.5 text-3xl font-bold text-white">
                            {{ metrics.expected_participants }}
                            <span class="text-lg font-normal text-white/50"> / {{ metrics.estimated_audience }}</span>
                        </p>
                        <p class="mt-1 text-xs text-white/50">Público estimado: {{ metrics.estimated_audience }}</p>
                    </div>
                    <div class="stat-card rounded-2xl border border-institutional-deep/8 bg-white p-6 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-institutional-text/50">Taxa de confirmação</p>
                        <p class="mt-1.5 text-3xl font-bold text-institutional-base">{{ rate(metrics.confirmation_rate) }}</p>
                    </div>
                    <div class="stat-card rounded-2xl border border-institutional-deep/8 bg-white p-6 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-institutional-text/50">Taxa de comparecimento</p>
                        <p class="mt-1.5 text-3xl font-bold text-institutional-interaction">{{ rate(metrics.attendance_rate) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.stat-card {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 32px -16px rgba(11, 42, 74, 0.3);
}
</style>
