<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    events: Array,
});

const statusBadge = {
    rascunho: 'bg-slate-100 text-slate-700',
    ativo: 'bg-emerald-100 text-emerald-800',
    encerrado: 'bg-gray-200 text-gray-600',
};

function formatDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Meus eventos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Meus eventos</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="events.length === 0"
                    class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500"
                >
                    Você ainda não tem acesso a nenhum evento.
                </div>

                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="event in events"
                        :key="event.id"
                        :href="route('events.dashboard', event.id)"
                        class="event-card group block rounded-2xl border border-institutional-deep/8 bg-white p-5 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-institutional-interaction"
                    >
                        <div class="flex items-start justify-between">
                            <h3 class="font-semibold text-institutional-deep transition-colors group-hover:text-institutional-base">{{ event.name }}</h3>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge[event.status]">
                                {{ event.status_label }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-institutional-text/60">{{ formatDate(event.starts_at) }}</p>
                        <p class="text-sm text-institutional-text/60">{{ event.location }}</p>
                        <p class="mt-3 text-xs font-medium uppercase tracking-wide text-institutional-interaction">{{ event.role_label }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.event-card {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease, border-color 0.3s ease;
}
.event-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 36px -18px rgba(11, 42, 74, 0.35);
    border-color: rgba(39, 108, 163, 0.35);
}
</style>
