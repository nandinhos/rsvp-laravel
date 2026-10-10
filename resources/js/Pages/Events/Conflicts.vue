<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UiButton from '@/Components/ui/UiButton.vue';
import UiCard from '@/Components/ui/UiCard.vue';
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
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-institutional-900">Conflitos de representação — {{ event.name }}</h2>
                <UiButton :href="route('events.dashboard', event.id)" variant="ghost" size="md">Voltar</UiButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <EmptyState v-if="conflicts.length === 0" message="Nenhum conflito pendente. Tudo resolvido." />

                <UiCard v-for="c in conflicts" :key="c.id" padding="p-6">
                    <p class="mb-4 text-sm text-institutional-text/70">
                        O titular e seu representante estão ambos confirmados. Escolha quem participará:
                    </p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button
                            @click="resolve(c.id, 'host')"
                            class="conflict-choice rounded-xl border border-institutional-200 bg-institutional-50 p-4 text-left"
                        >
                            <p class="text-xs font-medium uppercase tracking-wide text-institutional-600">Manter titular</p>
                            <p class="mt-1 font-semibold text-institutional-900">#{{ c.host.number }} {{ c.host.name }}</p>
                            <p class="mt-0.5 text-xs text-institutional-text/50">Representante será marcado como não participará</p>
                        </button>
                        <button
                            @click="resolve(c.id, 'representative')"
                            class="conflict-choice rounded-xl border border-institutional-200 bg-institutional-50 p-4 text-left"
                        >
                            <p class="text-xs font-medium uppercase tracking-wide text-institutional-600">Manter representante</p>
                            <p class="mt-1 font-semibold text-institutional-900">#{{ c.representative.number }} {{ c.representative.name }}</p>
                            <p class="mt-0.5 text-xs text-institutional-text/50">Titular será marcado como não participará</p>
                        </button>
                    </div>
                </UiCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.conflict-choice {
    transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
}
.conflict-choice:hover {
    border-color: var(--color-institutional-400, #5e97c4);
    background-color: #e2edf7;
    transform: translateY(-1px);
}
@media (prefers-reduced-motion: reduce) {
    .conflict-choice { transition: none; }
    .conflict-choice:hover { transform: none; }
}
</style>
