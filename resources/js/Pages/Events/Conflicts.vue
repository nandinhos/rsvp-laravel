<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Conflitos de representação — {{ event.name }}</h2>
                <Link :href="route('events.dashboard', event.id)" class="text-sm text-sky-700 hover:underline">Voltar</Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="conflicts.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                    Nenhum conflito pendente. Tudo resolvido.
                </div>

                <div v-for="c in conflicts" :key="c.id" class="rounded-lg bg-white p-5 shadow">
                    <p class="mb-3 text-sm text-gray-600">
                        O titular e seu representante estão ambos confirmados. Escolha quem participará:
                    </p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button
                            @click="resolve(c.id, 'host')"
                            class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-left hover:border-sky-400"
                        >
                            <p class="text-xs font-medium uppercase text-sky-700">Manter titular</p>
                            <p class="font-semibold text-gray-900">#{{ c.host.number }} {{ c.host.name }}</p>
                            <p class="text-xs text-gray-500">Representante será marcado como não participará</p>
                        </button>
                        <button
                            @click="resolve(c.id, 'representative')"
                            class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-left hover:border-sky-400"
                        >
                            <p class="text-xs font-medium uppercase text-sky-700">Manter representante</p>
                            <p class="font-semibold text-gray-900">#{{ c.representative.number }} {{ c.representative.name }}</p>
                            <p class="text-xs text-gray-500">Titular será marcado como não participará</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
