<script setup>
import { reactive, ref, watch } from 'vue';
import { router, Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UiButton from '@/Components/ui/UiButton.vue';
import UiBadge from '@/Components/ui/UiBadge.vue';
import UiCard from '@/Components/ui/UiCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import AlertBanner from '@/Components/ui/AlertBanner.vue';

const props = defineProps({
    event: Object,
    guests: Object,
    filters: Object,
    categories: Array,
    options: Object,
    can: Object,
});

const showCreate = ref(false);
const createForm = useForm({
    full_name: '',
    guest_category_id: '',
    rsvp_status: 'pendente',
    note: '',
});

function submitCreate() {
    createForm.post(route('guests.store', props.event.id), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreate.value = false;
        },
    });
}

const form = reactive({
    search: props.filters.search ?? '',
    category: props.filters.category ?? '',
    role: props.filters.role ?? '',
    rsvp: props.filters.rsvp ?? '',
    attendance: props.filters.attendance ?? '',
});

function debounce(fn, delay) {
    let timer = null;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

const applyFilters = debounce(() => {
    router.get(route('guests.index', props.event.id), cleanParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

function cleanParams() {
    return Object.fromEntries(
        Object.entries(form).filter(([, v]) => v !== '' && v !== null),
    );
}

function exportUrl(name) {
    const base = route(name, props.event.id);
    const params = new URLSearchParams(cleanParams()).toString();
    return params ? `${base}?${params}` : base;
}

watch(form, () => applyFilters());

function act(name, guestId) {
    router.post(
        route(name, [props.event.id, guestId]),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

const rsvpVariant = {
    pendente: 'warning',
    confirmado: 'success',
    nao_participara: 'danger',
};
const attendanceVariant = {
    aguardando: 'neutral',
    presente: 'info',
};

const inputClass =
    'w-full rounded-xl border-institutional-900/15 text-sm shadow-sm transition-colors focus:border-institutional-500 focus:ring-institutional-500';
</script>

<template>
    <Head :title="`Convidados · ${event.name}`" />

    <AuthenticatedLayout>
        <template #banner v-if="event.status === 'encerrado'">
            <AlertBanner full variant="warning">
                <strong>Atenção:</strong> este evento foi <strong>ENCERRADO</strong>. Inclusões, confirmações e credenciamentos estão bloqueados (CA-14).
            </AlertBanner>
        </template>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-institutional-900">
                    {{ event.name }} — Convidados
                </h2>
                <div class="flex flex-wrap gap-2">
                    <UiButton v-if="can.export" external :href="exportUrl('guests.export.excel')" variant="secondary" size="md">Excel</UiButton>
                    <UiButton v-if="can.export" external :href="exportUrl('guests.export.pdf')" variant="secondary" size="md">PDF</UiButton>
                    <UiButton v-if="can.manageGuests" :href="route('guests.import.create', event.id)" variant="primary">Importar</UiButton>
                    <UiButton v-if="can.manageGuests" variant="success" @click="showCreate = !showCreate">Novo convidado</UiButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Formulário de cadastro manual (PRD §6-C) -->
                <UiCard v-if="showCreate && can.manageGuests" padding="p-6" class="mb-4">
                    <h3 class="mb-4 font-semibold text-institutional-900">Novo convidado</h3>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-institutional-text/60">Nome completo</label>
                            <input v-model="createForm.full_name" type="text" :class="inputClass" />
                            <p v-if="createForm.errors.full_name" class="mt-1 text-xs text-rose-600">{{ createForm.errors.full_name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-institutional-text/60">Categoria</label>
                            <select v-model="createForm.guest_category_id" :class="inputClass">
                                <option value="">Selecione…</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <p v-if="createForm.errors.guest_category_id" class="mt-1 text-xs text-rose-600">{{ createForm.errors.guest_category_id }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-institutional-text/60">Confirmação</label>
                            <select v-model="createForm.rsvp_status" :class="inputClass">
                                <option v-for="o in options.rsvp" :key="o.value" :value="o.value">{{ o.label }}</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <UiButton variant="success" class="w-full" :disabled="createForm.processing" @click="submitCreate">
                                Adicionar
                            </UiButton>
                        </div>
                    </div>
                </UiCard>

                <!-- Busca e filtros -->
                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <input
                        v-model="form.search"
                        type="search"
                        placeholder="Buscar por nome ou número"
                        aria-label="Buscar convidado"
                        :class="[inputClass, 'lg:col-span-2']"
                    />
                    <select v-model="form.category" aria-label="Filtrar por categoria" :class="inputClass">
                        <option value="">Todas as categorias</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <select v-model="form.rsvp" aria-label="Filtrar por confirmação" :class="inputClass">
                        <option value="">Qualquer confirmação</option>
                        <option v-for="o in options.rsvp" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                    <select v-model="form.attendance" aria-label="Filtrar por presença" :class="inputClass">
                        <option value="">Qualquer presença</option>
                        <option v-for="o in options.attendance" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                </div>

                <!-- Estado vazio -->
                <EmptyState v-if="guests.data.length === 0" message="Nenhum convidado encontrado com os filtros atuais." />

                <!-- Tabela (desktop) -->
                <div v-else class="hidden overflow-hidden rounded-2xl border border-institutional-900/8 bg-white shadow-sm md:block">
                    <table class="min-w-full divide-y divide-institutional-900/8">
                        <thead class="bg-institutional-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-institutional-text/50">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Nome</th>
                                <th class="px-4 py-3">Categoria</th>
                                <th class="px-4 py-3">Papel</th>
                                <th class="px-4 py-3">RSVP</th>
                                <th class="px-4 py-3">Presença</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-institutional-900/6">
                            <tr v-for="g in guests.data" :key="g.id" class="text-sm text-institutional-text transition-colors hover:bg-institutional-50/60">
                                <td class="px-4 py-3 font-mono text-institutional-text/40">{{ g.number }}</td>
                                <td class="px-4 py-3 font-medium text-institutional-900">{{ g.full_name }}</td>
                                <td class="px-4 py-3">{{ g.category }}</td>
                                <td class="px-4 py-3">{{ g.role_label }}</td>
                                <td class="px-4 py-3"><UiBadge :variant="rsvpVariant[g.rsvp_status]">{{ g.rsvp_label }}</UiBadge></td>
                                <td class="px-4 py-3"><UiBadge :variant="attendanceVariant[g.attendance_status]">{{ g.attendance_label }}</UiBadge></td>
                                <td class="px-4 py-3 text-right">
                                    <div v-if="can.operate" class="inline-flex gap-2">
                                        <UiButton v-if="g.rsvp_status !== 'confirmado'" size="sm" variant="success" @click="act('guests.confirm', g.id)">Confirmar</UiButton>
                                        <UiButton v-if="g.rsvp_status !== 'nao_participara'" size="sm" variant="danger" @click="act('guests.decline', g.id)">Recusar</UiButton>
                                        <UiButton v-if="g.attendance_status !== 'presente'" size="sm" variant="primary" @click="act('guests.check-in', g.id)">Check-in</UiButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cartões (mobile) -->
                <div v-if="guests.data.length" class="space-y-3 md:hidden">
                    <UiCard v-for="g in guests.data" :key="g.id" padding="p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-institutional-900">
                                    <span class="font-mono text-institutional-text/40">#{{ g.number }}</span>
                                    {{ g.full_name }}
                                </p>
                                <p class="text-sm text-institutional-text/60">{{ g.category }} · {{ g.role_label }}</p>
                            </div>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <UiBadge :variant="rsvpVariant[g.rsvp_status]">{{ g.rsvp_label }}</UiBadge>
                            <UiBadge :variant="attendanceVariant[g.attendance_status]">{{ g.attendance_label }}</UiBadge>
                        </div>
                        <div v-if="can.operate" class="mt-3 flex gap-2">
                            <UiButton v-if="g.rsvp_status !== 'confirmado'" variant="success" class="flex-1" @click="act('guests.confirm', g.id)">Confirmar</UiButton>
                            <UiButton v-if="g.attendance_status !== 'presente'" variant="primary" class="flex-1" @click="act('guests.check-in', g.id)">Check-in</UiButton>
                        </div>
                    </UiCard>
                </div>

                <!-- Paginação -->
                <div v-if="guests.links && guests.data.length" class="mt-4 flex flex-wrap gap-1">
                    <component
                        :is="link.url ? 'button' : 'span'"
                        v-for="(link, i) in guests.links"
                        :key="i"
                        @click="link.url && router.visit(link.url, { preserveState: true, preserveScroll: true })"
                        class="rounded-lg px-3 py-1 text-sm transition-colors"
                        :class="link.active ? 'bg-institutional-600 text-white' : 'bg-white text-institutional-text/70 hover:bg-institutional-50'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
