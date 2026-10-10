<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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
</script>

<template>
    <Head :title="`Importar · ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Importar convidados — {{ event.name }}
                </h2>
                <Link :href="route('guests.index', event.id)" class="text-sm text-sky-700 hover:underline">
                    Voltar à lista
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-5 shadow">
                    <div class="mb-4 flex gap-2">
                        <button
                            @click="mode = 'text'"
                            :class="mode === 'text' ? 'bg-sky-700 text-white' : 'bg-gray-100 text-gray-700'"
                            class="rounded px-4 py-2 text-sm font-medium"
                        >Colar texto</button>
                        <button
                            @click="mode = 'file'"
                            :class="mode === 'file' ? 'bg-sky-700 text-white' : 'bg-gray-100 text-gray-700'"
                            class="rounded px-4 py-2 text-sm font-medium"
                        >Enviar arquivo</button>
                        <a :href="route('guests.import.template')" class="ml-auto self-center text-sm text-sky-700 hover:underline">
                            Baixar modelo (.csv)
                        </a>
                    </div>

                    <div v-if="mode === 'text'">
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Formato: <code>nome,tipo,relacao,confirmacao</code> — registros separados por <code>;</code> ou nova linha
                        </label>
                        <textarea
                            v-model="text"
                            rows="6"
                            class="w-full rounded-md border-gray-300 font-mono text-sm shadow-sm"
                            placeholder="Ana Silva,Autoridade,,0;Bruno Costa,Convidado geral,Ana Silva,1"
                        />
                    </div>

                    <div v-else>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Arquivo (.xlsx, .csv, .txt, .md)
                        </label>
                        <input type="file" accept=".xlsx,.xls,.csv,.txt,.md" @change="onFile" class="text-sm" />
                    </div>

                    <button
                        @click="submitPreview"
                        :disabled="processing"
                        class="mt-4 rounded-md bg-sky-700 px-4 py-2 text-sm font-medium text-white hover:bg-sky-800 disabled:opacity-50"
                    >
                        {{ processing ? 'Processando...' : 'Pré-visualizar' }}
                    </button>
                </div>

                <!-- Pré-visualização -->
                <div v-if="preview" class="rounded-lg bg-white p-5 shadow">
                    <h3 class="mb-3 font-semibold text-gray-900">Pré-visualização</h3>
                    <div class="mb-4 flex flex-wrap gap-4 text-sm">
                        <span>Total: <strong>{{ preview.summary.total }}</strong></span>
                        <span class="text-emerald-700">Válidos: <strong>{{ preview.summary.valid }}</strong></span>
                        <span class="text-rose-700">Com erro: <strong>{{ preview.summary.invalid }}</strong></span>
                        <span class="text-sky-700">Com vínculo: <strong>{{ preview.summary.with_relations }}</strong></span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2">#</th>
                                    <th class="px-3 py-2">Nome</th>
                                    <th class="px-3 py-2">Tipo</th>
                                    <th class="px-3 py-2">Relação</th>
                                    <th class="px-3 py-2">Confirmação</th>
                                    <th class="px-3 py-2">Situação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="row in preview.rows" :key="row.line" :class="row.valid ? '' : 'bg-rose-50'">
                                    <td class="px-3 py-2 text-gray-400">{{ row.line }}</td>
                                    <td class="px-3 py-2">{{ row.name }}</td>
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
                                        <span v-if="row.valid && !row.warnings.length" class="text-emerald-700">OK</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button
                        v-if="preview.summary.valid > 0"
                        @click="confirmImport"
                        :disabled="processing"
                        class="mt-4 rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                    >
                        Confirmar importação de {{ preview.summary.valid }} registro(s)
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
