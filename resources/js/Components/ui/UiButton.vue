<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    // primary | secondary | ghost | success | danger | neutral
    // onLight (solido claro) e onLightGhost (contorno claro) p/ uso sobre fundo escuro (hero azul)
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' }, // sm | md
    href: { type: String, default: null }, // se presente, renderiza <Link>
    external: { type: Boolean, default: false }, // com href, usa <a> nativo (downloads/navegação fora do Inertia)
    type: { type: String, default: 'button' },
});

const base =
    'inline-flex items-center justify-center gap-1.5 font-medium transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-institutional-500 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

const variants = {
    primary: 'bg-institutional-600 text-white shadow-sm hover:bg-institutional-700 hover:-translate-y-0.5',
    secondary: 'border border-institutional-600 text-institutional-600 hover:bg-institutional-50',
    ghost: 'text-institutional-600 hover:bg-institutional-50',
    success: 'bg-emerald-600 text-white hover:bg-emerald-700',
    danger: 'bg-rose-600 text-white hover:bg-rose-700',
    neutral: 'border border-institutional-900/15 text-institutional-text hover:bg-institutional-50',
    // Para o hero azul: cinza claro legível sobre fundo escuro.
    onLight: 'bg-slate-100 text-institutional-900 shadow-sm hover:bg-white hover:-translate-y-0.5',
    onLightGhost: 'border border-white/40 text-white hover:bg-white/15 hover:-translate-y-0.5',
};

const sizes = {
    sm: 'rounded-lg px-2.5 py-1 text-xs',
    md: 'rounded-xl px-4 py-2 text-sm',
};
</script>

<template>
    <a v-if="href && external" :href="href" :class="[base, variants[variant], sizes[size]]">
        <slot />
    </a>
    <Link v-else-if="href" :href="href" :class="[base, variants[variant], sizes[size]]">
        <slot />
    </Link>
    <button v-else :type="type" :class="[base, variants[variant], sizes[size]]">
        <slot />
    </button>
</template>
