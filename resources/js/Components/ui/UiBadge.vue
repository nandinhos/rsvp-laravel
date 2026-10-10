<script setup>
defineProps({
    // neutral | info | success | warning | danger
    variant: { type: String, default: 'neutral' },
});

const styles = {
    neutral: 'bg-institutional-100 text-institutional-700',
    info: 'bg-institutional-100 text-institutional-600',
    success: 'bg-emerald-100 text-emerald-800',
    warning: 'bg-amber-100 text-amber-800',
    danger: 'bg-rose-100 text-rose-800',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
        :class="styles[variant]"
    >
        <slot />
    </span>
</template>
