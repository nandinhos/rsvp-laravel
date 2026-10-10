<script setup>
defineProps({
    // Eleva no hover (spring lift). Use em cards clicáveis/interativos.
    interactive: { type: Boolean, default: false },
    // Padding interno.
    padding: { type: String, default: 'p-5' },
});
</script>

<template>
    <div
        class="rounded-2xl border border-institutional-900/8 bg-white shadow-sm"
        :class="[padding, interactive ? 'ui-card-interactive' : '']"
    >
        <slot />
    </div>
</template>

<style scoped>
.ui-card-interactive {
    transition:
        transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1),
        box-shadow 0.3s ease,
        border-color 0.3s ease;
}
.ui-card-interactive:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 36px -18px rgba(11, 42, 74, 0.35);
    border-color: rgba(39, 108, 163, 0.35);
}
@media (prefers-reduced-motion: reduce) {
    .ui-card-interactive { transition: none; }
    .ui-card-interactive:hover { transform: none; }
}
</style>
