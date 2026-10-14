# Análise aprofundada — Relatório de Benchmark × Código real do MVP

**Branch:** `analise/benchmark-roadmap`
**Base analisada:** `dev` (frente de design/UX já integrada)
**Método:** confronto entre as afirmações do `Relatorio_Tecnico_RSVP_Benchmark_e_Roadmap.md` e o código-fonte real (o relatório declara-se "auditoria de documentação e superfície pública"; esta análise valida linha a linha).

---

## 1. O que o relatório propõe (resumo)

Reposiciona o produto de "sistema RSVP genérico" para **gestão de convidados de eventos privados com lista fechada e confirmação ativa pela equipe**. O canal primário é a **ligação**; o **WhatsApp** apoia a reconfirmação quando disponível. Autosserviço público, bilheteria e IA ficam fora desta versão.

A tese central: o MVP já tem um **núcleo de domínio forte** (estados RSVP, titular/acompanhante/representante, conflito, check-in idempotente, auditoria, importação), mas **não modela a operação humana de confirmação** (fila de trabalho, histórico de tentativas de contato, reconfirmação).

---

## 2. Confronto relatório × código (verificado)

| Afirmação do relatório | Verificação no código | Veredito |
|---|---|---|
| Domínio separa pessoa, RSVP, papel e presença | `Guest` tem `rsvp_status`, `participation_role`, `attendance_status` como enums distintos (OBSERVED em `app/Models/Guest.php`, `app/Enums/`) | **SUPPORTED** |
| Promoção acompanhante→representante + conflito bloqueante | `RsvpService::promoteCompanionIfNeeded` e `flagConflictIfRepresentedHostConfirms` (OBSERVED) | **SUPPORTED** |
| Check-in idempotente com autor/horário | `CheckInService::checkIn` com `lockForUpdate` e guarda de idempotência (OBSERVED) | **SUPPORTED** |
| Autorização por evento | `EventPolicy` + `EventRole` (view/operate/manageGuests/export/administer) (OBSERVED) | **SUPPORTED** |
| Importação com prévia e exportação protegida contra fórmula | `Services/Import/*` e `GuestExcelExporter::guardFormula` (OBSERVED) | **SUPPORTED** |
| **Não há fila de ligações nem histórico de tentativas de contato** | `grep` por `contato\|contact\|tentativa\|channel\|whatsapp\|canal` em `app/` e `database/` → **NENHUM**. Tabela `guests` guarda só estado final (`rsvp_status`, `note`), sem registro cronológico | **SUPPORTED (lacuna confirmada)** |
| Não há integração/registro de WhatsApp | Nenhum vestígio no código (OBSERVED) | **SUPPORTED (lacuna confirmada)** |
| Não há RSVP público/autosserviço | `routes/web.php`: todas as rotas de evento sob `middleware('auth')`; landing é institucional | **SUPPORTED (e é intencional nesta versão)** |
| Inconsistência de numeração dos CA (README vs ARCHITECTURE) | README cita CA no texto-resumo; ARCHITECTURE lista CA-01..CA-14. Há CA-12 (responsividade) sem teste automatizado dedicado | **PARTIALLY_SUPPORTED** |
| PHP declarado `^8.3` | `composer.json`: `"php": "^8.3"`; README/stack diz "PHP 8.4" | **Divergência menor confirmada** |

**Conclusão do confronto:** o relatório é **preciso e honesto**. A maior lacuna que ele aponta — ausência de modelagem da operação de confirmação ativa — é real e verificável: o sistema hoje só guarda o **estado final** do RSVP, não o **processo** que levou a ele.

---

## 3. Gap central: estado final × processo de confirmação

Hoje o fluxo é: `pendente → (confirmar|recusar|reconsiderar)`. Isso responde "qual a resposta?", mas **não** responde as perguntas operacionais do relatório (§2):

- Quem já foi contatado e quem falta? (não há `last_contacted_at` nem estado "não contatado" vs "pendente")
- Quantas tentativas, por qual canal, por qual operador, com qual resultado? (não há tabela de contatos)
- O que precisa ser feito agora? (não há fila priorizada por retorno vencido / reconfirmação pendente)

O `AuditLog` registra `guest.confirmed` etc., mas é **append-only de sistema**, não uma **ferramenta operacional** de acompanhamento (não tem resultado estruturado, retorno agendado, nem é a fonte da fila de trabalho).

---

## 4. Oportunidades de melhoria priorizadas (viáveis no MVP)

Alinhadas ao P0 do relatório, com blast radius controlado e aproveitando o que já existe.

### M1 — Histórico de contatos (fundação da confirmação ativa) · ALTO valor
Nova entidade `GuestContact` (migration + model): `guest_id`, `event_id`, `channel` (ligacao|whatsapp|outro), `outcome` (sem_resposta|retorno_agendado|confirmou|recusou|contato_invalido|reconfirmado), `scheduled_return_at` (nullable), `note`, `operator_id`, `created_at`. Append-only, isolado por evento (CA-01).
- Aproveita: `AuditLogger`, `EventPolicy::operate`, padrão de `guardOwnership`.
- Risco: MÉDIO (nova tabela + relação). Totalmente testável em Pest.

### M2 — Estado de contato distinto do RSVP · ALTO valor
Derivar/registrar "não contatado" vs "pendente (contatado, aguardando retorno)". Opção conservadora: **não** alterar o enum `RsvpStatus`; derivar o estágio de contato de `GuestContact` (último `outcome` + `scheduled_return_at`). Mantém o domínio RSVP intacto e evita migração destrutiva.
- Risco: BAIXO se derivado; MÉDIO se virar coluna.

### M3 — Fila de trabalho do operador · ALTO valor
Nova aba/consulta reutilizando `GuestListQuery`: ordenar por prioridade (não contatados → retornos vencidos → reconfirmações pendentes), com filtros por responsável/categoria/estágio. Sem motor de workflow genérico (o relatório alerta contra isso).
- Aproveita: estação de check-in e `PageHero`/`PageContainer` já padronizados.
- Risco: MÉDIO.

### M4 — Registro manual de reconfirmação por WhatsApp · MÉDIO valor
Ação na ficha do convidado para registrar contato/resultado por WhatsApp (sem integração automática — explicitamente fora do escopo). É um `GuestContact` com `channel=whatsapp`.
- Risco: BAIXO (reusa M1).

### M5 — Relatório de operação · MÉDIO valor
Estender `GuestMetrics`/Dashboard com denominadores claros: total da lista, contatados, sem resposta, confirmados, recusados, pendentes de retorno, reconfirmados. O relatório pede denominadores explícitos.
- Aproveita: Dashboard premium já existente.
- Risco: BAIXO.

### M6 — Correções de consistência documental · BAIXO esforço
- Alinhar `composer.json` (`^8.3`) com o README (PHP 8.4) — decidir a versão-alvo real.
- Reconciliar numeração CA-01..CA-14 entre README e ARCHITECTURE; marcar CA-12 (responsividade) como validação manual até ter teste.
- Risco: BAIXO (só docs/config).

### M7 — Capacidade e lista de espera · condicional (P1)
Só se a cliente confirmar lotação limitada. `Event` já tem `estimated_audience`; faltaria `capacity` real + espera. Não construir sem validação.

---

## 5. O que NÃO fazer agora (confirmado pelo relatório + código)
- RSVP público/autosserviço: contraria o posicionamento e exigiria rotas fora de `auth`.
- Integração automática de WhatsApp/e-mail: exige consentimento, custo, entregabilidade (SPF/DKIM/DMARC) — P1, só com volume validado.
- IA: versão futura.
- Billing/multi-tenant: P2.

---

## 6. Riscos transversais a tratar (do §7 do relatório)
- **LGPD:** convidados + histórico de contato são dados pessoais. `retention_days` é configurável mas **não há expurgo automático** (verificar/implementar). Separação de acesso por papel já existe (`EventPolicy`).
- **SEC-01 (já no backlog):** `APP_KEY` hardcoded no `docker-compose.yml` — remover/rotacionar antes de qualquer piloto real.
- **Export:** proteção contra injeção de fórmula já implementada (`guardFormula`) — manter.

---

## 7. Sequência recomendada para um piloto demonstrável
1. **M6** (consistência docs — rápido, destrava confiança na matriz CA).
2. **M1 + M2** (fundação: histórico de contatos + estágio derivado).
3. **M3** (fila de trabalho — o diferencial operacional).
4. **M5** (relatório com denominadores).
5. **M4** (reconfirmação WhatsApp manual).
6. **SEC-01 + LGPD** (expurgo/retenção) antes de dados reais.

Cada item: migration/serviço cirúrgico → Pest → build + rebuild do container → commit. Validar vocabulário de estados com a cliente **antes** de congelar enums (recomendação explícita do relatório, P0 item 1).

---

## 8. Veredito

O relatório é sólido e o código **confirma** seu diagnóstico. O MVP tem um núcleo de domínio de alta qualidade e bem testado (114 testes verdes na `dev`), e a evolução natural não é adicionar features sociais ou de bilheteria, mas **transformar o registro de estado final numa ferramenta de operação de confirmação ativa** — fila, histórico de tentativas e relatório. É um incremento coerente com a arquitetura existente, de blast radius controlado, e que materializa o diferencial competitivo apontado (operação nominal confiável).
