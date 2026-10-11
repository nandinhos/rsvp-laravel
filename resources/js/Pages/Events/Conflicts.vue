<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHero from '@/Components/ui/PageHero.vue';
import PageContainer from '@/Components/ui/PageContainer.vue';
import UiButton from '@/Components/ui/UiButton.vue';
import UiCard from '@/Components/ui/UiCard.vue';
import UiBadge from '@/Components/ui/UiBadge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';

const props = defineProps({
    event: Object,
    conflicts: Array,
});

function resolve(relationshipId, keep) {
    router.post(
        route('conflicts.resolve', [props.event.id, relationshipId]),
        { keep },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="`Conflitos · ${event.name}`" />

    <AuthenticatedLayout>
        <PageHero
            title="Conflitos de representação"
            :subtitle="`${event.name} — quando titular e representante confirmam ao mesmo tempo, a decisão é explícita e auditada. Nada é alterado silenciosamente.`"
        />

        <PageContainer>
            <div class="space-y-5">
                <EmptyState v-if="conflicts.length === 0" title="Nenhum conflito pendente" message="Todos os vínculos estão consistentes." />

                <UiCard v-for="c in conflicts" :key="c.id" padding="p-0">
                    <!-- Cabeçalho do conflito -->
                    <div class="flex items-center gap-2 border-b border-institutional-900/8 px-5 py-3">
                        <svg class="h-5 w-5 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                        <p class="text-sm font-semibold text-institutional-900">Confirmação mútua detectada</p>
                    </div>

                    <!-- Tabela: posição atual × conflitante -->
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-institutional-900/8 text-sm">
                            <thead class="bg-institutional-50 text-left text-xs font-semibold uppercase tracking-wide text-institutional-text/50">
                                <tr>
                                    <th class="px-5 py-3">Posição</th>
                                    <th class="px-5 py-3">#</th>
                                    <th class="px-5 py-3">Nome</th>
                                    <th class="px-5 py-3">Papel</th>
                                    <th class="px-5 py-3">RSVP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-institutional-900/6">
                                <tr>
                                    <td class="px-5 py-3"><UiBadge variant="info">Titular</UiBadge></td>
                                    <td class="px-5 py-3 font-mono text-institutional-text/40">#{{ c.host.number }}</td>
                                    <td class="px-5 py-3 font-medium text-institutional-900">{{ c.host.name }}</td>
                                    <td class="px-5 py-3 text-institutional-text/70">Titular</td>
                                    <td class="px-5 py-3"><UiBadge variant="success">Confirmado</UiBadge></td>
                                </tr>
                                <tr class="bg-amber-50/40">
                                    <td class="px-5 py-3"><UiBadge variant="warning">Conflitante</UiBadge></td>
                                    <td class="px-5 py-3 font-mono text-institutional-text/40">#{{ c.representative.number }}</td>
                                    <td class="px-5 py-3 font-medium text-institutional-900">{{ c.representative.name }}</td>
                                    <td class="px-5 py-3 text-institutional-text/70">Representante</td>
                                    <td class="px-5 py-3"><UiBadge variant="success">Confirmado</UiBadge></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Ações de resolução -->
                    <div class="flex flex-col gap-2 border-t border-institutional-900/8 px-5 py-4 sm:flex-row sm:flex-wrap">
                        <UiButton variant="primary" size="md" @click="resolve(c.id, 'host')">
                            Manter titular (#{{ c.host.number }})
                        </UiButton>
                        <UiButton variant="secondary" size="md" @click="resolve(c.id, 'representative')">
                            Manter representante (#{{ c.representative.number }})
                        </UiButton>
                        <UiButton
                            variant="success"
                            size="md"
                            class="sm:ml-auto"
                            @click="resolve(c.id, 'both')"
                        >
                            Rebaixar a acompanhante (manter ambos)
                        </UiButton>
                    </div>
                    <p class="px-5 pb-4 text-xs text-institutional-text/50">
                        <strong>Manter titular/representante:</strong> o outro é marcado como não participará.
                        <strong>Rebaixar a acompanhante:</strong> corrige provável erro de cadastro — ambos comparecem e o representante volta a ser acompanhante.
                    </p>
                </UiCard>
            </div>
        </PageContainer>
    </AuthenticatedLayout>
</template>
