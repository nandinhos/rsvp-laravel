<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHero from '@/Components/ui/PageHero.vue';
import PageContainer from '@/Components/ui/PageContainer.vue';
import UiCard from '@/Components/ui/UiCard.vue';
import UiBadge from '@/Components/ui/UiBadge.vue';
import UiButton from '@/Components/ui/UiButton.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';

const props = defineProps({
    event: Object,
    conflicts: Array,
});

const pending = ref(null);

/** Monta a confirmação com efeito estruturado (quem fica / quem muda). */
function ask(conflict, keep) {
    const host = { label: 'Titular', text: `#${conflict.host.number} ${conflict.host.name}` };
    const rep = { label: 'Representante', text: `#${conflict.representative.number} ${conflict.representative.name}` };

    const specs = {
        host: {
            title: 'Manter o titular?',
            confirmLabel: 'Manter titular',
            confirmVariant: 'primary',
            effects: [
                { person: host, outcome: 'Permanece confirmado', tone: 'keep' },
                { person: rep, outcome: 'Marcado como “não participará”', tone: 'drop' },
            ],
        },
        representative: {
            title: 'Manter o representante?',
            confirmLabel: 'Manter representante',
            confirmVariant: 'primary',
            effects: [
                { person: rep, outcome: 'Permanece confirmado', tone: 'keep' },
                { person: host, outcome: 'Marcado como “não participará”', tone: 'drop' },
            ],
        },
        both: {
            title: 'Manter ambos?',
            confirmLabel: 'Manter ambos',
            confirmVariant: 'success',
            effects: [
                { person: host, outcome: 'Permanece confirmado (titular)', tone: 'keep' },
                { person: rep, outcome: 'Rebaixado a acompanhante — também comparece', tone: 'demote' },
            ],
        },
    };

    pending.value = { conflictId: conflict.id, keep, ...specs[keep] };
}

const toneBadge = {
    keep: 'bg-emerald-100 text-emerald-800',
    drop: 'bg-rose-100 text-rose-800',
    demote: 'bg-amber-100 text-amber-800',
};

function confirmResolve() {
    if (!pending.value) return;
    const { conflictId, keep } = pending.value;
    router.post(
        route('conflicts.resolve', [props.event.id, conflictId]),
        { keep },
        { preserveScroll: true, onFinish: () => { pending.value = null; } },
    );
}
</script>

<template>
    <Head :title="`Conflitos · ${event.name}`" />

    <AuthenticatedLayout>
        <PageHero
            title="Conflitos de representação"
            :subtitle="`${event.name} — titular e representante confirmados ao mesmo tempo. Cada caso é resolvido individualmente, de forma explícita e auditada.`"
        />

        <PageContainer>
            <EmptyState v-if="conflicts.length === 0" title="Nenhum conflito pendente" message="Todos os vínculos estão consistentes." />

            <template v-else>
                <!-- Resumo de volume -->
                <div class="mb-4 flex items-center gap-2 text-sm text-institutional-text/70">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                        {{ conflicts.length }} conflito(s) pendente(s)
                    </span>
                    <span>Trate cada caso na coluna <strong>Ação</strong> — resolver um não afeta os demais.</span>
                </div>

                <UiCard padding="p-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-institutional-900/8 text-sm">
                            <thead class="bg-institutional-50 text-left text-xs font-semibold uppercase tracking-wide text-institutional-text/50">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">Titular</th>
                                    <th class="px-4 py-3">Relação</th>
                                    <th class="px-4 py-3">Representante</th>
                                    <th class="px-4 py-3 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-institutional-900/6">
                                <tr v-for="(c, i) in conflicts" :key="c.id" class="align-top transition-colors hover:bg-amber-50/30">
                                    <td class="px-4 py-4 font-mono text-institutional-text/40">{{ i + 1 }}</td>

                                    <!-- Titular -->
                                    <td class="px-4 py-4">
                                        <p class="font-medium text-institutional-900">
                                            <span class="font-mono text-institutional-text/40">#{{ c.host.number }}</span>
                                            {{ c.host.name }}
                                        </p>
                                        <p class="text-xs text-institutional-text/50">{{ c.host.category }}</p>
                                        <UiBadge variant="success" class="mt-1">Confirmado</UiBadge>
                                    </td>

                                    <!-- Relação entre os convidados -->
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-institutional-100 px-2.5 py-1 text-xs font-medium text-institutional-700">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>
                                            representado por
                                        </span>
                                    </td>

                                    <!-- Representante -->
                                    <td class="px-4 py-4">
                                        <p class="font-medium text-institutional-900">
                                            <span class="font-mono text-institutional-text/40">#{{ c.representative.number }}</span>
                                            {{ c.representative.name }}
                                        </p>
                                        <p class="text-xs text-institutional-text/50">{{ c.representative.category }}</p>
                                        <UiBadge variant="warning" class="mt-1">Representante · Confirmado</UiBadge>
                                    </td>

                                    <!-- Coluna Ação: botões específicos do caso -->
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col items-end gap-1.5">
                                            <UiButton size="sm" variant="primary" class="w-full justify-center sm:w-auto" @click="ask(c, 'host')" :title="`${c.representative.name} será marcado como não participará`">
                                                Manter titular
                                            </UiButton>
                                            <UiButton size="sm" variant="secondary" class="w-full justify-center sm:w-auto" @click="ask(c, 'representative')" :title="`${c.host.name} será marcado como não participará`">
                                                Manter representante
                                            </UiButton>
                                            <UiButton size="sm" variant="success" class="w-full justify-center sm:w-auto" @click="ask(c, 'both')" :title="`${c.representative.name} volta a acompanhante; ambos comparecem`">
                                                Manter ambos
                                            </UiButton>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </UiCard>

                <p class="mt-3 text-xs text-institutional-text/50">
                    <strong>Manter titular / representante:</strong> o outro é marcado como não participará.
                    <strong>Manter ambos:</strong> rebaixa o representante a acompanhante — corrige provável erro de cadastro e os dois comparecem.
                </p>
            </template>
        </PageContainer>

        <ConfirmDialog
            :show="pending !== null"
            :title="pending?.title ?? ''"
            :confirm-label="pending?.confirmLabel"
            :confirm-variant="pending?.confirmVariant"
            @confirm="confirmResolve"
            @close="pending = null"
        >
            <ul v-if="pending" class="mt-3 space-y-2">
                <li
                    v-for="(e, idx) in pending.effects"
                    :key="idx"
                    class="flex items-center justify-between gap-3 rounded-lg border border-institutional-900/8 bg-institutional-50/50 px-3 py-2"
                >
                    <span class="min-w-0">
                        <span class="block text-[10px] font-semibold uppercase tracking-wide text-institutional-text/40">{{ e.person.label }}</span>
                        <span class="block truncate text-sm font-medium text-institutional-900">{{ e.person.text }}</span>
                    </span>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="toneBadge[e.tone]">
                        {{ e.outcome }}
                    </span>
                </li>
            </ul>
            <p class="mt-3 text-xs text-institutional-text/50">
                Ninguém é excluído — a decisão é auditada e pode ser revertida depois.
            </p>
        </ConfirmDialog>
    </AuthenticatedLayout>
</template>
