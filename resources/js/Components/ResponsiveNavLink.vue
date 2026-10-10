<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-institutional-500 text-start text-base font-medium text-institutional-700 bg-institutional-50 focus:outline-none focus:text-institutional-800 focus:bg-institutional-100 focus:border-institutional-700 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-institutional-text/70 hover:text-institutional-text hover:bg-institutional-50 hover:border-institutional-300 focus:outline-none focus:text-institutional-text focus:bg-institutional-50 focus:border-institutional-300 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
