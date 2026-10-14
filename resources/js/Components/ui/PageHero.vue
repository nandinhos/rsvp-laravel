<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
});
</script>

<template>
    <!-- Card flutuante (mesma estrutura do banner do painel), em tom institucional. -->
    <div class="px-4 pt-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-institutional-900 to-institutional-700 shadow-lg shadow-institutional-900/20">
            <div class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-1 text-sm text-white/70">{{ subtitle }}</p>
                </div>
                <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2">
                    <slot name="actions" />
                </div>
            </div>
            <slot />
        </section>
    </div>
</template>
