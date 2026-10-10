<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen bg-institutional-neutral">
        <!-- Painel institucional (esquerda) -->
        <aside class="auth-hero relative hidden w-1/2 flex-col justify-between overflow-hidden p-12 lg:flex">
            <div class="relative z-10">
                <Link href="/" class="inline-flex items-center gap-2.5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white backdrop-blur">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" /></svg>
                    </span>
                    <span class="text-xl font-bold tracking-tight text-white">RSVP</span>
                </Link>
            </div>

            <div class="relative z-10 max-w-md">
                <h1 class="text-3xl font-bold leading-tight text-white">
                    Gestão de convidados, confirmações e presença.
                </h1>
                <p class="mt-4 text-base leading-relaxed text-white/70">
                    Confirme RSVP, trate acompanhantes e representantes, credencie na recepção
                    e audite cada operação — com isolamento total por evento.
                </p>

                <ul class="mt-8 space-y-3">
                    <li v-for="item in ['RSVP e presença como processos distintos', 'Acompanhantes e representantes sem duplicidade', 'Credenciamento com auditoria completa']"
                        :key="item"
                        class="flex items-center gap-3 text-sm text-white/80">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/15">
                            <svg class="h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5" /></svg>
                        </span>
                        {{ item }}
                    </li>
                </ul>
            </div>

            <p class="relative z-10 text-xs text-white/40">
                Plataforma premium para eventos corporativos e protocolares.
            </p>
        </aside>

        <!-- Formulário (direita) -->
        <main class="flex w-full flex-col items-center justify-center px-6 py-12 lg:w-1/2">
            <div class="w-full max-w-md">
                <!-- Branding mobile -->
                <Link href="/" class="mb-8 inline-flex items-center gap-2.5 lg:hidden">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-institutional-base text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" /></svg>
                    </span>
                    <span class="text-xl font-bold tracking-tight text-institutional-deep">RSVP</span>
                </Link>

                <div class="rounded-2xl border border-institutional-deep/10 bg-white p-8 shadow-xl shadow-institutional-deep/5">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.auth-hero {
    background-color: #0b2a4a;
    background-image:
        radial-gradient(40rem 40rem at 20% -10%, rgba(39, 108, 163, 0.55), transparent 60%),
        radial-gradient(36rem 36rem at 100% 110%, rgba(28, 78, 128, 0.6), transparent 55%);
}
</style>
