<script setup>
import { reactive, watch } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHero from '@/Components/ui/PageHero.vue';
import PageContainer from '@/Components/ui/PageContainer.vue';
import UiCard from '@/Components/ui/UiCard.vue';
import UiButton from '@/Components/ui/UiButton.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';

const props = defineProps({
    event: Object,
    guests: Array,
    filters: Object,
    categories: Array,
    can: Object,
});

const form = reactive({
    search: props.filters.search ?? '',
    category: props.filters.category ?? '',
});

function debounce(fn, delay) {
    let t = null;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), delay); };
}

const apply = debounce(() => {
    const params = {};
    if (form.search) params.search = form.search;
    if (form.category) params.category = form.category;
    router.get(route('guests.work-queue', props.event.id), params, { preserveState: true, preserveScroll: true, replace: true });
}, 300);

watch(form, () => apply());

const stageBadge = {
    nao_contatado: 'bg-slate-100 text-slate-600',
    sem_resposta: 'bg-amber-100 text-amber-800',
    retorno_agendado: 'bg-institutional-100 text-institutional-700',
    retorno_vencido: 'bg-rose-100 text-rose-800',
    respondido: 'bg-emerald-100 text-emerald-800',
    reconfirmado: 'bg-emerald-100 text-emerald-800',
};

const inputClass =
    'w-full rounded-xl border-institutional-900/15 text-sm shadow-sm transition-colors focus:border-institutional-500 focus:ring-institutional-500';

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head :title="`Fila de trabalho · ${event.name}`" />

    <AuthenticatedLayout>
        <PageHero :title="`${event.name} — Fila de trabalho`" subtitle="Priorizada pela ação de confirmação: retornos vencidos e não contatados primeiro.">
            <template #actions>
                <UiButton :href="route('guests.index', event.id)" variant="onLightGhost" size="md">Lista completa</UiButton>
            </template>
        </PageHero>

        <PageContainer>
            <!-- Filtros -->
            <div class="mb-4 flex flex-col gap-3 sm:flex-row">
                <input v-model="form.search" type="text" placeholder="Buscar por nome ou número…" :class="inputClass" />
                <select v-model="form.category" :class="inputClass">
                    <option value="">Todas as categorias</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>

            <EmptyState v-if="!guests.length" title="Fila vazia" message="Nenhum convidado pendente de ação com os filtros atuais." />

            <UiCard v-else padding="p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-institutional-900/8 text-sm">
                        <thead class="bg-institutional-50 text-left text-xs font-semibold uppercase tracking-wide text-institutional-text/50">
                            <tr>
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Nome</th>
                                <th class="px-4 py-3">Categoria</th>
                                <th class="px-4 py-3">Estágio</th>
                                <th class="px-4 py-3">Retorno</th>
                                <th class="px-4 py-3">Último contato</th>
                                <th class="px-4 py-3 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-institutional-900/6">
                            <tr v-for="g in guests" :key="g.id" class="transition-colors hover:bg-institutional-50/60">
                                <td class="px-4 py-3 font-mono text-institutional-text/40">{{ g.number }}</td>
                                <td class="px-4 py-3 font-medium text-institutional-900">{{ g.full_name }}</td>
                                <td class="px-4 py-3 text-institutional-text/70">{{ g.category }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="stageBadge[g.contact_stage]">{{ g.contact_stage_label }}</span>
                                </td>
                                <td class="px-4 py-3 text-institutional-text/60">{{ formatDate(g.scheduled_return_at) }}</td>
                                <td class="px-4 py-3 text-institutional-text/60">{{ formatDate(g.last_contact_at) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <UiButton :href="route('guests.index', [event.id, { search: g.number }])" variant="ghost" size="sm">Abrir</UiButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </UiCard>
        </PageContainer>
    </AuthenticatedLayout>
</template>
