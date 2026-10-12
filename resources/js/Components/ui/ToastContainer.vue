<script setup>
import { useToast } from '@/composables/useToast';

const { toasts, dismiss } = useToast();

const accent = {
    success: 'text-emerald-400',
    warning: 'text-amber-400',
    info: 'text-sky-300',
};

function runUndo(toast) {
    toast.onUndo?.();
    dismiss(toast.id);
}
</script>

<template>
    <div
        class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-2"
        aria-live="polite"
        aria-atomic="false"
    >
        <TransitionGroup name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex items-start justify-between gap-3 rounded-xl border border-institutional-500/30 bg-institutional-900 p-3.5 text-white shadow-xl"
                role="status"
            >
                <div class="flex min-w-0 items-start gap-2.5">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" :class="accent[toast.type]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><path d="m9 11 3 3L22 4" />
                    </svg>
                    <div class="min-w-0 text-xs">
                        <h4 class="font-bold leading-tight">{{ toast.title }}</h4>
                        <p v-if="toast.message" class="mt-0.5 text-[11px] leading-snug text-white/80">{{ toast.message }}</p>
                        <button
                            v-if="toast.onUndo"
                            type="button"
                            class="mt-2 inline-flex items-center gap-1.5 rounded bg-white px-2.5 py-1 text-[11px] font-bold text-institutional-900 shadow-sm transition-colors hover:bg-institutional-50"
                            @click="runUndo(toast)"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 7v6h6" /><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13" />
                            </svg>
                            <span>{{ toast.undoLabel || 'Desfazer' }}</span>
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    class="shrink-0 p-1 text-white/70 transition-colors hover:text-white"
                    title="Fechar aviso"
                    @click="dismiss(toast.id)"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}
.toast-enter-from {
    opacity: 0;
    transform: translateY(-12px);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(16px);
}
@media (prefers-reduced-motion: reduce) {
    .toast-enter-active,
    .toast-leave-active {
        transition: none;
    }
}
</style>
