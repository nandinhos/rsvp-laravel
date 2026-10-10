<script setup>
import { reactive, watch } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    event: Object,
    guests: Object,
    filters: Object,
    categories: Array,
    options: Object,
    can: Object,
});

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

const rsvpBadge = {
    pendente: 'bg-amber-100 text-amber-800',
    confirmado: 'bg-emerald-100 text-emerald-800',
    nao_participara: 'bg-rose-100 text-rose-800',
};
const attendanceBadge = {
    aguardando: 'bg-slate-100 text-slate-700',
    presente: 'bg-sky-100 text-sky-800',
};
</script>

<template>
    <Head :title="`Convidados · ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ event.name }} — Convidados
                </h2>
                <div class="flex gap-2">
                    <a
                        v-if="can.export"
                        :href="exportUrl('guests.export.excel')"
                        class="rounded-md border border-sky-700 px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50"
                    >Excel</a>
                    <a
                        v-if="can.export"
                        :href="exportUrl('guests.export.pdf')"
                        class="rounded-md border border-sky-700 px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50"
                    >PDF</a>
                    <Link
                        v-if="can.manageGuests"
                        :href="route('guests.import.create', event.id)"
                        class="rounded-md bg-sky-700 px-3 py-2 text-sm font-medium text-white hover:bg-sky-800"
                    >
                        Importar
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Busca e filtros -->
                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <input
                        v-model="form.search"
                        type="search"
                        placeholder="Buscar por nome ou número"
                        aria-label="Buscar convidado"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 lg:col-span-2"
                    />
                    <select v-model="form.category" aria-label="Filtrar por categoria" class="rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">Todas as categorias</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <select v-model="form.rsvp" aria-label="Filtrar por confirmação" class="rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">Qualquer confirmação</option>
                        <option v-for="o in options.rsvp" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                    <select v-model="form.attendance" aria-label="Filtrar por presença" class="rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">Qualquer presença</option>
                        <option v-for="o in options.attendance" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                </div>

                <!-- Estado vazio -->
                <div
                    v-if="guests.data.length === 0"
                    class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500"
                >
                    Nenhum convidado encontrado com os filtros atuais.
                </div>

                <!-- Tabela (desktop) -->
                <div v-else class="hidden overflow-hidden rounded-lg bg-white shadow md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="px-4 py-3">#</th>
                                <th class="px-4 py-3">Nome</th>
                                <th class="px-4 py-3">Categoria</th>
                                <th class="px-4 py-3">Papel</th>
                                <th class="px-4 py-3">RSVP</th>
                                <th class="px-4 py-3">Presença</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="g in guests.data" :key="g.id" class="text-sm text-gray-700">
                                <td class="px-4 py-3 font-mono text-gray-500">{{ g.number }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ g.full_name }}</td>
                                <td class="px-4 py-3">{{ g.category }}</td>
                                <td class="px-4 py-3">{{ g.role_label }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="rsvpBadge[g.rsvp_status]">
                                        {{ g.rsvp_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="attendanceBadge[g.attendance_status]">
                                        {{ g.attendance_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div v-if="can.operate" class="inline-flex gap-2">
                                        <button
                                            v-if="g.rsvp_status !== 'confirmado'"
                                            @click="act('guests.confirm', g.id)"
                                            class="rounded bg-emerald-600 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-700"
                                        >Confirmar</button>
                                        <button
                                            v-if="g.rsvp_status !== 'nao_participara'"
                                            @click="act('guests.decline', g.id)"
                                            class="rounded bg-rose-600 px-2 py-1 text-xs font-medium text-white hover:bg-rose-700"
                                        >Recusar</button>
                                        <button
                                            v-if="g.attendance_status !== 'presente'"
                                            @click="act('guests.check-in', g.id)"
                                            class="rounded bg-sky-700 px-2 py-1 text-xs font-medium text-white hover:bg-sky-800"
                                        >Check-in</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Cartões (mobile) -->
                <div v-if="guests.data.length" class="space-y-3 md:hidden">
                    <div v-for="g in guests.data" :key="g.id" class="rounded-lg bg-white p-4 shadow">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-gray-900">
                                    <span class="font-mono text-gray-400">#{{ g.number }}</span>
                                    {{ g.full_name }}
                                </p>
                                <p class="text-sm text-gray-500">{{ g.category }} · {{ g.role_label }}</p>
                            </div>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="rsvpBadge[g.rsvp_status]">{{ g.rsvp_label }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="attendanceBadge[g.attendance_status]">{{ g.attendance_label }}</span>
                        </div>
                        <div v-if="can.operate" class="mt-3 flex gap-2">
                            <button v-if="g.rsvp_status !== 'confirmado'" @click="act('guests.confirm', g.id)" class="flex-1 rounded bg-emerald-600 py-2 text-sm font-medium text-white">Confirmar</button>
                            <button v-if="g.attendance_status !== 'presente'" @click="act('guests.check-in', g.id)" class="flex-1 rounded bg-sky-700 py-2 text-sm font-medium text-white">Check-in</button>
                        </div>
                    </div>
                </div>

                <!-- Paginação -->
                <div v-if="guests.links && guests.data.length" class="mt-4 flex flex-wrap gap-1">
                    <component
                        :is="link.url ? 'button' : 'span'"
                        v-for="(link, i) in guests.links"
                        :key="i"
                        @click="link.url && router.visit(link.url, { preserveState: true, preserveScroll: true })"
                        class="rounded px-3 py-1 text-sm"
                        :class="link.active ? 'bg-sky-700 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
