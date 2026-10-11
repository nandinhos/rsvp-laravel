<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: null },
    // 'default' (branco, accent bar no hover) | 'solid' (gradiente institucional)
    variant: { type: String, default: 'default' },
    // cor do valor em variante default: 'ink' | 'base' | 'interaction'
    accent: { type: String, default: 'ink' },
    // ponto colorido no topo direito (categoria da métrica): emerald|sky|violet|amber|slate|institutional
    dot: { type: String, default: null },
    // realça o card (ring institucional)
    highlight: { type: Boolean, default: false },
});

const dotColor = {
    emerald: 'bg-emerald-500',
    sky: 'bg-sky-500',
    violet: 'bg-violet-500',
    amber: 'bg-amber-500',
    slate: 'bg-slate-400',
    institutional: 'bg-institutional-500',
};

const accentText = {
    ink: 'text-institutional-900',
    base: 'text-institutional-600',
    interaction: 'text-institutional-500',
};
</script>

<template>
    <!-- Variante sólida: destaque com gradiente institucional -->
    <div
        v-if="variant === 'solid'"
        class="rounded-2xl border border-institutional-900/8 bg-gradient-to-br from-institutional-900 to-institutional-600 p-6 shadow-lg shadow-institutional-900/20"
    >
        <p class="text-xs font-medium uppercase tracking-wide text-white/60">{{ label }}</p>
        <p class="mt-1.5 text-3xl font-bold text-white">
            <slot name="value">{{ value }}</slot>
        </p>
        <p v-if="hint" class="mt-1 text-xs text-white/50">{{ hint }}</p>
    </div>

    <!-- Variante padrão: branco com accent bar no hover -->
    <div
        v-else
        class="stat-card group relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm"
        :class="highlight ? 'border-institutional-500 ring-1 ring-institutional-500/40' : 'border-institutional-900/8'"
    >
        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-institutional-600 to-institutional-500 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
        <div class="flex items-center justify-between">
            <p class="text-xs font-medium uppercase tracking-wide text-institutional-text/50">{{ label }}</p>
            <span v-if="dot" class="h-2.5 w-2.5 rounded-full" :class="dotColor[dot]"></span>
        </div>
        <p class="mt-1.5 text-3xl font-bold" :class="accentText[accent]">{{ value }}</p>
        <p v-if="hint" class="mt-1 text-xs text-institutional-text/40">{{ hint }}</p>
    </div>
</template>

<style scoped>
.stat-card {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 32px -16px rgba(11, 42, 74, 0.3);
}
@media (prefers-reduced-motion: reduce) {
    .stat-card { transition: none; }
    .stat-card:hover { transform: none; }
}
</style>
