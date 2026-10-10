<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import UiButton from '@/Components/ui/UiButton.vue';

const props = defineProps({
    event: Object,
    metrics: Object,
    can: Object,
});

function action(name) {
    router.post(route(name, props.event.id), {}, { preserveScroll: true });
}

const cards = computed(() => [
    { label: 'Confirmados', value: props.metrics.confirmed_hosts, hint: 'Titulares', accent: 'ink' },
    { label: 'Acompanhantes', value: props.metrics.confirmed_companions, hint: 'Vinculados', accent: 'base' },
    { label: 'Representantes', value: props.metrics.confirmed_representatives, hint: 'Substituições', accent: 'interaction' },
    { label: 'Não confirmados', value: props.metrics.not_confirmed, hint: 'Pendentes ou recusados', accent: 'ink' },
    { label: 'Presenças', value: props.metrics.present, hint: 'Credenciados', accent: 'base' },
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
                <h2 class="text-xl font-semibold leading-tight text-institutional-900">
                    {{ event.name }}
                </h2>
                <div class="flex flex-wrap gap-2">
                    <UiButton
                        v-if="metrics.has_pending_conflicts && can.operate"
                        :href="route('conflicts.index', event.id)"
                        variant="primary"
                        class="!bg-amber-500 hover:!bg-amber-600"
                    >
                        Resolver conflitos
                    </UiButton>
                    <UiButton v-if="can.administer" :href="route('events.audit', event.id)" variant="neutral">
                        Auditoria
                    </UiButton>
                    <UiButton
                        v-if="can.administer && event.status !== 'encerrado'"
                        variant="secondary"
                        class="!border-rose-300 !text-rose-700 hover:!bg-rose-50"
                        @click="action('events.close')"
                    >
                        Encerrar
                    </UiButton>
                    <UiButton
                        v-if="can.administer && event.status === 'encerrado'"
                        variant="secondary"
                        class="!border-emerald-300 !text-emerald-700 hover:!bg-emerald-50"
                        @click="action('events.reopen')"
                    >
                        Reabrir
                    </UiButton>
                    <UiButton :href="route('guests.index', event.id)" variant="primary">
                        Ver convidados
                    </UiButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Aviso de conflitos pendentes (CA-06) -->
                <div
                    v-if="metrics.has_pending_conflicts"
                    role="alert"
                    class="rounded-xl border-l-4 border-amber-500 bg-amber-50 p-4 text-sm text-amber-800"
                >
                    Há conflitos de vínculo (titular e representante confirmados) pendentes de resolução.
                    Os quantitativos definitivos dependem dessa resolução.
                </div>

                <!-- Indicadores principais -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <StatCard
                        v-for="card in cards"
                        :key="card.label"
                        :label="card.label"
                        :value="card.value"
                        :hint="card.hint"
                        :accent="card.accent"
                    />
                </div>

                <!-- Participação prevista e taxas -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <StatCard
                        variant="solid"
                        label="Participação prevista"
                        :value="metrics.expected_participants"
                        :hint="`Público estimado: ${metrics.estimated_audience}`"
                    >
                        <template #value>
                            {{ metrics.expected_participants }}
                            <span class="text-lg font-normal text-white/50"> / {{ metrics.estimated_audience }}</span>
                        </template>
                    </StatCard>
                    <StatCard label="Taxa de confirmação" :value="rate(metrics.confirmation_rate)" accent="base" />
                    <StatCard label="Taxa de comparecimento" :value="rate(metrics.attendance_rate)" accent="interaction" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
