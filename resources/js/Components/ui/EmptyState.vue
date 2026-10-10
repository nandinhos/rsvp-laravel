<script setup>
defineProps({
    message: { type: String, default: 'Nada por aqui ainda.' },
});
</script>

<template>
    <div class="rounded-2xl border border-dashed border-institutional-900/15 bg-white p-10 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-institutional-50 text-institutional-400">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7" /><path d="M21 21l-4.3-4.3" />
            </svg>
        </div>
        <p class="text-sm text-institutional-text/60">{{ message }}</p>
        <div class="mt-4"><slot /></div>
    </div>
</template>
