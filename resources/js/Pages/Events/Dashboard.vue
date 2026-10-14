<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import UiButton from '@/Components/ui/UiButton.vue';
import UiCard from '@/Components/ui/UiCard.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';
import PageContainer from '@/Components/ui/PageContainer.vue';

const props = defineProps({
    event: Object,
    metrics: Object,
    can: Object,
});

function action(name) {
    router.post(route(name, props.event.id), {}, { preserveScroll: true });
}

const statusLabel = computed(() => {
    if (props.event.status === 'ativo') return 'Em Andamento (Ativo)';
    if (props.event.status === 'encerrado') return 'Encerrado';
    return 'Rascunho';
});

const formattedDate = computed(() => {
    if (!props.event.starts_at) return null;
    try {
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        }).format(new Date(props.event.starts_at)).replace(',', ' às');
    } catch {
        return null;
    }
});

const cards = computed(() => [
    { label: 'Confirmados', value: props.metrics.confirmed_hosts, hint: 'Titulares diretos', accent: 'ink', dot: 'emerald' },
    { label: 'Acompanhantes', value: props.metrics.confirmed_companions, hint: 'Vínculos ativos', accent: 'base', dot: 'sky' },
    { label: 'Representantes', value: props.metrics.confirmed_representatives, hint: 'Substituições', accent: 'interaction', dot: 'violet' },
    { label: 'Não confirmados', value: props.metrics.not_confirmed, hint: 'Pendentes/Recusas', accent: 'ink', dot: 'amber' },
    { label: 'Presenças', value: props.metrics.present, hint: 'Portaria / Check-in', accent: 'base', dot: 'emerald', highlight: true },
]);

/** Avaliação do público real (presenças) frente ao estimado (planejamento). */
const audience = computed(() => {
    const diff = props.metrics.audience_diff ?? 0;
    if (diff > 0) {
        return { label: 'Acima do planejamento', tone: 'text-emerald-700', bg: 'bg-emerald-50', sign: '+', value: diff };
    }
    if (diff < 0) {
        return { label: 'Abaixo do planejamento', tone: 'text-amber-700', bg: 'bg-amber-50', sign: '', value: diff };
    }
    return { label: 'Conforme o planejamento', tone: 'text-institutional-600', bg: 'bg-institutional-50', sign: '', value: 0 };
});

function rate(value) {
    return value === null ? 'Indisponível' : `${value}%`;
}

const confirmationPct = computed(() => Math.min(100, props.metrics.confirmation_rate ?? 0));
const attendancePct = computed(() => Math.min(100, props.metrics.attendance_rate ?? 0));
</script>

<template>
    <Head :title="`Painel · ${event.name}`" />

    <AuthenticatedLayout>
        <template #banner v-if="event.status === 'encerrado'">
            <AlertBanner full variant="warning">
                <strong>Atenção:</strong> este evento foi <strong>ENCERRADO</strong>. Inclusões, confirmações e credenciamentos estão bloqueados (CA-14).
            </AlertBanner>
        </template>

        <PageContainer>
            <div class="space-y-6">
                <!-- Banner do evento (card institucional) -->
                <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-institutional-900 to-institutional-700 px-6 py-5 shadow-lg shadow-institutional-900/20">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <p class="flex items-center gap-2 text-xs font-medium text-white/60">
                                <span>Evento</span>
                                <span class="text-white/30">·</span>
                                <span class="font-semibold text-white/90">{{ statusLabel }}</span>
                            </p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ event.name }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-white/70">
                                <span v-if="formattedDate" class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4" /><path d="M16 2v4" /><rect width="18" height="18" x="3" y="4" rx="2" /><path d="M3 10h18" /></svg>
                                    {{ formattedDate }}
                                </span>
                                <span v-if="event.location" class="hidden text-white/20 sm:inline">|</span>
                                <span v-if="event.location" class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-white/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" /></svg>
                                    {{ event.location }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a v-if="can.operate" :href="route('guests.check-in-station', event.id)" class="hero-btn hero-btn--solid">Estação Portaria</a>
                            <a :href="route('guests.index', event.id)" class="hero-btn hero-btn--ghost">Lista de convidados</a>
                            <a v-if="can.administer" :href="route('events.audit', event.id)" class="hero-btn hero-btn--ghost">Auditoria</a>
                            <button
                                v-if="can.administer && event.status !== 'encerrado'"
                                type="button"
                                class="hero-btn hero-btn--ghost"
                                @click="action('events.close')"
                            >
                                Encerrar
                            </button>
                            <button
                                v-if="can.administer && event.status === 'encerrado'"
                                type="button"
                                class="hero-btn hero-btn--ghost"
                                @click="action('events.reopen')"
                            >
                                Reabrir
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Aviso de conflitos pendentes (CA-06) -->
                <AlertBanner
                    v-if="metrics.has_pending_conflicts"
                    variant="warning"
                    title="Conflito de representação pendente (CA-06)"
                >
                    Titular e representante confirmados simultaneamente. Os quantitativos definitivos
                    dependem da resolução explícita desta pendência.
                    <template #action v-if="can.operate">
                        <UiButton :href="route('conflicts.index', event.id)" variant="primary" size="sm" class="!bg-amber-600 hover:!bg-amber-700">
                            Resolver conflito agora
                        </UiButton>
                    </template>
                </AlertBanner>

                <!-- Indicadores -->
                <div>
                    <div class="mb-3 flex items-end justify-between">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-institutional-text/60">Indicadores de participação e presença</h3>
                        <p class="text-xs text-institutional-text/50">
                            Total na base: <strong class="text-institutional-700">{{ metrics.total_registered }}</strong> pessoas
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <StatCard
                            v-for="card in cards"
                            :key="card.label"
                            :label="card.label"
                            :value="card.value"
                            :hint="card.hint"
                            :accent="card.accent"
                            :dot="card.dot"
                            :highlight="card.highlight"
                        />

                        <!-- Público estimado × real, com diferencial de planejamento -->
                        <div class="stat-card group relative overflow-hidden rounded-2xl border border-institutional-900/8 bg-white p-5 shadow-sm">
                            <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-institutional-600 to-institutional-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium uppercase tracking-wide text-institutional-text/50">Público estimado</p>
                                <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                            </div>
                            <p class="mt-1.5 text-3xl font-bold text-institutional-900">{{ metrics.estimated_audience }}</p>

                            <p class="mt-2 text-xs font-medium uppercase tracking-wide text-institutional-text/50">Público real</p>
                            <p class="text-2xl font-bold text-emerald-700">{{ metrics.actual_audience }}</p>

                            <span
                                class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="[audience.bg, audience.tone]"
                                :title="`Diferença: ${audience.sign}${audience.value} em relação ao estimado`"
                            >
                                {{ audience.label }}
                                <span v-if="audience.value !== 0">({{ audience.sign }}{{ audience.value }})</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Painéis de progresso + regras -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <!-- Total previsto -->
                    <UiCard padding="p-5">
                        <div class="flex items-start justify-between">
                            <p class="text-xs font-medium uppercase tracking-wide text-institutional-text/60">Total previsto</p>
                            <p class="text-[11px] text-institutional-text/40">Titulares + Acomp. + Repr.</p>
                        </div>
                        <p class="mt-1 text-3xl font-bold text-institutional-900">
                            {{ metrics.expected_participants }}
                            <span class="text-base font-normal text-institutional-text/40">/ {{ metrics.estimated_audience }} estimados</span>
                        </p>
                        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-institutional-100">
                            <div class="h-full rounded-full bg-institutional-600 transition-all" :style="{ width: confirmationPct + '%' }"></div>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span class="text-institutional-text/60">Taxa de confirmação:</span>
                            <strong class="text-institutional-900">{{ rate(metrics.confirmation_rate) }}</strong>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs">
                            <span class="text-institutional-text/60">Saldo para capacidade:</span>
                            <strong class="text-institutional-700">{{ metrics.capacity_balance }} vagas disponíveis</strong>
                        </div>
                    </UiCard>

                    <!-- Comparecimento real -->
                    <UiCard padding="p-5">
                        <div class="flex items-start justify-between">
                            <p class="text-xs font-medium uppercase tracking-wide text-institutional-text/60">Comparecimento real</p>
                            <p class="text-[11px] text-institutional-text/40">Credenciados / Previstos</p>
                        </div>
                        <p class="mt-1 text-3xl font-bold text-emerald-700">
                            {{ metrics.present }}
                            <span class="text-base font-normal text-institutional-text/40">/ {{ metrics.expected_participants }} previstos</span>
                        </p>
                        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-emerald-100">
                            <div class="h-full rounded-full bg-emerald-600 transition-all" :style="{ width: attendancePct + '%' }"></div>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span class="text-institutional-text/60">Taxa de comparecimento:</span>
                            <strong class="text-institutional-900">{{ rate(metrics.attendance_rate) }}</strong>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs">
                            <span class="text-institutional-text/60">Aguardando chegada:</span>
                            <strong class="text-amber-700">{{ metrics.awaiting_arrival }} pessoas</strong>
                        </div>
                    </UiCard>

                    <!-- Regras do cerimonial -->
                    <UiCard padding="p-5">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-institutional-900">
                            <svg class="h-5 w-5 text-institutional-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" /></svg>
                            Regras do cerimonial (PRD)
                        </h3>
                        <ul class="mt-3 space-y-2 text-xs text-institutional-text/70">
                            <li><strong class="text-institutional-900">Zero dupla contagem:</strong> representantes e acompanhantes são contados uma única vez (CA-07).</li>
                            <li><strong class="text-institutional-900">Promoção automática:</strong> acompanhante que confirma sem o titular vira representante (CA-05).</li>
                            <li><strong class="text-institutional-900">Auditoria contínua:</strong> toda alteração registra operador e carimbo temporal (CA-10).</li>
                        </ul>
                        <UiButton :href="route('guests.index', event.id)" variant="ghost" size="sm" class="mt-3 !px-0">
                            Ir para lista completa de convidados →
                        </UiButton>
                    </UiCard>
                </div>
            </div>
        </PageContainer>
    </AuthenticatedLayout>
</template>

<style scoped>
.hero-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}
.hero-btn--solid {
    background-color: #fff;
    color: var(--color-institutional-900, #0b2a4a);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
}
.hero-btn--solid:hover {
    background-color: #eef4fa;
    transform: translateY(-1px);
}
.hero-btn--ghost {
    border: 1px solid rgba(255, 255, 255, 0.35);
    color: #fff;
}
.hero-btn--ghost:hover {
    background-color: rgba(255, 255, 255, 0.12);
    transform: translateY(-1px);
}
@media (prefers-reduced-motion: reduce) {
    .hero-btn:hover { transform: none; }
}
</style>
