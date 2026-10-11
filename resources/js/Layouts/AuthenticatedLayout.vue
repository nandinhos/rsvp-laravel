<script setup>
import { ref } from 'vue';
import Sidebar from '@/Components/ui/Sidebar.vue';
import ToastContainer from '@/Components/ui/ToastContainer.vue';

const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-institutional-neutral">
        <!-- Sidebar fixa (desktop) -->
        <div class="fixed inset-y-0 left-0 z-30 hidden lg:block">
            <Sidebar />
        </div>

        <!-- Drawer da sidebar (mobile) -->
        <transition name="overlay">
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-40 bg-institutional-900/40 lg:hidden"
                @click="sidebarOpen = false"
            ></div>
        </transition>
        <transition name="drawer">
            <div v-if="sidebarOpen" class="fixed inset-y-0 left-0 z-50 lg:hidden">
                <Sidebar />
            </div>
        </transition>

        <!-- Coluna de conteúdo (deslocada pela largura da sidebar em desktop) -->
        <div class="lg:pl-64">
            <!-- Topbar (apenas mobile: botão para abrir a sidebar) -->
            <div class="flex h-16 items-center gap-3 border-b border-institutional-900/8 bg-white px-4 lg:hidden">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-institutional-text/70 transition hover:bg-institutional-50 hover:text-institutional-900"
                    aria-label="Abrir menu"
                    @click="sidebarOpen = true"
                >
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <span class="text-base font-bold tracking-tight text-institutional-900">RSVP</span>
            </div>

            <!-- Faixa de alerta global (ex.: evento encerrado — CA-14) -->
            <slot name="banner" />

            <!-- Cabeçalho da página -->
            <header v-if="$slots.header" class="bg-white shadow-sm">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Conteúdo -->
            <main>
                <slot />
            </main>
        </div>

        <!-- Notificações globais (toasts com desfazer) -->
        <ToastContainer />
    </div>
</template>

<style scoped>
.overlay-enter-active,
.overlay-leave-active {
    transition: opacity 0.2s ease;
}
.overlay-enter-from,
.overlay-leave-to {
    opacity: 0;
}
.drawer-enter-active,
.drawer-leave-active {
    transition: transform 0.25s ease;
}
.drawer-enter-from,
.drawer-leave-to {
    transform: translateX(-100%);
}
@media (prefers-reduced-motion: reduce) {
    .overlay-enter-active,
    .overlay-leave-active,
    .drawer-enter-active,
    .drawer-leave-active {
        transition: none;
    }
}
</style>
