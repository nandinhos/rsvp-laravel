# Deploy & Operação

A aplicação é **encapsulada em Docker** (app + PostgreSQL) em rede isolada.

---

## Composição (docker-compose.yml)

| Serviço | Imagem | Porta host → container |
|---|---|---|
| `app` | build local (multi-stage) | **8900** → 8000 |
| `pgsql` | postgres:16-alpine | **55432** → 5432 |

As portas de host foram escolhidas para **não conflitar** com serviços comuns
(5432, 8080, etc.). A app fala com o banco pela rede interna `rsvp-net` via
hostname `pgsql`.

```bash
docker compose up -d --build     # sobe a stack
docker compose ps                # status
docker compose logs -f app       # logs
docker compose down              # para (mantém o volume de dados)
docker compose down -v           # para e APAGA os dados (destrutivo)
```

### Build multi-stage (Dockerfile)

1. **vendor** — `composer install --no-dev` (dependências PHP).
2. **assets** — `npm ci && npm run build` em base Debian (glibc), compatível com o runtime.
3. **runtime** — `php:8.4-cli` com extensões `pdo_pgsql`, `gd`, `zip`, `intl`, `bcmath`;
   recebe vendor + assets compilados.

> Nota: o build do Vite roda em base glibc (não Alpine) para compatibilidade com
> os binários nativos do bundler.

### Entrypoint (`docker/entrypoint.sh`)

Na subida do container `app`: aguarda o PostgreSQL, roda `migrate --force`,
executa seeders (quando `DB_SEED=true`), faz `config:cache` e `route:cache`,
e inicia o servidor. É **idempotente** — reinícios não duplicam dados.

---

## Variáveis de ambiente

| Variável | Descrição | Padrão (demo) |
|---|---|---|
| `APP_KEY` | Chave de criptografia. **Obrigatória (sem fallback).** | — defina no `.env` |
| `APP_ENV` | Ambiente | `production` |
| `APP_URL` | URL pública | `http://localhost:8900` |
| `DB_HOST` / `DB_PORT` | Banco | `pgsql` / `5432` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Credenciais | `rsvp_kiro` / `rsvp` / `rsvp` |
| `DB_SEED` | Roda seeders no entrypoint | `true` |

> **Produção:** gere uma `APP_KEY` real (`php artisan key:generate --show`),
> defina-a no ambiente, use credenciais de banco fortes e `DB_SEED=false`.
> O `docker-compose.yml` **não** possui fallback de chave: `APP_KEY` deve
> estar definida no `.env` (local) ou no ambiente (produção), senão o
> container não sobe.
>
> **Retenção (LGPD):** execute `php artisan guests:purge` (manual ou agendado)
> para anonimizar convidados e eliminar contatos de eventos encerrados além
> do `retention_days` configurado no evento.

---

## Operações comuns

```bash
# Acessar o shell da aplicação
docker compose exec app sh

# Rodar artisan dentro do container
docker compose exec app php artisan migrate:status
docker compose exec app php artisan tinker

# Recriar o banco (DEV — destrutivo)
docker compose exec app php artisan migrate:fresh --seed --force

# Backup do volume PostgreSQL
docker compose exec pgsql pg_dump -U rsvp rsvp_kiro > backup.sql
```

---

## Saúde e verificação

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8900/        # landing → 200
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8900/login   # login → 200
docker compose ps   # pgsql deve estar "healthy"
```

O serviço `pgsql` possui `healthcheck`; o `app` só inicia após o banco ficar saudável
(`depends_on: condition: service_healthy`).
