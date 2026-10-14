<script setup>
defineProps({
    // warning | danger | info
    variant: { type: String, default: 'warning' },
    title: { type: String, default: null },
    // Faixa compacta de largura total (ex.: topo do layout) ou card com borda lateral.
    full: { type: Boolean, default: false },
});

const styles = {
    warning: {
        band: 'bg-amber-500 text-amber-950',
        card: 'bg-amber-50 border-amber-600 text-amber-900',
        icon: 'text-amber-700',
        role: 'status',
    },
    danger: {
        band: 'bg-rose-600 text-white',
        card: 'bg-rose-50 border-rose-600 text-rose-900',
        icon: 'text-rose-700',
        role: 'alert',
    },
    info: {
        band: 'bg-institutional-600 text-white',
        card: 'bg-institutional-50 border-institutional-600 text-institutional-900',
        icon: 'text-institutional-600',
        role: 'status',
    },
};
</script>

<template>
    <!-- Faixa de largura total (topo de layout) -->
    <div
        v-if="full"
        :role="styles[variant].role"
        class="flex items-center justify-center gap-2 px-4 py-1.5 text-xs font-medium"
        :class="styles[variant].band"
    >
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 9v4" /><path d="M12 17h.01" />
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
        </svg>
        <span><slot /></span>
    </div>

    <!-- Card com borda lateral (dentro de uma página) -->
    <div
        v-else
        :role="styles[variant].role"
        class="flex flex-col gap-3 rounded-r-lg border-l-4 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
        :class="styles[variant].card"
    >
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 shrink-0" :class="styles[variant].icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10" /><path d="M12 8v4" /><path d="M12 16h.01" />
            </svg>
            <div>
                <h3 v-if="title" class="text-sm font-semibold">{{ title }}</h3>
                <p class="text-xs" :class="title ? 'mt-0.5' : ''"><slot /></p>
            </div>
        </div>
        <div v-if="$slots.action" class="shrink-0">
            <slot name="action" />
        </div>
    </div>
</template>
