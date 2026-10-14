# Roadmap & Plano de Implementação — Evolução do MVP RSVP

**Branch de origem desta análise:** `analise/benchmark-roadmap`
**Base de código:** `dev` (114 testes verdes)
**Workflow:** execução **sequencial baseada em evidências** — uma melhoria por vez, cada uma com ciclo `INSPECT → IMPLEMENT → TEST (Pest) → BUILD+REBUILD → COMMIT`. Nada em paralelo.
**Fonte:** `docs/Relatorio_Tecnico_RSVP_Benchmark_e_Roadmap.md` + `docs/Analise_Aprofundada_Relatorio_vs_Codigo.md`.

---

## 1. Visão geral do roadmap

```
P0 (piloto demonstrável)         P1 (após validar operação real)      P2 (escala comercial)
┌────────────────────────┐       ┌──────────────────────────────┐    ┌──────────────────────┐
│ M6  Consistência docs   │       │ M7  Capacidade + espera       │    │ Multi-tenant/contas  │
│ M1  Histórico contatos  │──┐    │ M8  Integração WhatsApp oficial│    │ Onboarding           │
│ M2  Estágio de contato  │◀─┘    │ M9  Agendamento reconfirmação  │    │ API/webhooks assinados│
│ M3  Fila de trabalho    │       │ M10 Acompanhante c/ limite     │    │ Métricas entre eventos│
│ M5  Relatório operação  │       │ M11 QR individual check-in     │    └──────────────────────┘
│ M4  Reconfirmação WA man.│      │ M12 Campos operacionais mínimos│
│ SEC LGPD + APP_KEY       │      └──────────────────────────────┘    Futuro: IA (fora de escopo)
└────────────────────────┘
```

**Regra de promoção P1→execução:** só sobe quando a cliente confirmar frequência, dor atual, alternativa, custo/risco e critério de sucesso (recomendação do relatório §6).

---

## 2. Grafo de dependências

```
M6 (docs)         ── independente, faz primeiro (destrava a matriz CA)
   │
M1 (GuestContact) ── FUNDAÇÃO. Nada de confirmação ativa existe sem ela.
   ├── M2 (estágio de contato)      depende de M1 (deriva do último contato)
   │     └── M3 (fila de trabalho)  depende de M2 (ordena por estágio/retorno)
   │           └── M5 (relatório)   depende de M2 (denominadores por estágio)
   └── M4 (reconfirmação WhatsApp)  depende de M1 (é um GuestContact channel=whatsapp)

SEC/LGPD          ── independente de M1..M5; obrigatório ANTES de dados reais.

P1:
M7 (capacidade)   ── independente (usa Event.estimated_audience + nova coluna)
M8 (WhatsApp API) ── depende de M4 (registro manual) + validação comercial
M9 (agendamento)  ── depende de M3 (fila) e M1 (scheduled_return_at)
M10 (acompanhante)── independente, toca GuestRelationship
M11 (QR check-in) ── independente, toca CheckInService + nova coluna token
M12 (campos custom)── independente, decisão de produto + política de dados
```

**Caminho crítico:** `M1 → M2 → M3 → M5`. Essa é a espinha dorsal da confirmação ativa e deve ser feita nesta ordem.

---

## 3. Matriz de conflito (se fossem paralelas — referência, NÃO vamos paralelizar)

| Item | Arquivos que toca | Conflita com | Observação |
|---|---|---|---|
| M6 | `README.md`, `docs/ARCHITECTURE.md`, `composer.json` | — | Isolado. Zero conflito. |
| M1 | nova migration, `app/Models/GuestContact.php`, `Guest.php` (relação), `routes/web.php`, `GuestController` (ou novo `ContactController`) | M2, M3, M4 (todos leem GuestContact) | É a base; faz primeiro. |
| M2 | `GuestMetrics` ou novo `ContactStage` service, `GuestListQuery` (payload), páginas Vue | M1, M3, M5 | Deriva de M1; conflito alto se paralelo. |
| M3 | `routes/web.php`, `GuestController` (método fila), nova página `Pages/Guests/WorkQueue.vue`, `Sidebar.vue` | M1, M2 (rota + enum), `routes/web.php` | Edita rotas e sidebar (hotspots). |
| M4 | `ContactController`, `routes/web.php`, página ficha convidado | M1 (mesma tabela), M3 (sidebar/rotas) | Reusa M1. |
| M5 | `GuestMetrics`, `DashboardController`, `Dashboard.vue` | M2 (denominadores) | Conflito com M2 em GuestMetrics. |
| SEC/LGPD | `docker-compose.yml`, novo command de expurgo, `Event` | — | Isolado do fluxo. |

**Hotspots de conflito (editados por vários itens):** `routes/web.php`, `GuestController.php`, `Sidebar.vue`, `GuestMetrics.php`. É exatamente por isso que a execução **sequencial** é a escolha certa — evita merge hell nesses arquivos.

---

## 4. Plano detalhado por item (P0)

> Cada item segue o mesmo ciclo de evidência. "Pronto" = Pest verde + build + rebuild + validação HTTP + commit.

### M6 — Consistência documental · esforço XS · risco BAIXO
**Objetivo:** eliminar divergências que enfraquecem a matriz CA como prova.
**Passos:**
1. Decidir versão PHP-alvo real (provável 8.4, conforme README/Docker). Alinhar `composer.json` `"php"`.
2. Reconciliar numeração CA-01..CA-14 entre README e ARCHITECTURE; marcar CA-12 (responsividade) como "validação manual" até existir teste.
3. Nenhuma mudança de código de produção.
**Testes/evidência:** `composer validate`; conferência textual; `pest` segue verde.
**Critério de pronto:** README e ARCHITECTURE citam o mesmo conjunto CA; `composer.json` coerente com a stack.
**Dependências:** nenhuma. **Conflito:** nenhum. **Fazer primeiro.**

### M1 — Histórico de contatos (`GuestContact`) · esforço M · risco MÉDIO · FUNDAÇÃO
**Objetivo:** registrar cada tentativa de contato, append-only, isolada por evento.
**Modelagem:**
- Migration `guest_contacts`: `id`, `event_id` (FK, cascade), `guest_id` (FK, cascade), `channel` (string: `ligacao|whatsapp|outro`), `outcome` (string: `sem_resposta|retorno_agendado|confirmou|recusou|contato_invalido|reconfirmado`), `scheduled_return_at` (timestamp nullable), `note` (text nullable), `operator_id` (FK users, nullOnDelete), `timestamps`. Índices: `['event_id','guest_id']`, `['event_id','outcome']`, `['event_id','scheduled_return_at']`.
- Enums novos: `ContactChannel`, `ContactOutcome` (padrão dos enums existentes, com `label()`).
- Model `GuestContact` + relação `Guest::contacts()` (hasMany, ordenada desc).
**Backend:**
- `ContactService::record(Guest, channel, outcome, ...)` — valida evento aberto (reusa `guardOpen`), posse (CA-01), grava e **espelha efeito no RSVP quando aplicável** (ex.: outcome `confirmou` → `RsvpService::confirm`; `recusou` → `decline`). Decisão de produto: o contato é a origem, o RSVP é consequência.
- `ContactController::store` + rota `POST events/{event}/guests/{guest}/contacts` (`guests.contacts.store`), `Gate::operate`.
- Auditoria: `AuditLogger` registra `guest.contact_recorded`.
**Frontend:** painel/histórico de contatos na ficha do convidado (modal ou seção), formulário de novo contato (canal, resultado, retorno, nota).
**Testes Pest (novos):** grava contato; isolamento cross-evento (404); leitor não registra (403); evento encerrado bloqueia (403); outcome `confirmou` reflete no RSVP; histórico preserva tentativas anteriores (append-only).
**Critério de pronto:** CRUD de contato funcional, auditado, testado; histórico visível.
**Dependências:** M6 (opcional, só ordem). **Conflito:** base de M2/M3/M4.
**⚠️ Validar vocabulário de `outcome` com a cliente ANTES de congelar o enum** (recomendação P0 do relatório).

### M2 — Estágio de contato derivado · esforço S · risco BAIXO
**Objetivo:** distinguir "não contatado" de "pendente contatado / aguardando retorno" sem alterar o enum `RsvpStatus`.
**Abordagem conservadora:** novo serviço `ContactStage::for(Guest): string` que deriva de `guest->contacts` (último outcome + `scheduled_return_at` + se há algum contato): `nao_contatado | sem_resposta | retorno_agendado | retorno_vencido | respondido | reconfirmado`.
**Backend:** expor `contact_stage` no payload da lista (`GuestController::index`) e da ficha, calculado via `ContactStage`.
**Frontend:** badge de estágio na lista de convidados.
**Testes:** mapa de derivação (sem contato → nao_contatado; retorno no passado → retorno_vencido; etc.).
**Critério de pronto:** estágio correto em todos os casos, coberto por teste unitário do serviço.
**Dependências:** M1. **Conflito:** toca `GuestListQuery`/payload (hotspot) e `GuestMetrics` (se M5 depois).

### M3 — Fila de trabalho do operador · esforço M · risco MÉDIO
**Objetivo:** tela priorizada do que fazer agora.
**Backend:** método `GuestController::workQueue` + rota `GET events/{event}/work-queue` (`guests.work-queue`). Reusa `GuestListQuery` + ordena por prioridade: `nao_contatado` → `retorno_vencido` → `reconfirmacao_pendente` → resto. Filtros: responsável, categoria, estágio (via query string). Sem motor de workflow genérico.
**Frontend:** nova página `Pages/Guests/WorkQueue.vue` (reusa `PageHero`/`PageContainer`/tabela). Link na `Sidebar.vue`.
**Testes:** ordenação por prioridade; filtros; isolamento (CA-01); permissão operate.
**Critério de pronto:** fila ordenada e filtrável, testada.
**Dependências:** M2 (estágio). **Conflito:** `routes/web.php` + `Sidebar.vue` (hotspots).

### M5 — Relatório de operação · esforço S · risco BAIXO
**Objetivo:** denominadores claros no Dashboard.
**Backend:** estender `GuestMetrics` com: total da lista, contatados, sem resposta, confirmados, recusados, pendentes de retorno, reconfirmados. Documentar cada denominador (confirmação sobre elegíveis; comparecimento sobre confirmados).
**Frontend:** novo bloco no Dashboard (reusa StatCard).
**Testes:** contagens por estágio corretas; divisão por zero protegida (padrão já existente).
**Critério de pronto:** números batem com cenário de seed; denominadores explícitos.
**Dependências:** M2. **Conflito:** `GuestMetrics` (compartilhado com M2).

### M4 — Reconfirmação WhatsApp manual · esforço S · risco BAIXO
**Objetivo:** registrar reconfirmação por WhatsApp **sem** integração automática.
**Abordagem:** é um `GuestContact` com `channel=whatsapp` e outcome `reconfirmado` (ou outro). Botão/ação na ficha. Deixar explícito na UI que é registro manual.
**Testes:** registra contato whatsapp; aparece no histórico; reflete estágio `reconfirmado`.
**Critério de pronto:** registro manual funcional e auditado.
**Dependências:** M1. **Conflito:** mínimo (reusa M1).

### SEC/LGPD — Pré-requisitos de piloto real · esforço M · risco ALTO (segurança)
**Objetivo:** não levar dados reais sem proteções mínimas.
**Passos:**
1. **SEC-01:** remover `APP_KEY` literal do `docker-compose.yml`; exigir via ambiente; documentar geração por ambiente; (se já esteve em repo público) rotacionar. ⚠️ **Requer aprovação humana** se houver ambiente real em uso.
2. **Expurgo/retenção:** `retention_days` existe mas não há execução. Criar command `guests:purge` (ou job agendado) que anonimiza/elimina convidados e contatos de eventos encerrados além da retenção. Testar com Pest.
3. Varredura de segredos (gitleaks) no pipeline — opcional.
**Critério de pronto:** sem segredo hardcoded; expurgo testado; retenção executável.
**Dependências:** expurgo depende de M1 (também expurga contatos). **Conflito:** `docker-compose.yml` isolado.

---

## 5. Plano P1 (resumido — detalhar quando promovido)

| Item | Pré-condição de promoção | Esboço |
|---|---|---|
| **M7** Capacidade + lista de espera | Cliente confirma lotação limitada | Coluna `capacity` em `Event`; estado `espera`; promoção manual auditada. |
| **M8** WhatsApp API oficial | Volume/rotina justificam + consentimento/custo validados | Template, entrega, resposta e falha observáveis; não altera histórico de ligações; não automatiza decisão sem aprovação. Depende de M4. |
| **M9** Agendamento de reconfirmação | Fila (M3) em uso real | Lembrete interno + janela configurável antes do evento. Começa por tarefa, sem automação externa. |
| **M10** Acompanhante com limite por titular | Demanda real de limite | Limite por titular + status individual; representante segue papel distinto. Toca `GuestRelationshipService`. |
| **M11** QR individual para check-in | Processo de entrada justifica | Token por convidado + leitura; busca manual como fallback. Toca `CheckInService`. |
| **M12** Campos operacionais mínimos | Necessidade + política de dados confirmadas | Ex.: restrição alimentar. Evitar construtor genérico prematuro. |

## 6. Futuro — IA (fora do escopo atual)
Só após acumular fluxo/dados: priorizar pendências, resumir histórico, sugerir próxima ação, rascunhar mensagem. Nunca decidir confirmação ou comunicar sem supervisão. Não é requisito de piloto.

---

## 7. Ordem de execução sequencial (workflow baseado em evidências)

1. **M6** — consistência docs (XS, destrava confiança).
2. **M1** — `GuestContact` (fundação). *Validar enum de outcome com a cliente antes.*
3. **M2** — estágio de contato derivado.
4. **M3** — fila de trabalho.
5. **M5** — relatório de operação.
6. **M4** — reconfirmação WhatsApp manual.
7. **SEC-01 + LGPD/expurgo** — antes de qualquer dado real.
8. **(gate de validação com a cliente)** → promover itens P1 conforme evidência.

Cada passo em sua própria branch a partir da `dev` (ex.: `feature/m1-historico-contatos`), com ciclo completo de evidência e merge na `dev` só com Pest verde. Um item encerra antes do próximo começar — sem paralelismo, evitando conflito nos hotspots (`routes/web.php`, `GuestController`, `Sidebar`, `GuestMetrics`).

---

## 8. Riscos e mitigação

| Risco | Mitigação |
|---|---|
| Congelar enum de `outcome` sem validar vocabulário | Confirmar com a cliente antes de M1 (gate explícito). |
| Conflito nos hotspots se houver paralelismo | Execução sequencial obrigatória; cada item merge antes do próximo. |
| `GuestMetrics` tocado por M2 e M5 | Ordem M2→M5 garante base pronta antes do relatório. |
| Dados pessoais sem retenção executável (LGPD) | SEC/LGPD obrigatório antes de piloto com dados reais. |
| Alterar `RsvpStatus` para refletir contato | Evitado: M2 deriva estágio, não altera o enum de domínio. |
| `APP_KEY` hardcoded | SEC-01; rotacionar se exposto; aprovação humana se ambiente real. |

---

## 9. Resumo de esforço (ordem de grandeza)

| Item | Esforço | Risco | Caminho crítico? |
|---|---|---|---|
| M6 | XS | Baixo | não |
| M1 | M | Médio | **sim (fundação)** |
| M2 | S | Baixo | **sim** |
| M3 | M | Médio | **sim** |
| M5 | S | Baixo | sim |
| M4 | S | Baixo | não |
| SEC/LGPD | M | Alto (seg.) | não (mas obrigatório p/ piloto) |

P0 completo entrega o diferencial do produto (operação de confirmação ativa) sem sair do recorte de MVP.
