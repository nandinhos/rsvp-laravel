<script setup>
import { reactive, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import UiButton from '@/Components/ui/UiButton.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    event: { type: Object, required: true },
    guest: { type: Object, default: null },
    channels: { type: Array, default: () => [] },
    outcomes: { type: Array, default: () => [] },
    presetChannel: { type: String, default: 'ligacao' },
    presetOutcome: { type: String, default: 'sem_resposta' },
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    channel: 'ligacao',
    outcome: 'sem_resposta',
    scheduled_return_at: '',
    note: '',
});

watch(() => props.show, (v) => {
    if (v) {
        form.reset();
        form.channel = props.presetChannel;
        form.outcome = props.presetOutcome;
    }
});

const inputClass =
    'w-full rounded-xl border-institutional-900/15 text-sm shadow-sm transition-colors focus:border-institutional-500 focus:ring-institutional-500';

function submit() {
    if (!props.guest) return;
    form.post(route('guests.contacts.store', [props.event.id, props.guest.id]), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
        },
    });
}
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <form class="p-6" @submit.prevent="submit">
            <h3 class="text-base font-semibold text-institutional-900">
                Registrar contato
                <span v-if="guest" class="text-institutional-text/50">· #{{ guest.number }} {{ guest.full_name }}</span>
            </h3>
            <p class="mt-1 text-xs text-institutional-text/50">
                Cada registro é mantido no histórico (append-only). Confirmar/reconfirmar reflete no RSVP.
            </p>
            <p v-if="form.channel === 'whatsapp'" class="mt-2 rounded-lg bg-institutional-50 px-3 py-2 text-xs text-institutional-700">
                Registro <strong>manual</strong> de contato por WhatsApp. O sistema não envia nem lê mensagens automaticamente.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-institutional-text/60">Canal</label>
                    <select v-model="form.channel" :class="inputClass">
                        <option v-for="c in channels" :key="c.value" :value="c.value">{{ c.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-institutional-text/60">Resultado</label>
                    <select v-model="form.outcome" :class="inputClass">
                        <option v-for="o in outcomes" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>
                </div>
                <div v-if="form.outcome === 'retorno_agendado'" class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-institutional-text/60">Retornar em</label>
                    <input v-model="form.scheduled_return_at" type="datetime-local" :class="inputClass" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-institutional-text/60">Observação</label>
                    <textarea v-model="form.note" rows="2" :class="inputClass" placeholder="Opcional"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <UiButton type="button" variant="neutral" size="md" @click="emit('close')">Cancelar</UiButton>
                <UiButton type="submit" variant="primary" size="md" :disabled="form.processing">Registrar</UiButton>
            </div>
        </form>
    </Modal>
</template>
