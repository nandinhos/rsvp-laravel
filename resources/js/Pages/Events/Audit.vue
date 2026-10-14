<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHero from '@/Components/ui/PageHero.vue';
import PageContainer from '@/Components/ui/PageContainer.vue';
import UiButton from '@/Components/ui/UiButton.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';

defineProps({
    event: Object,
    logs: Object,
});

const actionLabel = {
    'guest.created': 'Convidado criado',
    'guest.deleted': 'Convidado removido',
    'guest.confirmed': 'Participação confirmada',
    'guest.declined': 'Ausência registrada',
    'guest.checked_in': 'Credenciamento',
    'guests.imported': 'Importação',
    'conflict.resolved': 'Conflito resolvido',
    'event.closed': 'Evento encerrado',
    'event.reopened': 'Evento reaberto',
};
</script>

<template>
    <Head :title="`Auditoria · ${event.name}`" />

    <AuthenticatedLayout>
        <PageHero :title="`Auditoria — ${event.name}`" subtitle="Registro append-only de operações críticas (CA-10).">
            <template #actions>
                <UiButton :href="route('events.dashboard', event.id)" variant="onLightGhost" size="md">Voltar ao painel</UiButton>
            </template>
        </PageHero>

        <PageContainer>
            <EmptyState v-if="logs.data.length === 0" message="Nenhum registro de auditoria ainda." />

            <div v-else class="overflow-hidden rounded-2xl border border-institutional-900/8 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-institutional-900/8 text-sm">
                    <thead class="bg-institutional-50 text-left text-xs uppercase tracking-wide text-institutional-text/50">
                        <tr>
                            <th class="px-4 py-3">Data/Hora</th>
                            <th class="px-4 py-3">Ação</th>
                            <th class="px-4 py-3">Usuário</th>
                            <th class="px-4 py-3">Origem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-institutional-900/6">
                        <tr v-for="log in logs.data" :key="log.id" class="transition-colors hover:bg-institutional-50/60">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-institutional-text/50">{{ log.created_at }}</td>
                            <td class="px-4 py-3 text-institutional-900">{{ actionLabel[log.action] ?? log.action }}</td>
                            <td class="px-4 py-3 text-institutional-text/80">{{ log.user }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-institutional-text/40">{{ log.ip_address }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </PageContainer>
    </AuthenticatedLayout>
</template>
