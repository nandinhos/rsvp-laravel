<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    event: Object,
    logs: Object,
});

const actionLabel = {
    'guest.created': 'Convidado criado',
    'guest.deleted': 'Convidado removido',
    'guest.confirmed': 'Participação confirmada',
    'guest.declined': 'Ausência registrada',
    'guest.checked_in': 'Credenciamento',
    'guests.imported': 'Importação',
    'conflict.resolved': 'Conflito resolvido',
    'event.closed': 'Evento encerrado',
    'event.reopened': 'Evento reaberto',
};
</script>

<template>
    <Head :title="`Auditoria · ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Auditoria — {{ event.name }}</h2>
                <Link :href="route('events.dashboard', event.id)" class="text-sm text-sky-700 hover:underline">Voltar</Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="logs.data.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
                    Nenhum registro de auditoria ainda.
                </div>

                <div v-else class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Data/Hora</th>
                                <th class="px-4 py-3">Ação</th>
                                <th class="px-4 py-3">Usuário</th>
                                <th class="px-4 py-3">Origem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="log in logs.data" :key="log.id">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-gray-500">{{ log.created_at }}</td>
                                <td class="px-4 py-3 text-gray-900">{{ actionLabel[log.action] ?? log.action }}</td>
                                <td class="px-4 py-3">{{ log.user }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-400">{{ log.ip_address }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
