# RSVP — Gestão de Convidados, Confirmações e Presença

Plataforma web **mobile-first** para gestão de convidados de eventos corporativos,
institucionais e protocolares: cadastro, classificação, confirmações RSVP,
acompanhantes, representantes, credenciamento (check-in), importação, exportação
e auditoria — com **isolamento total por evento**.

> Premissa central do produto: **não confundir pessoas, convites, confirmações,
> relações de representação e presenças.** Esse cuidado resolve a maior parte dos
> problemas de contagem e inconsistência em sistemas RSVP.

---

## Sumário

- [Principais recursos](#principais-recursos)
- [Stack](#stack)
- [Início rápido (Docker)](#início-rápido-docker)
- [Início rápido (local)](#início-rápido-local)
- [Usuários de demonstração](#usuários-de-demonstração)
- [Testes](#testes)
- [Critérios de aceite](#critérios-de-aceite)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Documentação adicional](#documentação-adicional)

---

## Principais recursos

| Recurso | Descrição |
|---|---|
| **RSVP ≠ Presença** | Estados de confirmação (pendente, confirmado, não participará) distintos do comparecimento físico. |
| **Acompanhantes & Representantes** | Relações entre pessoas, sem duplicidade. Acompanhante de titular não confirmado que confirma é **promovido a representante**, preservando a origem. |
| **Conflito de representação** | Titular e representante ambos confirmados geram pendência **bloqueante** de resolução explícita — nunca silenciosa. |
| **Credenciamento (check-in)** | Idempotente; registra horário e responsável. Check-ins repetidos não duplicam presenças. |
| **Isolamento por evento** | Permissões por evento validadas no servidor; sem acesso cruzado nem por alteração de URL. |
| **Importação inteligente** | Excel/CSV/TXT/Markdown + colagem + manual, com pré-visualização de erros/avisos antes de persistir. |
| **Exportações** | Excel (.xlsx) e PDF premium, respeitando filtros e permissões; guarda contra injeção de fórmula. |
| **Dashboard de indicadores** | Participação prevista, taxas de confirmação/comparecimento, sem dupla contagem. |
| **Auditoria** | Registro append-only de operações críticas com autor, instante e contexto. |
| **Ciclo de vida do evento** | Rascunho → ativo → encerrado; encerramento bloqueia alterações, reabertura é auditada. |

Perfis de acesso (por evento): **Administrador**, **Gestor**, **Operador**, **Leitor**.

---

## Stack

- **Backend:** Laravel 13 (PHP 8.4)
- **Frontend:** Inertia.js + Vue 3 + Tailwind CSS 3 (+ Alpine.js na landing)
- **Banco:** PostgreSQL 16 (SQLite em memória nos testes)
- **Testes:** Pest
- **Documentos:** PhpSpreadsheet (xlsx), dompdf (PDF)
- **Infra:** Docker + Docker Compose

---

## Início rápido (Docker)

Pré-requisitos: Docker e Docker Compose.

```bash
git clone https://github.com/nandinhos/rsvp-laravel.git
cd rsvp-laravel
docker compose up -d --build
```

A stack sobe encapsulada em portas que **não conflitam** com serviços comuns:

| Serviço | URL / porta |
|---|---|
| Aplicação | http://localhost:8900 |
| PostgreSQL | localhost:55432 (db `rsvp_kiro`, user `rsvp`, senha `rsvp`) |

Na primeira subida o container executa migrations e **seed** automaticamente
(usuários de demonstração + evento de exemplo). Acesse http://localhost:8900.

> Para produção, defina `APP_KEY` no ambiente (veja [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)).
> O valor presente no `docker-compose.yml` é apenas um fallback de demonstração.

---

## Início rápido (local)

Pré-requisitos: PHP 8.4+, Composer, Node 20+, PostgreSQL 16.

```bash
cp .env.example .env
composer install
npm install

# Configure as credenciais do banco no .env, então:
php artisan key:generate
php artisan migrate --seed
npm run build          # ou: npm run dev (hot reload)
php artisan serve
```

---

## Usuários de demonstração

Criados pelo seeder (senha padrão: `password`). Exibidos também abaixo do formulário de login.

| Perfil | E-mail | Pode |
|---|---|---|
| Administrador | `admin@rsvp.test` | tudo: permissões, encerrar/reabrir, auditoria |
| Gestor | `gestor@rsvp.test` | convidados, vínculos, importar, exportar |
| Operador | `operador@rsvp.test` | confirmar, recusar, check-in, resolver conflitos |
| Leitor | `leitor@rsvp.test` | somente consulta |

Evento semente: **Cerimônia Institucional 2026**, com categorias padrão e convidados de exemplo
(incluindo o caso de promoção acompanhante → representante).

---

## Testes

```bash
./vendor/bin/pest            # suíte completa
./vendor/bin/pest --filter=GuestDomain   # um grupo
./vendor/bin/pint            # code style (PHP)
```

Os testes rodam sobre **SQLite em memória** (determinístico, isolado do banco de dev).

---

## Critérios de aceite

A implementação é guiada por 14 critérios de aceite (CA-01 a CA-14). Treze deles são cobertos
por testes automatizados — isolamento entre eventos, regras de representação, idempotência de
check-in, indicadores sem dupla contagem, importação com pré-visualização, exportação com
permissão server-side, auditoria e encerramento de evento. O **CA-12 (responsividade)** é
validado manualmente nas três classes de dispositivo. Detalhes em
[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

---

## Estrutura do projeto

```
app/
├── Enums/                 # Estados de domínio (RSVP, papel, presença, perfil...)
├── Http/Controllers/      # Event, Dashboard, Guest, GuestImport, GuestExport, Conflict
├── Models/                # Event, EventUser, GuestCategory, Guest, GuestRelationship, AuditLog, User
├── Policies/              # EventPolicy (autorização por evento)
└── Services/              # Regras de domínio (RSVP, vínculos, métricas, check-in, numeração)
    ├── Import/            # Pipeline de importação (parser, analyzer, persister)
    └── Export/            # Exportadores Excel e PDF
resources/js/
├── Components/ui/         # Biblioteca de componentes premium (UiCard, StatCard, UiButton...)
├── Layouts/               # AuthenticatedLayout, GuestLayout (login split-screen)
└── Pages/                 # Events, Guests, Auth (Inertia/Vue)
resources/views/
└── landing.blade.php      # Landing page animada (Blade + Alpine)
docs/                      # Documentação de arquitetura, desenvolvimento e deploy
```

---

## Documentação adicional

- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — modelo de domínio, entidades, regras e decisões
- [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md) — fluxo de desenvolvimento, testes, convenções, design system
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — Docker, variáveis de ambiente, operações
- [CONTRIBUTING.md](CONTRIBUTING.md) — como contribuir

---

## Licença

[MIT](LICENSE).
