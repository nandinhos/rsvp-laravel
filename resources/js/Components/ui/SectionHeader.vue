<script setup>
defineProps({
    eyebrow: { type: String, default: null },
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
    center: { type: Boolean, default: false },
});
</script>

<template>
    <div :class="center ? 'mx-auto max-w-2xl text-center' : ''">
        <p v-if="eyebrow" class="text-sm font-semibold uppercase tracking-wider text-institutional-500">
            {{ eyebrow }}
        </p>
        <h2 class="mt-2 text-2xl font-bold tracking-tight text-institutional-900 sm:text-3xl">
            {{ title }}
        </h2>
        <p v-if="subtitle" class="mt-3 text-base text-institutional-text/70">
            {{ subtitle }}
        </p>
    </div>
</template>
