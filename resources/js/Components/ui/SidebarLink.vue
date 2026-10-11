<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
    label: { type: String, required: true },
    active: { type: Boolean, default: false },
    // nome do ícone (ver <template> abaixo)
    icon: { type: String, default: 'dot' },
});
</script>

<template>
    <Link
        :href="href"
        :aria-current="active ? 'page' : undefined"
        class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all duration-200"
        :class="active
            ? 'bg-gradient-to-r from-institutional-700 to-institutional-600 text-white shadow-lg shadow-institutional-900/25 -translate-y-px'
            : 'text-institutional-text/70 hover:-translate-y-px hover:bg-white hover:text-institutional-900 hover:shadow-md hover:shadow-institutional-900/10'"
    >
        <!-- Barra lateral do item ativo -->
        <span
            v-if="active"
            class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-white/80"
            aria-hidden="true"
        ></span>

        <span
            class="flex h-5 w-5 shrink-0 items-center justify-center transition-colors"
            :class="active ? 'text-white' : 'text-institutional-500 group-hover:text-institutional-600'"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <template v-if="icon === 'dashboard'"><rect width="7" height="9" x="3" y="3" rx="1" /><rect width="7" height="5" x="14" y="3" rx="1" /><rect width="7" height="9" x="14" y="12" rx="1" /><rect width="7" height="5" x="3" y="16" rx="1" /></template>
                <template v-else-if="icon === 'users'"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></template>
                <template v-else-if="icon === 'checkin'"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><path d="m9 11 3 3L22 4" /></template>
                <template v-else-if="icon === 'import'"><path d="M14 2v4a2 2 0 0 0 2 2h4" /><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z" /><path d="M12 18v-6" /><path d="m9 15 3 3 3-3" /></template>
                <template v-else-if="icon === 'conflict'"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></template>
                <template v-else-if="icon === 'audit'"><path d="M3 3v18h18" /><path d="m19 9-5 5-4-4-3 3" /></template>
                <template v-else-if="icon === 'calendar'"><path d="M8 2v4" /><path d="M16 2v4" /><rect width="18" height="18" x="3" y="4" rx="2" /><path d="M3 10h18" /></template>
                <template v-else><circle cx="12" cy="12" r="4" /></template>
            </svg>
        </span>
        <span class="truncate">{{ label }}</span>
    </Link>
</template>
