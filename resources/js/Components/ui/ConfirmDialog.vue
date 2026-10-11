<script setup>
import Modal from '@/Components/Modal.vue';
import UiButton from '@/Components/ui/UiButton.vue';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    message: { type: String, default: null },
    confirmLabel: { type: String, default: 'Confirmar' },
    cancelLabel: { type: String, default: 'Cancelar' },
    // variante do botão de confirmação: primary | success | danger
    confirmVariant: { type: String, default: 'primary' },
    // tom do ícone/realce: info | warning | danger
    tone: { type: String, default: 'warning' },
});

const emit = defineEmits(['confirm', 'close']);

const toneStyle = {
    info: { ring: 'bg-institutional-100 text-institutional-600' },
    warning: { ring: 'bg-amber-100 text-amber-700' },
    danger: { ring: 'bg-rose-100 text-rose-700' },
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full" :class="toneStyle[tone].ring">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-institutional-900">{{ title }}</h3>
                    <p v-if="message" class="mt-1 text-sm text-institutional-text/70">{{ message }}</p>
                    <slot />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <UiButton variant="neutral" size="md" @click="emit('close')">{{ cancelLabel }}</UiButton>
                <UiButton :variant="confirmVariant" size="md" @click="emit('confirm')">{{ confirmLabel }}</UiButton>
            </div>
        </div>
    </Modal>
</template>
