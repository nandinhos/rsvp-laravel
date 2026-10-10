<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UiCard from '@/Components/ui/UiCard.vue';
import UiButton from '@/Components/ui/UiButton.vue';
import UiBadge from '@/Components/ui/UiBadge.vue';

const props = defineProps({
    event: Object,
    categories: Array,
});

const page = usePage();
const mode = ref('text'); // 'text' | 'file'
const text = ref('');
const file = ref(null);
const processing = ref(false);

const preview = computed(() => page.props.flash?.importPreview ?? null);

function submitPreview() {
    processing.value = true;
    const data = mode.value === 'file' ? { file: file.value } : { text: text.value };
    router.post(route('guests.import.preview', props.event.id), data, {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onFinish: () => (processing.value = false),
    });
}

function confirmImport() {
    if (!preview.value) return;
    processing.value = true;
    router.post(
        route('guests.import.store', props.event.id),
        { records: preview.value.payload },
        { onFinish: () => (processing.value = false) },
    );
}

function onFile(e) {
    file.value = e.target.files[0] ?? null;
}

const tabClass = (active) =>
    active
        ? 'bg-institutional-600 text-white'
        : 'bg-institutional-50 text-institutional-text/70 hover:bg-institutional-100';
</script>

<template>
    <Head :title="`Importar · ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-institutional-900">
                    Importar convidados — {{ event.name }}
                </h2>
                <UiButton :href="route('guests.index', event.id)" variant="ghost" size="md">Voltar à lista</UiButton>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <UiCard padding="p-6">
                    <div class="mb-4 flex gap-2">
                        <button @click="mode = 'text'" :class="tabClass(mode === 'text')" class="rounded-lg px-4 py-2 text-sm font-medium transition-colors">Colar texto</button>
                        <button @click="mode = 'file'" :class="tabClass(mode === 'file')" class="rounded-lg px-4 py-2 text-sm font-medium transition-colors">Enviar arquivo</button>
                        <a :href="route('guests.import.template')" class="ml-auto self-center text-sm font-medium text-institutional-600 hover:text-institutional-700 hover:underline">
                            Baixar modelo (.csv)
                        </a>
                    </div>

                    <div v-if="mode === 'text'">
                        <label class="mb-1 block text-sm font-medium text-institutional-text/70">
                            Formato: <code class="rounded bg-institutional-50 px-1 text-institutional-700">nome,tipo,relacao,confirmacao</code> — registros separados por <code class="rounded bg-institutional-50 px-1 text-institutional-700">;</code> ou nova linha
                        </label>
                        <textarea
                            v-model="text"
                            rows="6"
                            class="w-full rounded-xl border-institutional-900/15 font-mono text-sm shadow-sm focus:border-institutional-500 focus:ring-institutional-500"
                            placeholder="Ana Silva,Autoridade,,0;Bruno Costa,Convidado geral,Ana Silva,1"
                        />
                    </div>

                    <div v-else>
                        <label class="mb-1 block text-sm font-medium text-institutional-text/70">
                            Arquivo (.xlsx, .csv, .txt, .md)
                        </label>
                        <input type="file" accept=".xlsx,.xls,.csv,.txt,.md" @change="onFile" class="text-sm text-institutional-text/70" />
                    </div>

                    <UiButton variant="primary" class="mt-4" :disabled="processing" @click="submitPreview">
                        {{ processing ? 'Processando...' : 'Pré-visualizar' }}
                    </UiButton>
                </UiCard>

                <!-- Pré-visualização -->
                <UiCard v-if="preview" padding="p-6">
                    <h3 class="mb-3 font-semibold text-institutional-900">Pré-visualização</h3>
                    <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
                        <span class="text-institutional-text/70">Total: <strong class="text-institutional-900">{{ preview.summary.total }}</strong></span>
                        <UiBadge variant="success">Válidos: {{ preview.summary.valid }}</UiBadge>
                        <UiBadge variant="danger">Com erro: {{ preview.summary.invalid }}</UiBadge>
                        <UiBadge variant="info">Com vínculo: {{ preview.summary.with_relations }}</UiBadge>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-institutional-900/8">
                        <table class="min-w-full divide-y divide-institutional-900/8 text-sm">
                            <thead class="bg-institutional-50 text-left text-xs uppercase tracking-wide text-institutional-text/50">
                                <tr>
                                    <th class="px-3 py-2">#</th>
                                    <th class="px-3 py-2">Nome</th>
                                    <th class="px-3 py-2">Tipo</th>
                                    <th class="px-3 py-2">Relação</th>
                                    <th class="px-3 py-2">Confirmação</th>
                                    <th class="px-3 py-2">Situação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-institutional-900/6">
                                <tr v-for="row in preview.rows" :key="row.line" :class="row.valid ? '' : 'bg-rose-50'">
                                    <td class="px-3 py-2 text-institutional-text/40">{{ row.line }}</td>
                                    <td class="px-3 py-2 text-institutional-900">{{ row.name }}</td>
                                    <td class="px-3 py-2">{{ row.category }}</td>
                                    <td class="px-3 py-2">{{ row.relation || '—' }}</td>
                                    <td class="px-3 py-2">{{ row.rsvp === 'confirmado' ? 'Confirmado' : 'Pendente' }}</td>
                                    <td class="px-3 py-2">
                                        <ul v-if="row.errors.length" class="text-rose-700">
                                            <li v-for="(e, i) in row.errors" :key="i">⚠ {{ e }}</li>
                                        </ul>
                                        <ul v-if="row.warnings.length" class="text-amber-700">
                                            <li v-for="(w, i) in row.warnings" :key="i">• {{ w }}</li>
                                        </ul>
                                        <span v-if="row.valid && !row.warnings.length" class="font-medium text-emerald-700">OK</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <UiButton v-if="preview.summary.valid > 0" variant="success" class="mt-4" :disabled="processing" @click="confirmImport">
                        Confirmar importação de {{ preview.summary.valid }} registro(s)
                    </UiButton>
                </UiCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
