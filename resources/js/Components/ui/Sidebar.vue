<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SidebarLink from '@/Components/ui/SidebarLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const authorizedEvents = computed(() => page.props.authorizedEvents ?? []);
const activeEvent = computed(() => page.props.activeEvent ?? null);

function switchEvent(eventId) {
    router.visit(route('events.dashboard', eventId));
}

function current(name) {
    return route().current(name);
}

const initials = computed(() => (user.value?.name ?? '?').charAt(0).toUpperCase());

const eventNav = computed(() => {
    const e = activeEvent.value;
    if (!e) return [];
    return [
        { label: 'Painel & Métricas', icon: 'dashboard', href: route('events.dashboard', e.id), active: current('events.dashboard') },
        { label: 'Convidados', icon: 'users', href: route('guests.index', e.id), active: current('guests.index') },
        { label: 'Fila de trabalho', icon: 'queue', href: route('guests.work-queue', e.id), active: current('guests.work-queue') },
        { label: 'Credenciamento', icon: 'checkin', href: route('guests.check-in-station', e.id), active: current('guests.check-in-station') },
        { label: 'Importação', icon: 'import', href: route('guests.import.create', e.id), active: current('guests.import.create') },
        { label: 'Conflitos', icon: 'conflict', href: route('conflicts.index', e.id), active: current('conflicts.index') },
        { label: 'Auditoria', icon: 'audit', href: route('events.audit', e.id), active: current('events.audit') },
    ];
});
</script>

<template>
    <aside class="flex h-full w-64 shrink-0 flex-col border-r border-institutional-900/8 bg-institutional-50/60">
        <!-- Logo / marca -->
        <div class="flex h-16 items-center gap-2.5 px-5">
            <Link :href="route('events.index')" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-institutional-700 to-institutional-600 text-white shadow-md shadow-institutional-900/25">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" /></svg>
                </span>
                <span class="text-lg font-bold tracking-tight text-institutional-900">RSVP</span>
            </Link>
        </div>

        <!-- Navegação -->
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2" aria-label="Navegação principal">
            <SidebarLink
                :href="route('events.index')"
                label="Eventos"
                icon="calendar"
                :active="current('events.index')"
            />

            <!-- Itens do evento ativo -->
            <template v-if="activeEvent">
                <p class="truncate px-3.5 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-institutional-text/40" :title="activeEvent.name">
                    {{ activeEvent.name }}
                </p>
                <SidebarLink
                    v-for="item in eventNav"
                    :key="item.label"
                    :href="item.href"
                    :label="item.label"
                    :icon="item.icon"
                    :active="item.active"
                />
            </template>
        </nav>

        <!-- Seletor de evento -->
        <div v-if="authorizedEvents.length" class="px-3 pb-2">
            <Dropdown align="left" width="60">
                <template #trigger>
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-xl border border-institutional-900/10 bg-white px-3.5 py-2.5 text-sm font-medium text-institutional-text shadow-sm transition hover:-translate-y-px hover:shadow-md"
                        title="Trocar evento ativo"
                    >
                        <svg class="h-4 w-4 shrink-0 text-institutional-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4" /><path d="M16 2v4" /><rect width="18" height="18" x="3" y="4" rx="2" /><path d="M3 10h18" /></svg>
                        <span class="min-w-0 flex-1 truncate text-left">Trocar evento</span>
                        <svg class="h-4 w-4 shrink-0 text-institutional-text/50" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </template>
                <template #content>
                    <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-institutional-text/40">
                        Eventos autorizados ({{ authorizedEvents.length }})
                    </div>
                    <button
                        v-for="evt in authorizedEvents"
                        :key="evt.id"
                        type="button"
                        class="flex w-full items-start gap-2 px-4 py-2 text-left text-sm text-institutional-text transition hover:bg-institutional-50"
                        @click="switchEvent(evt.id)"
                    >
                        <span class="min-w-0 flex-1 truncate font-medium">{{ evt.name }}</span>
                        <span
                            class="shrink-0 text-[10px] font-semibold uppercase"
                            :class="evt.status === 'ativo' ? 'text-emerald-600' : evt.status === 'encerrado' ? 'text-institutional-text/40' : 'text-amber-600'"
                        >{{ evt.status }}</span>
                    </button>
                </template>
            </Dropdown>
        </div>

        <!-- Rodapé: usuário -->
        <div class="border-t border-institutional-900/8 p-3">
            <Dropdown align="left" width="48">
                <template #trigger>
                    <button
                        type="button"
                        class="flex w-full items-center gap-2.5 rounded-xl bg-white px-3 py-2.5 text-left shadow-sm transition hover:-translate-y-px hover:shadow-md"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-institutional-600 text-xs font-bold text-white">{{ initials }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-institutional-900">{{ user.name }}</span>
                            <span class="block truncate text-[11px] text-institutional-text/50">{{ user.email }}</span>
                        </span>
                    </button>
                </template>
                <template #content>
                    <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">Sair</DropdownLink>
                </template>
            </Dropdown>
        </div>
    </aside>
</template>
