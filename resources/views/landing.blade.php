<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RSVP · Gestão de Convidados, Confirmações e Presença</title>
    <meta name="description" content="Plataforma premium para gestão de convidados de eventos corporativos e protocolares: RSVP, acompanhantes, representantes, credenciamento e auditoria — com isolamento por evento.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body class="bg-white antialiased">

{{-- ╭──────────────────────────────────────────────╮
     │  NAVBAR scroll-aware (Alpine)                  │
     ╰──────────────────────────────────────────────╯ --}}
<header
    x-data="{ scrolled: false, open: false }"
    x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 24)"
    :class="scrolled ? 'nav-scrolled' : 'bg-transparent'"
    class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
>
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
        <a href="#top" class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1c4e80] text-white shadow-lg shadow-[#1c4e80]/30">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </span>
            <span class="text-lg font-bold tracking-tight" :class="scrolled ? 'text-[#0b2a4a]' : 'text-white'">RSVP</span>
        </a>

        <div class="hidden items-center gap-8 md:flex">
            <a href="#recursos" class="text-sm font-medium transition-colors" :class="scrolled ? 'text-[#182738] hover:text-[#276ca3]' : 'text-white/80 hover:text-white'">Recursos</a>
            <a href="#fluxo" class="text-sm font-medium transition-colors" :class="scrolled ? 'text-[#182738] hover:text-[#276ca3]' : 'text-white/80 hover:text-white'">Como funciona</a>
            <a href="#indicadores" class="text-sm font-medium transition-colors" :class="scrolled ? 'text-[#182738] hover:text-[#276ca3]' : 'text-white/80 hover:text-white'">Indicadores</a>
            <a href="#faq" class="text-sm font-medium transition-colors" :class="scrolled ? 'text-[#182738] hover:text-[#276ca3]' : 'text-white/80 hover:text-white'">Dúvidas</a>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}"
               class="cta-glow hidden rounded-xl bg-[#1c4e80] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#1c4e80]/30 sm:inline-flex">
                Acessar plataforma
            </a>
            <a href="{{ route('login') }}" class="rounded-xl border px-4 py-2.5 text-sm font-semibold transition-colors sm:hidden"
               :class="scrolled ? 'border-[#1c4e80] text-[#1c4e80]' : 'border-white/40 text-white'">
                Entrar
            </a>
        </div>
    </nav>
</header>

{{-- ╭──────────────────────────────────────────────╮
     │  HERO                                          │
     ╰──────────────────────────────────────────────╯ --}}
<section id="top" class="hero-mesh hero-grain relative overflow-hidden">
    <div class="relative mx-auto max-w-7xl px-6 pb-24 pt-36 lg:px-8 lg:pt-44">
        <div class="mx-auto max-w-3xl text-center">
            <div class="reveal mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-xs font-medium text-white/90 backdrop-blur">
                <span class="live-dot inline-block h-2 w-2 rounded-full bg-emerald-400"></span>
                Credenciamento em tempo real · multi-eventos
            </div>

            <h1 class="reveal reveal-delay-1 text-4xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-5xl lg:text-6xl">
                Gestão de convidados que
                <span class="text-gradient">não confunde pessoas, confirmações e presenças.</span>
            </h1>

            <p class="reveal reveal-delay-2 mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-white/75">
                Plataforma premium para eventos corporativos e protocolares. Confirme RSVP,
                trate acompanhantes e representantes como relações entre pessoas, credencie na
                recepção e audite cada operação — com isolamento total por evento.
            </p>

            <div class="reveal reveal-delay-3 mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('login') }}"
                   class="cta-glow inline-flex items-center gap-2 rounded-xl bg-white px-7 py-3.5 text-sm font-semibold text-[#0b2a4a] shadow-xl">
                    Acessar a plataforma
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a href="#fluxo" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-7 py-3.5 text-sm font-semibold text-white transition-colors hover:bg-white/10">
                    Ver como funciona
                </a>
            </div>
        </div>

        {{-- Mock do painel — prova visual --}}
        <div class="reveal reveal-delay-3 mx-auto mt-16 max-w-5xl">
            <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-2 shadow-2xl backdrop-blur">
                <div class="grid grid-cols-2 gap-3 rounded-xl bg-[#0b2a4a]/60 p-4 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ([
                        ['Confirmados', '128', 'Titulares'],
                        ['Acompanhantes', '36', 'Vinculados'],
                        ['Representantes', '12', 'Substituições'],
                        ['Não confirmados', '44', 'Pendentes'],
                        ['Presenças', '96', 'Credenciados'],
                    ] as $card)
                        <div class="rounded-lg border border-white/10 bg-white/[0.04] p-4 text-left">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-white/50">{{ $card[0] }}</p>
                            <p class="mt-1 text-2xl font-bold text-white">{{ $card[1] }}</p>
                            <p class="text-[11px] text-white/40">{{ $card[2] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="accent-line mt-4 rounded-full"></div>
        </div>
    </div>
</section>

{{-- ╭──────────────────────────────────────────────╮
     │  RECURSOS — os 5 diferenciais do produto       │
     ╰──────────────────────────────────────────────╯ --}}
<section id="recursos" class="bg-[#f6f9fc] py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="reveal mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wider text-[#276ca3]">Precisão operacional</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-[#0b2a4a] sm:text-4xl">
                Cinco decisões que resolvem a maior parte dos erros de contagem
            </h2>
            <p class="mt-4 text-lg text-[#182738]/70">
                Cada funcionalidade existe para manter a integridade da informação — sem complexidade artificial.
            </p>
        </div>

        <div class="mt-16 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @php
                $features = [
                    ['M3 12h18M3 6h18M3 18h18', 'RSVP ≠ Presença', 'Confirmar participação não é comparecer. Estados distintos de RSVP (pendente, confirmado, recusado) e de presença física, com credenciamento próprio.'],
                    ['M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z', 'Acompanhantes & Representantes', 'Relações entre pessoas, não registros duplicados. Um acompanhante que confirma sem o titular é promovido a representante, preservando a origem.'],
                    ['M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', 'Pendente nunca é recusa', 'O valor 0 da importação significa pendente — nunca uma recusa automática. A ausência só é identificada após o evento.'],
                    ['M5 13l4 4L19 7', 'Credenciamento (check-in)', 'Lista de presença de verdade: registra horário, responsável e correções. Check-ins repetidos nunca duplicam presenças.'],
                    ['M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm10-10V7a4 4 0 0 0-8 0v4', 'Isolamento por evento', 'Permissões por evento. Um usuário autorizado em um evento não acessa os dados de outro — nem por alteração de URL. Validação no servidor.'],
                    ['M4 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5zM4 13a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6zM16 13a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-6z', 'Importação inteligente', 'Excel, CSV, texto ou Markdown. Pré-visualização com erros e avisos, resolução de vínculos e nenhuma mesclagem silenciosa.'],
                ];
            @endphp

            @foreach ($features as $i => $f)
                <article class="feature-card reveal reveal-delay-{{ ($i % 3) + 1 }} group rounded-2xl border border-[#0b2a4a]/8 bg-white p-7 shadow-sm">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#1c4e80]/8 text-[#1c4e80] transition-colors group-hover:bg-[#1c4e80] group-hover:text-white">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $f[0] }}"/></svg>
                    </span>
                    <h3 class="mt-5 text-lg font-semibold text-[#0b2a4a]">{{ $f[1] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-[#182738]/70">{{ $f[2] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- ╭──────────────────────────────────────────────╮
     │  FLUXO — como funciona                         │
     ╰──────────────────────────────────────────────╯ --}}
<section id="fluxo" class="py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="reveal mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wider text-[#276ca3]">Do convite ao credenciamento</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-[#0b2a4a] sm:text-4xl">Um fluxo direto, sem ambiguidades</h2>
        </div>

        <ol class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
            @php
                $steps = [
                    ['01', 'Cadastre ou importe', 'Adicione convidados manualmente ou importe listas com pré-visualização e validação de vínculos.'],
                    ['02', 'Confirme o RSVP', 'Marque confirmação, recusa ou mantenha pendente. Acompanhantes e representantes seguem as regras automaticamente.'],
                    ['03', 'Credencie na recepção', 'Busca imediata por nome ou número e check-in em um toque — otimizado para mobile e tablet.'],
                    ['04', 'Acompanhe e audite', 'Indicadores em tempo real, exportação Excel/PDF e auditoria de cada operação relevante.'],
                ];
            @endphp
            @foreach ($steps as $i => $s)
                <li class="reveal reveal-delay-{{ min($i + 1, 3) }} relative rounded-2xl border border-[#0b2a4a]/8 bg-[#f6f9fc] p-7">
                    <span class="text-4xl font-extrabold text-[#276ca3]/25">{{ $s[0] }}</span>
                    <h3 class="mt-3 text-lg font-semibold text-[#0b2a4a]">{{ $s[1] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-[#182738]/70">{{ $s[2] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ╭──────────────────────────────────────────────╮
     │  INDICADORES — prova de valor                  │
     ╰──────────────────────────────────────────────╯ --}}
<section id="indicadores" class="bg-[#0b2a4a] py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
            <div class="reveal">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    Totais que representam <span class="text-gradient">pessoas únicas</span>
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-white/70">
                    Participação prevista, taxa de confirmação e taxa de comparecimento calculadas
                    sem dupla contagem. Conflitos de vínculo são sinalizados para resolução explícita —
                    nunca silenciosa.
                </p>
                <a href="{{ route('login') }}" class="cta-glow mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-[#0b2a4a]">
                    Ver no painel
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>

            <div class="reveal reveal-delay-2 grid grid-cols-2 gap-4">
                @foreach ([
                    ['Participação prevista', '176 / 200', '88% do público estimado'],
                    ['Taxa de comparecimento', '72%', 'credenciados sobre previstos'],
                    ['Sem dupla contagem', '100%', 'pessoas únicas por papel'],
                    ['Operações auditadas', 'Todas', 'autor, instante e contexto'],
                ] as $stat)
                    <div class="rounded-2xl border border-white/10 bg-white/[0.05] p-6 backdrop-blur">
                        <p class="text-2xl font-bold text-white">{{ $stat[1] }}</p>
                        <p class="mt-1 text-sm font-medium text-white/80">{{ $stat[0] }}</p>
                        <p class="mt-0.5 text-xs text-white/45">{{ $stat[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ╭──────────────────────────────────────────────╮
     │  FAQ accordion (Alpine)                        │
     ╰──────────────────────────────────────────────╯ --}}
<section id="faq" class="py-24">
    <div class="mx-auto max-w-3xl px-6 lg:px-8">
        <div class="reveal text-center">
            <h2 class="text-3xl font-bold tracking-tight text-[#0b2a4a] sm:text-4xl">Dúvidas frequentes</h2>
        </div>

        <div class="mt-12 divide-y divide-[#0b2a4a]/10" x-data="{ open: 0 }">
            @php
                $faqs = [
                    ['Qual a diferença entre RSVP e presença?', 'RSVP é a confirmação de participação (pendente, confirmado ou não participará). Presença é o comparecimento físico, registrado no credenciamento. São processos independentes — confirmar não significa comparecer.'],
                    ['Como são tratados acompanhantes e representantes?', 'Como relações entre pessoas. Cada um tem registro e categoria próprios. Um acompanhante que confirma enquanto o titular não confirmou é promovido a representante, preservando o histórico do vínculo.'],
                    ['Um usuário pode acessar eventos de outros?', 'Não. As permissões são por evento e validadas no servidor. Não há acesso cruzado nem por alteração de URL — cada evento tem lista, numeração, permissões e auditoria independentes.'],
                    ['Quais formatos de importação são aceitos?', 'Excel (.xlsx), CSV, texto e Markdown, além de colagem direta e cadastro manual. Toda importação passa por pré-visualização com erros e avisos antes de persistir.'],
                    ['As operações ficam registradas?', 'Sim. Cadastros, confirmações, promoções, check-ins, importações, exportações e mudanças de permissão são auditados com autor, instante e contexto.'],
                ];
            @endphp
            @foreach ($faqs as $i => $faq)
                <div class="reveal">
                    <button
                        @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="flex w-full items-center justify-between gap-4 py-5 text-left"
                    >
                        <span class="text-base font-semibold text-[#0b2a4a]">{{ $faq[0] }}</span>
                        <svg class="h-5 w-5 shrink-0 text-[#276ca3] transition-transform duration-300"
                             :class="open === {{ $i }} ? 'rotate-45' : ''"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <div x-show="open === {{ $i }}"
                         x-collapse.duration.300ms
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-cloak>
                        <p class="pb-5 text-sm leading-relaxed text-[#182738]/70">{{ $faq[1] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ╭──────────────────────────────────────────────╮
     │  CTA final                                     │
     ╰──────────────────────────────────────────────╯ --}}
<section class="px-6 pb-24 lg:px-8">
    <div class="hero-mesh hero-grain reveal relative mx-auto max-w-5xl overflow-hidden rounded-3xl px-8 py-16 text-center shadow-2xl">
        <h2 class="relative text-3xl font-bold tracking-tight text-white sm:text-4xl">
            Pronto para operar seu próximo evento?
        </h2>
        <p class="relative mx-auto mt-4 max-w-xl text-lg text-white/75">
            Entre na plataforma e gerencie convidados, confirmações e presença com a precisão
            que eventos reais exigem.
        </p>
        <a href="{{ route('login') }}"
           class="cta-glow relative mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-sm font-semibold text-[#0b2a4a] shadow-xl">
            Acessar a plataforma
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    </div>
</section>

{{-- ╭──────────────────────────────────────────────╮
     │  FOOTER                                        │
     ╰──────────────────────────────────────────────╯ --}}
<footer class="border-t border-[#0b2a4a]/10 bg-[#f6f9fc]">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-10 sm:flex-row lg:px-8">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#1c4e80] text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </span>
            <span class="font-bold text-[#0b2a4a]">RSVP</span>
        </div>
        <p class="text-sm text-[#182738]/60">Gestão de convidados, confirmações e presença.</p>
        <a href="{{ route('login') }}" class="text-sm font-semibold text-[#1c4e80] hover:text-[#276ca3]">Acessar plataforma →</a>
    </div>
</footer>

<style>[x-cloak]{display:none!important;}</style>
</body>
</html>
