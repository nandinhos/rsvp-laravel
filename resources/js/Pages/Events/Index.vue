<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UiCard from '@/Components/ui/UiCard.vue';
import UiBadge from '@/Components/ui/UiBadge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';

defineProps({
    events: Array,
});

const statusVariant = {
    rascunho: 'neutral',
    ativo: 'success',
    encerrado: 'neutral',
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
            <h2 class="text-xl font-semibold leading-tight text-institutional-900">Meus eventos</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <EmptyState v-if="events.length === 0" message="Você ainda não tem acesso a nenhum evento." />

                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="event in events"
                        :key="event.id"
                        :href="route('events.dashboard', event.id)"
                        class="group block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-institutional-500 focus-visible:ring-offset-2"
                    >
                        <UiCard interactive class="h-full">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-institutional-900 transition-colors group-hover:text-institutional-600">{{ event.name }}</h3>
                                <UiBadge :variant="statusVariant[event.status]">{{ event.status_label }}</UiBadge>
                            </div>
                            <p class="mt-2 text-sm text-institutional-text/60">{{ formatDate(event.starts_at) }}</p>
                            <p class="text-sm text-institutional-text/60">{{ event.location }}</p>
                            <p class="mt-3 text-xs font-medium uppercase tracking-wide text-institutional-500">{{ event.role_label }}</p>
                        </UiCard>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
