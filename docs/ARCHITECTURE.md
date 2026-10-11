# Arquitetura

Monólito modular Laravel, com regras de domínio isoladas em **serviços** e
autorização **por evento**. A premissa central é não confundir pessoas,
confirmações, relações de representação e presenças.

---

## Modelo de domínio (7 entidades)

| Entidade | Responsabilidade |
|---|---|
| `User` | Identidade e autenticação. |
| `Event` | Configurações e ciclo de vida (rascunho/ativo/encerrado); contador de numeração. |
| `EventUser` | Permissão de um usuário **em um evento** (pivot com papel). |
| `GuestCategory` | Categorias de convidados por evento (configuráveis). |
| `Guest` | Identificação e estados do convidado (RSVP, papel, presença). |
| `GuestRelationship` | Relações de acompanhamento/representação entre convidados. |
| `AuditLog` | Histórico append-only de operações relevantes. |

### Diagrama de relações

```
User ──< EventUser >── Event ──< GuestCategory
                         │
                         ├──< Guest >── GuestCategory
                         │       │
                         │       └──< GuestRelationship >── Guest (titular)
                         │
                         └──< AuditLog >── User
```

### Enums (fonte da verdade dos estados) — `app/Enums/`

- `RsvpStatus`: `pendente` · `confirmado` · `nao_participara`
  (método `fromImportFlag`: `1 ⇒ Confirmado`, qualquer outro ⇒ `Pendente` — **CA-03**)
- `ParticipationRole`: `titular` · `acompanhante` · `representante`
- `AttendanceStatus`: `aguardando` · `presente`
- `RelationshipType`: `acompanhante` · `representante` (com `origin_type` preservando a origem)
- `EventStatus`: `rascunho` · `ativo` · `encerrado`
- `EventVisibility`: `publico` · `reservado`
- `EventRole`: `administrador` · `gestor` · `operador` · `leitor`

---

## Autorização por evento (`EventPolicy`)

Toda decisão depende do papel do usuário **naquele** evento (via `EventUser`).
Um usuário sem vínculo é tratado como não autorizado — garantindo isolamento (**CA-01**).

| Ability | Administrador | Gestor | Operador | Leitor |
|---|:---:|:---:|:---:|:---:|
| `view` | ✓ | ✓ | ✓ | ✓ |
| `operate` (confirmar/recusar/check-in/resolver conflito) | ✓ | ✓ | ✓ | — |
| `manageGuests` (CRUD, importar) | ✓ | ✓ | — | — |
| `export` | ✓ | ✓ | — | — |
| `administer` (encerrar/reabrir, permissões, auditoria) | ✓ | — | — | — |

O isolamento é reforçado nos controllers por `guardOwnership` (impede acesso
cruzado via manipulação de IDs na URL → 404) e `guardOpen` (bloqueia alterações
em evento encerrado → 403, **CA-14**).

---

## Serviços de domínio — `app/Services/`

| Serviço | Responsabilidade | CA |
|---|---|---|
| `RsvpService` | Máquina de estados RSVP; promoção acompanhante→representante; detecção de conflito. | CA-03, CA-05, CA-06 |
| `GuestRelationshipService` | Criação de vínculos com invariantes (não-self, sem ciclo, mesmo evento, único por dependente, 1 representante ativo por titular). | §2.5 |
| `GuestMetrics` | Indicadores sem dupla contagem; taxas com proteção contra divisão por zero. | CA-04, CA-07 |
| `CheckInService` | Credenciamento idempotente (lock + checagem de estado). | CA-09 |
| `GuestNumberAllocator` | Numeração sequencial atômica por evento (`lockForUpdate`), estável e não reutilizada. | CA-13 |
| `GuestWriter` | Criação/edição com categoria obrigatória do evento. | CA-02 |
| `GuestListQuery` | Busca/filtros/ordenação — reutilizado por lista **e** exportações. | — |
| `AuditLogger` | Registro append-only de operações. | CA-10 |
| `Import/*` | `ImportParser` (respeita aspas), `ImportFileReader` (xlsx/csv/txt/md), `ImportAnalyzer` (validação/vínculos/ambiguidade), `ImportPersister` (transacional). | CA-08 |
| `Export/*` | `GuestExcelExporter` (guarda anti-fórmula), `GuestPdfExporter` (dompdf premium). | CA-11 |

---

## Regras-chave

### Promoção acompanhante → representante (CA-05)
Ao confirmar um **acompanhante** cujo **titular não está confirmado**, o vínculo
muda `type` para `representante`, mas `origin_type` permanece `acompanhante`
(a origem é preservada e rastreável). O papel do convidado passa a `representante`.

### Conflito de representação (CA-06)
Se o **titular representado** confirma depois, e há representante ativo confirmado,
o vínculo é marcado `is_conflict = true` (bloqueante) — **sem alterar nada silenciosamente**.
A resolução é explícita (operador escolhe titular **ou** representante); o lado não
escolhido passa a `nao_participara`. A resolução é auditada.

### Contagem sem dupla contagem (CA-07)
`expected_participants = titulares + acompanhantes + representantes confirmados`,
cada pessoa contada uma única vez pelo seu papel atual. Um acompanhante promovido
conta apenas em representantes.

### Numeração estável (CA-13)
`events.guest_number_seq` é um contador monotônico incrementado sob `lockForUpdate`,
imune a corrida e a exclusões lógicas (números não são reutilizados).

---

## Critérios de aceite (CA-01 … CA-14)

| CA | Descrição | Cobertura de teste |
|---|---|---|
| CA-01 | Isolamento entre eventos | `EventAuthorizationTest`, `GuestListTest` |
| CA-02 | Categoria obrigatória | `GuestNumberingTest`, `GuestListTest` |
| CA-03 | `0` importado ≠ recusa | `GuestDomainTest` |
| CA-04 | Titular + acompanhante = 2 previstos | `GuestDomainTest`, `DashboardTest` |
| CA-05 | Promoção a representante | `GuestDomainTest`, `GuestImportTest` |
| CA-06 | Conflito bloqueante + resolução | `GuestDomainTest`, `AuditTest` |
| CA-07 | Sem dupla contagem | `GuestDomainTest`, `DashboardTest` |
| CA-08 | Importação com pré-visualização | `GuestImportTest` |
| CA-09 | Check-in idempotente | `GuestListTest` |
| CA-10 | Auditoria de operações | `AuditTest` |
| CA-11 | Exportação com permissão/filtros/anti-fórmula | `GuestExportTest` |
| CA-13 | Numeração estável/não reutilizada | `GuestNumberingTest` |
| CA-14 | Encerramento bloqueia; reabertura auditada | `GuestListTest`, `AuditTest` |

> CA-12 (responsividade) é validado manualmente nas três classes de dispositivo.

---

## Decisões (produto §14 do PRD)

- **Eventos públicos:** somente visualização institucional (sem inscrição espontânea no MVP).
- **Titular + representante confirmados:** flag de conflito bloqueante, resolução manual.
- **Walk-in no credenciamento:** não suportado no MVP.
- **Nomes iguais:** permitidos; identidade por ID (duplicidade gera apenas aviso na importação).
- **Retenção de dados:** campo `retention_days` configurável por evento, sem expurgo automático no MVP.
