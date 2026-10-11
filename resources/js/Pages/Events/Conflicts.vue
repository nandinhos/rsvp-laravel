<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHero from '@/Components/ui/PageHero.vue';
import PageContainer from '@/Components/ui/PageContainer.vue';
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

                <!-- Resumo de volume: cada conflito é tratado individualmente -->
                <div v-if="conflicts.length" class="flex items-center gap-2 text-sm text-institutional-text/70">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                        {{ conflicts.length }} conflito(s) pendente(s)
                    </span>
                    <span>Resolva cada caso individualmente — a decisão de um par não afeta os demais.</span>
                </div>

                <UiCard v-for="(c, i) in conflicts" :key="c.id" padding="p-0">
                    <!-- Cabeçalho do conflito (numerado) -->
                    <div class="flex items-center justify-between gap-2 border-b border-institutional-900/8 px-5 py-3">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                            <p class="text-sm font-semibold text-institutional-900">Confirmação mútua detectada</p>
                        </div>
                        <span class="text-xs font-medium text-institutional-text/50">Conflito {{ i + 1 }} de {{ conflicts.length }}</span>
                    </div>

                    <!-- Tabela: posição atual × conflitante -->
                    <div class="overflow-x-auto">
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

                    <!-- Ações individualizadas: cada botão explicita o efeito sobre cada pessoa -->
                    <div class="grid grid-cols-1 gap-2 border-t border-institutional-900/8 px-5 py-4 sm:grid-cols-3">
                        <button
                            type="button"
                            class="conflict-action group rounded-xl border border-institutional-200 bg-white p-3 text-left"
                            @click="resolve(c.id, 'host')"
                        >
                            <span class="block text-xs font-semibold uppercase tracking-wide text-institutional-600">Manter titular</span>
                            <span class="mt-0.5 block text-sm font-medium text-institutional-900">#{{ c.host.number }} {{ c.host.name }}</span>
                            <span class="mt-1 block text-[11px] text-institutional-text/50">{{ c.representative.name }} será marcado como não participará</span>
                        </button>

                        <button
                            type="button"
                            class="conflict-action group rounded-xl border border-institutional-200 bg-white p-3 text-left"
                            @click="resolve(c.id, 'representative')"
                        >
                            <span class="block text-xs font-semibold uppercase tracking-wide text-institutional-600">Manter representante</span>
                            <span class="mt-0.5 block text-sm font-medium text-institutional-900">#{{ c.representative.number }} {{ c.representative.name }}</span>
                            <span class="mt-1 block text-[11px] text-institutional-text/50">{{ c.host.name }} será marcado como não participará</span>
                        </button>

                        <button
                            type="button"
                            class="conflict-action conflict-action--both group rounded-xl border border-emerald-200 bg-emerald-50/50 p-3 text-left"
                            @click="resolve(c.id, 'both')"
                        >
                            <span class="block text-xs font-semibold uppercase tracking-wide text-emerald-700">Manter ambos</span>
                            <span class="mt-0.5 block text-sm font-medium text-institutional-900">Rebaixar a acompanhante</span>
                            <span class="mt-1 block text-[11px] text-institutional-text/50">{{ c.representative.name }} volta a acompanhante; os dois comparecem</span>
                        </button>
                    </div>
                </UiCard>
            </div>
        </PageContainer>
    </AuthenticatedLayout>
</template>

<style scoped>
.conflict-action {
    transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}
.conflict-action:hover {
    border-color: var(--color-institutional-400, #5e97c4);
    transform: translateY(-2px);
    box-shadow: 0 10px 24px -14px rgba(11, 42, 74, 0.4);
}
.conflict-action--both:hover {
    border-color: #34d399;
}
@media (prefers-reduced-motion: reduce) {
    .conflict-action { transition: none; }
    .conflict-action:hover { transform: none; }
}
</style>
