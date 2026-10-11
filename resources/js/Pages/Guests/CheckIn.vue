<script setup>
import { reactive, ref, watch } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UiButton from '@/Components/ui/UiButton.vue';
import UiBadge from '@/Components/ui/UiBadge.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    event: Object,
    eligible: Array,
    pending: Array,
    search: String,
    counters: Object,
    can: Object,
});

const toast = useToast();
const form = reactive({ search: props.search ?? '' });
const lastCheckedId = ref(null);

function debounce(fn, delay) {
    let timer = null;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

const applySearch = debounce(() => {
    router.get(
        route('guests.check-in-station', props.event.id),
        form.search ? { search: form.search } : {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch(() => form.search, () => applySearch());

const attendanceVariant = { aguardando: 'neutral', presente: 'info' };
const rsvpVariant = { pendente: 'warning', confirmado: 'success', nao_participara: 'danger' };

function post(name, guestId, opts = {}) {
    router.post(route(name, [props.event.id, guestId]), {}, {
        preserveScroll: true,
        preserveState: false,
        ...opts,
    });
}

function checkIn(guest) {
    post('guests.check-in', guest.id, {
        onSuccess: () => {
            lastCheckedId.value = guest.id;
            toast.success(
                `Credenciado: #${guest.number} ${guest.full_name}`,
                'Presença registrada.',
                { undoLabel: 'Desfazer check-in', onUndo: () => post('guests.revert-check-in', guest.id) },
            );
        },
    });
}

/** Confirma o RSVP de um pendente, habilitando-o para o credenciamento. */
function confirmGuest(guest) {
    post('guests.confirm', guest.id, {
        onSuccess: () => {
            toast.success(
                `Confirmado: #${guest.number} ${guest.full_name}`,
                'Convidado habilitado para credenciamento.',
                { undoLabel: 'Desfazer confirmação', onUndo: () => post('guests.undo-rsvp', guest.id) },
            );
        },
    });
}

function undoLastCheckIn() {
    if (!lastCheckedId.value) return;
    post('guests.revert-check-in', lastCheckedId.value);
    lastCheckedId.value = null;
}
</script>

<template>
    <Head :title="`Credenciamento · ${event.name}`" />

    <AuthenticatedLayout>
        <template #banner v-if="event.status === 'encerrado'">
            <AlertBanner full variant="warning">
                <strong>Atenção:</strong> este evento foi <strong>ENCERRADO</strong>. Credenciamentos estão bloqueados (CA-14).
            </AlertBanner>
        </template>

        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-institutional-900">
                    {{ event.name }} — Credenciamento (Portaria)
                </h2>
                <UiButton :href="route('guests.index', event.id)" variant="neutral" size="md">Lista completa</UiButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- Contadores ao vivo -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <StatCard label="Presentes" :value="counters.present" accent="base" />
                    <StatCard label="Aguardando" :value="counters.awaiting" hint="Confirmados a credenciar" accent="ink" />
                    <StatCard label="Confirmados" :value="counters.confirmed" accent="interaction" />
                    <StatCard label="Pendentes" :value="counters.pending" hint="Precisam confirmar" accent="ink" />
                </div>

                <!-- Busca unificada + estornar último -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <input
                            v-model="form.search"
                            type="text"
                            inputmode="search"
                            autofocus
                            placeholder="Buscar por nome ou número…"
                            class="w-full rounded-xl border-institutional-900/15 py-3 pl-11 text-base shadow-sm transition-colors focus:border-institutional-500 focus:ring-institutional-500"
                        />
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-institutional-text/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" />
                        </svg>
                    </div>
                    <UiButton
                        v-if="can.operate && lastCheckedId"
                        variant="secondary"
                        size="md"
                        class="!py-3"
                        @click="undoLastCheckIn"
                    >
                        Estornar último
                    </UiButton>
                </div>

                <!-- SEÇÃO 1: Aptos para credenciamento (confirmados) -->
                <section>
                    <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-institutional-700">
                        Aptos para credenciamento
                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">{{ eligible.length }}</span>
                    </h3>

                    <EmptyState v-if="!eligible.length" title="Nenhum convidado apto" message="Confirme convidados na seção abaixo para habilitá-los ao credenciamento." />

                    <ul v-else class="space-y-2">
                        <li
                            v-for="g in eligible"
                            :key="g.id"
                            class="flex items-center justify-between gap-3 rounded-2xl border border-institutional-900/8 bg-white p-4 shadow-sm"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium text-institutional-900">
                                    <span class="font-mono text-institutional-text/40">#{{ g.number }}</span>
                                    {{ g.full_name }}
                                </p>
                                <p class="truncate text-sm text-institutional-text/60">{{ g.category }} · {{ g.role_label }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    <UiBadge :variant="rsvpVariant[g.rsvp_status]">{{ g.rsvp_label }}</UiBadge>
                                    <UiBadge :variant="attendanceVariant[g.attendance_status]">{{ g.attendance_label }}</UiBadge>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <UiButton
                                    v-if="can.operate && g.attendance_status !== 'presente'"
                                    variant="primary"
                                    size="md"
                                    class="!px-6 !py-3 text-base"
                                    @click="checkIn(g)"
                                >
                                    Credenciar
                                </UiButton>
                                <span
                                    v-else-if="g.attendance_status === 'presente'"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 6 9 17l-5-5" />
                                    </svg>
                                    Presente
                                </span>
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- SEÇÃO 2: Pendentes (precisam confirmar antes do check-in) -->
                <section v-if="pending.length">
                    <AlertBanner variant="info" title="Convidados pendentes de confirmação">
                        Só é possível credenciar quem está confirmado. Confirme abaixo para habilitar o convidado na lista oficial de check-in.
                    </AlertBanner>

                    <h3 class="mb-2 mt-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-amber-700">
                        Pendentes de confirmação
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">{{ pending.length }}</span>
                    </h3>

                    <ul class="space-y-2">
                        <li
                            v-for="g in pending"
                            :key="g.id"
                            class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50/40 p-4 shadow-sm"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium text-institutional-900">
                                    <span class="font-mono text-institutional-text/40">#{{ g.number }}</span>
                                    {{ g.full_name }}
                                </p>
                                <p class="truncate text-sm text-institutional-text/60">{{ g.category }} · {{ g.role_label }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    <UiBadge :variant="rsvpVariant[g.rsvp_status]">{{ g.rsvp_label }}</UiBadge>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <UiButton
                                    v-if="can.operate"
                                    variant="success"
                                    size="md"
                                    class="!px-6 !py-3 text-base"
                                    @click="confirmGuest(g)"
                                >
                                    Confirmar
                                </UiButton>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
