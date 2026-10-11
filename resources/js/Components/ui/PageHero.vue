<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
});
</script>

<template>
    <section class="bg-gradient-to-br from-institutional-900 to-institutional-700">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-1 text-sm text-white/70">{{ subtitle }}</p>
                </div>
                <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2">
                    <slot name="actions" />
                </div>
            </div>
            <slot />
        </div>
    </section>
</template>
