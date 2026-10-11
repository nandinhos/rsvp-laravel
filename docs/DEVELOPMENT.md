# Desenvolvimento

Guia para dar continuidade ao desenvolvimento do RSVP.

---

## Ambiente

### Opção A — Docker (recomendada)

```bash
docker compose up -d --build
# App: http://localhost:8900 · PostgreSQL: localhost:55432
docker compose logs -f app      # acompanhar logs
docker compose exec app php artisan tinker
```

### Opção B — Local

Pré-requisitos: PHP 8.4+, Composer, Node 20+, PostgreSQL 16.

```bash
cp .env.example .env
composer install && npm install
php artisan key:generate
php artisan migrate --seed
npm run dev        # Vite com hot reload
php artisan serve
```

---

## Fluxo de trabalho

1. Crie um branch a partir de `dev`: `git checkout -b feature/minha-feature`.
2. Implemente com testes (ver abaixo).
3. Rode `./vendor/bin/pest` e `./vendor/bin/pint` antes de commitar.
4. Rode `npm run build` se tocou em assets.
5. Abra um PR para `dev`. Veja [CONTRIBUTING.md](../CONTRIBUTING.md).

> `dev` é o branch de integração; `main` reflete releases estáveis.

---

## Testes (Pest)

```bash
./vendor/bin/pest                       # suíte completa
./vendor/bin/pest --filter=GuestDomain  # um arquivo/grupo
./vendor/bin/pest --coverage            # cobertura (requer xdebug/pcov)
```

- Rodam sobre **SQLite em memória** (`phpunit.xml`), determinísticos e isolados do banco de dev.
- Toda regra de domínio nova deve ter teste. Os testes atuais mapeiam os critérios
  de aceite (ver tabela em [ARCHITECTURE.md](ARCHITECTURE.md)).

Arquivos de teste em `tests/Feature/`:
`EventAuthorizationTest`, `GuestNumberingTest`, `GuestDomainTest`, `GuestListTest`,
`DashboardTest`, `GuestImportTest`, `GuestExportTest`, `AuditTest`.

---

## Convenções de código

### PHP
- `./vendor/bin/pint` (preset Laravel) antes de commitar.
- Regras de domínio vivem em **serviços** (`app/Services/`), não em controllers.
- Enums como fonte da verdade dos estados; nunca strings mágicas.
- Transações (`DB::transaction`) em operações críticas; `forceFill` para mudanças controladas de estado.

### Frontend (Inertia + Vue 3)
- Componentes de página em `resources/js/Pages/`; componentes reutilizáveis em `resources/js/Components/`.
- Biblioteca de UI premium em `resources/js/Components/ui/` — reutilize antes de criar
  (`UiCard`, `StatCard`, `UiBadge`, `UiButton`, `EmptyState`, `SectionHeader`).

---

## Design system

**Fonte única de verdade:** a paleta institucional está em `tailwind.config.js`
sob a chave `institutional` (escala `50`–`950` + aliases semânticos
`deep/base/interaction/surface/neutral/text`).

- **Nunca** use hex hardcoded ou arbitrary values (`[#...]`) nas views — use classes
  `institutional-*`.
- Fonte: **Inter** (carregada via Bunny Fonts no layout).
- Motion CSS-first; sempre respeitar `prefers-reduced-motion`.

A **landing** (`resources/views/landing.blade.php`) é Blade + Alpine, com entries
Vite próprios (`resources/css/landing.css`, `resources/js/landing.js`). É servida
fora do contexto Inertia — por isso links entre landing e app usam âncoras `<a>`
normais (não o `<Link>` do Inertia).

---

## Build de assets

```bash
npm run dev      # desenvolvimento (HMR)
npm run build    # produção (gera public/build + manifest)
```

> Ao criar uma **nova página Inertia** (`.vue` em `Pages/`), rode `npm run build`
> antes de testá-la em ambiente que resolve pelo manifest (ex.: container), senão
> o Inertia não localiza o componente.

---

## Banco de dados

```bash
php artisan migrate              # aplica migrations pendentes
php artisan migrate:fresh --seed # recria do zero + seeders (DEV)
php artisan db:seed              # apenas seeders
```

Migrations em `database/migrations/` (prefixo `2026_01_01_*` para o domínio RSVP).
Seeder principal: `DatabaseSeeder` (usuários demo + evento de exemplo com o caso
de promoção acompanhante→representante).
