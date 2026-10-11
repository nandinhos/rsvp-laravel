<script setup>
defineProps({
    // Largura máxima do conteúdo. 'full' ocupa toda a área disponível
    // (com padding lateral consistente). Use um valor Tailwind p/ limitar.
    maxWidth: { type: String, default: 'full' },
});

const maxWidthClass = {
    full: 'max-w-none',
    '7xl': 'max-w-7xl mx-auto',
    '5xl': 'max-w-5xl mx-auto',
    '4xl': 'max-w-4xl mx-auto',
    '3xl': 'max-w-3xl mx-auto',
};
</script>

<template>
    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div :class="maxWidthClass[maxWidth] ?? 'max-w-none'">
            <slot />
        </div>
    </div>
</template>
