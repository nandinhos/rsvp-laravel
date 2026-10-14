# Relatório técnico — Benchmark e evolução do RSVP

**Projeto:** RSVP — gestão de convidados, confirmações e presença  
**Repositório analisado:** `nandinhos/rsvp-laravel` (branch `main`)  
**Data da pesquisa:** 14/10/2026  
**Objetivo:** avaliar o recorte de produto e a base existente, identificar oportunidades reais de diferenciação e propor uma evolução demonstrável para uma potencial cliente, preservando foco de MVP.

## 1. Síntese executiva

RSVP não é apenas uma resposta de “sim ou não”. É o processo de solicitar e registrar a resposta a um convite para planejar o evento com dados confiáveis. O Dictionnaire de l’Académie française define R.S.V.P. como *Répondez s’il vous plaît* e como a indicação no convite para que o destinatário confirme presença. Na operação digital, a resposta se conecta à identidade do convidado, aos limites de acompanhantes, à capacidade, à comunicação e ao comparecimento real.

O MVP já resolve uma parte difícil: semântica de estados e vínculos entre pessoas. O README declara confirmação distinta de presença, acompanhante/representante, conflitos explícitos, check-in idempotente, isolamento por evento, importação com prévia, exportações, indicadores, auditoria e ciclo de vida. A arquitetura descreve serviços, políticas por evento e critérios de aceite. É uma base de produto consistente, não somente um CRUD.

A necessidade central deste produto é apoiar a **confirmação ativa feita pela equipe**. A lista de convidados é fechada: o operador entra em contato por ligação, registra a resposta e, quando houver WhatsApp disponível, faz a reconfirmação por esse canal. O sistema deve organizar o trabalho, preservar o histórico de contatos e deixar claro quem ainda precisa ser contatado. RSVP público/autosserviço e venda de ingressos não fazem parte desta versão. IA é possibilidade para uma versão futura, fora do roadmap de implementação atual.

**Posicionamento definido:** sistema de **gestão de convidados para eventos privados com lista fechada e confirmação ativa**, voltado à equipe responsável pela operação. A ligação é o canal de confirmação; o WhatsApp, quando disponível, apoia a reconfirmação. O sistema registra o processo e organiza pendências; não pressupõe disparos ou respostas automáticas.

O diferencial a demonstrar é a **organização confiável da operação humana de confirmação**, com acompanhamento por convidado, histórico auditável, relações de titulares/acompanhantes/representantes e visão clara do que falta fazer.

## 2. O que RSVP significa para o produto

A tradução “responda, por favor” está correta. Neste produto, a resposta não é presumida como autoatendimento: a equipe busca ativamente o convidado por ligação e pode reconfirmar pelo WhatsApp quando disponível. Portanto, o sistema precisa responder, de forma auditável:

1. **Quem foi convidado?** Pessoa, grupo familiar, organização, categoria, responsável pelo convite.
2. **Qual convite recebeu?** Evento/sessão, canal, prazo e link ou credencial.
3. **Qual resposta deu?** Confirmou, recusou, pediu retorno, não atendeu, contato inválido ou ainda não contatado; registrar data, canal e responsável. Reconfirmação por WhatsApp precisa ter estado próprio.
4. **Quem mais está incluído?** Acompanhantes e representantes, com relação e limite claramente definidos.
5. **A resposta cabe na operação?** Capacidade, lista de espera, refeições/restrições, sessão ou acessibilidade quando aplicável.
6. **Quem compareceu de fato?** Check-in, horário, operador/dispositivo e tratamento de duplicidade.
7. **O que o operador precisa fazer agora?** Próximas ligações, retornos agendados, convidados sem reconfirmação, mensagens WhatsApp pendentes e relatórios confiáveis.

Mantenha separados **pessoa**, **convite/inscrição**, **resposta RSVP** e **presença**. O MVP já explicita RSVP ≠ presença; esse princípio precisa permanecer.

### Vocabulário recomendado

- **Convidado:** pessoa vinculada ao evento.
- **Convite:** registro de que a pessoa integra a lista fechada do evento.
- **Contato:** tentativa feita pela equipe por determinado canal, com resultado e responsável.
- **RSVP / resposta:** intenção declarada pelo convidado e registrada pelo operador.
- **Reconfirmação:** novo contato, especialmente por WhatsApp quando disponível, para validar que a intenção permanece válida.
- **Acompanhante:** pessoa adicional autorizada pela inscrição do titular.
- **Representante:** pessoa que comparece no lugar de outra, segundo regra explícita.
- **Presença / check-in:** confirmação operacional de entrada, não inferida do RSVP.

A distinção entre acompanhante e representante é uma força própria do domínio. A promoção de acompanhante para representante, preservando origem e sinalizando conflito se o titular também confirmar, endereça casos que plataformas sociais simples não modelam com a mesma precisão.

## 3. Benchmark: referências e capacidades observadas

A amostra não declara um “melhor sistema universal”. Cada produto atende segmento diferente. Capacidades verificadas em páginas oficiais, documentação de ajuda ou descrição oficial; recursos de marketing devem ser confirmados em demonstração antes de serem tratados como evidência de operação.

| Referência | Segmento / força | Capacidades observadas | Aprendizado para o RSVP |
|---|---|---|---|
| **Partiful** | Convites sociais, rápida adoção | Página de evento, RSVP simples, compartilhamento por texto, e-mail, Instagram e WhatsApp, lembretes, cohosts, aprovação, limite de acompanhantes, capacidade e waitlist; evolui para ingressos e QR check-in. | Responder precisa ser fácil no celular. Link sem exigir instalação reduz fricção. Recursos sociais e visuais também fazem parte do produto. |
| **Eventbrite** | Descoberta e venda de ingressos | Gestão de eventos/inscrições, lista de convidados, app do organizador, check-in por app e lista, papéis/permissões da equipe e venda de ingressos. | Referência para operação em campo e equipe; menos direta para convite nominal fechado. |
| **Cvent** | Eventos corporativos/enterprise | Formulários e fluxos de inscrição, tipos de participantes, caminhos condicionais, aprovações, edição/cancelamento, sessões, integração com seating e controles enterprise. | Segmentação e fluxos por participante importam em eventos complexos; a amplitude é excessiva para o MVP atual. |
| **RSVPify** | Plataforma abrangente | Sites e formulários, questões e tags customizadas, colaboradores, convites, comunicação, check-in, seating, badges, pagamentos/tickets e relatórios. | Demonstra o valor percebido de uma jornada coerente; liberar recursos por demanda. |
| **RSVP.com.br** | Gestão nominal corporativa no Brasil | RSVP e gestão de convidados, importação em escala, integrações/webhooks, WhatsApp corporativo, QR check-in em múltiplos pontos, reconciliação após instabilidade, acompanhantes, espera e regras de evento fechado. | Referência local mais próxima do posicionamento proposto. Resiliência de credenciamento, identidade do remetente e operação nominal são diferenciais concretos. |
| **Sympla** | Ingressos e inscrições no Brasil | Venda de ingressos, app do organizador, check-in manual ou QR code, credenciamento e acompanhamento de vendas. | Boa referência para check-in e autoatendimento; marketplace/ticketing é outro modelo. |

### Leitura estratégica para este produto

- O benchmark serve para comparar padrões e entender o mercado, mas o produto não deve imitar convites públicos, marketplace ou autosserviço de RSVP.
- A referência competitiva mais próxima é a gestão nominal corporativa, com operação de equipe, trilha de contato e credenciamento.
- Nesta versão, WhatsApp é um canal de reconfirmação **quando disponível**; o sistema deve permitir registrar contato/resposta manualmente. Integração oficial automatizada fica para decisão futura e exige validar regras, consentimento, custo e operação.
- “Premium” significa tornar o trabalho do operador evidente e rápido: fila priorizada, registro sem fricção, histórico legível, boa tipografia, acessibilidade e feedback consistente.

## 4. Auditoria documental do MVP

### Evidências consultadas

- `README.md`: proposta, funcionalidades, stack, estrutura, perfis, critérios de aceite e execução.
- `docs/ARCHITECTURE.md`: entidades, enums, autorização, serviços, regras de domínio e decisões.
- `docs/DEVELOPMENT.md`: fluxo de trabalho, testes e convenções.
- `routes/web.php`: rotas de eventos, convidados, importação, exportação, conflitos, auditoria e check-in.
- `composer.json`: Laravel 13, PHP declarado `^8.3`, Inertia, Sanctum, PhpSpreadsheet, Dompdf e Pest.

Esta é uma **auditoria de documentação e superfície pública do repositório**, não uma revisão linha a linha de componentes, nem execução dos testes ou do produto. A documentação declara cobertura automatizada; o resultado atual da suíte não foi reexecutado nesta análise.

### Capacidades documentadas — aproveitar, não reescrever

| Área | Estado documentado | Avaliação |
|---|---|---|
| Domínio de convidados | Pessoa, evento, categoria, RSVP, papel e presença separados conceitualmente | Boa base e central para integridade de métricas. |
| Acompanhantes e representantes | Vínculos, promoção acompanhante→representante, conflito bloqueante e resolução auditada | Diferencial do domínio; merece UX exemplar e demonstração. |
| Segurança multi-evento | `EventUser`, papéis por evento, `EventPolicy`, ownership validado no servidor | Base necessária. Validar em testes e UI antes de apresentar como garantia operacional. |
| Presença | Check-in idempotente, autor e horário | Bom núcleo; documentar experiência e resposta a falha de rede. |
| Importação/exportação | Prévia, análise de erros/avisos, importação transacional, Excel/PDF | Alto valor para agências e clientes com listas existentes. |
| Auditoria/ciclo de vida | Audit log append-only, rascunho/ativo/encerrado, reabertura auditada | Fundamento de confiança em eventos corporativos. |
| Dashboard | Indicadores de resposta/comparecimento sem dupla contagem | Diferencial se denominadores forem explícitos e úteis. |
| Tecnologia/design | Laravel 13, Inertia/Vue 3, Tailwind, componentes UI e landing | Stack adequada. “Premium” depende do fluxo e refinamento visual, não da stack. |

### Lacunas e hipóteses a validar

| Tema | Evidência observada | Consequência / pergunta para demo |
|---|---|---|
| Fluxo de confirmação ativa | Rotas examinadas têm ações para confirmar/recusar, mas os documentos não mostram fila de ligações nem histórico detalhado de tentativas | O operador deve conseguir registrar contato, resultado, retorno e responsável. Validar no código se hoje há histórico ou somente estado final. |
| Reconfirmação por WhatsApp | Não há envio/integração WhatsApp documentado nas fontes do repositório examinadas | Necessidade definida: reconfirmar por WhatsApp quando disponível. Para esta versão, prever registro manual do canal e resultado; integração automática é futura e depende de validação. |
| RSVP público/autosserviço | Rotas examinadas não incluem página pública de resposta; evento público é somente visualização institucional | Não é lacuna desta versão: lista fechada e confirmação conduzida pela equipe são requisitos do produto. |
| Disparo de convites e lembretes automáticos | Não documentados | Fora do escopo atual, salvo confirmação da cliente de que a equipe precisa de envio integrado; não presumir automação. |
| Capacidade/lista de espera | Arquitetura declara walk-in não suportado; capacidade/espera não aparece nas decisões consultadas | Importante com lotação. Capacidade total + espera e promoção manual bastam para validar. |
| Acompanhante como inscrição individual | `GuestRelationship` existe; não está documentada a coleta pública, consentimento ou dados mínimos | Confirmar identidade, resposta e credencial próprias e campos obrigatórios. |
| Visibilidade vs inscrição | `EventVisibility` público/reservado; público é somente visualização institucional | Explicitar comportamento; não confundir página visível com autorização para se inscrever. |
| Check-in em campo | Check-in existe; QR, app/PWA, offline e múltiplos postos não estão documentados | Pode bastar para evento pequeno. Demonstrar antes; QR e offline são incrementos distintos. |
| Dados personalizados | Categorias existem; perguntas/refeições/acessibilidade/sessões não aparecem na arquitetura | Perguntar qual dado é indispensável; evitar construtor genérico prematuro. |
| Métricas | Dashboard existe, mas fórmulas e denominadores não estão catalogados no material lido | Definir: resposta sobre elegíveis, comparecimento sobre confirmados, conversão sobre convidados totais. |
| SaaS/multi-organização | Isolamento por evento documentado; organização/tenant, plano, billing e onboarding não aparecem | Não bloqueia demo. Decidir antes de comercializar para múltiplos clientes, sem construir billing agora. |
| Privacidade/retenção | `retention_days` é configurável, sem expurgo automático | Configuração sem execução não é política completa. Definir finalidade, acesso, descarte/anonimização e atendimento a titulares. |
| Acessibilidade/visual | Responsividade é validação manual; design system descrito | Fazer QA de teclado, contraste, foco e mobile; capturas reais sustentam a alegação premium. |

**Precisão de status:** há possível inconsistência documental. README cita critérios CA-01 a CA-14, mas arquitetura lista CA-01 a CA-11 e CA-13/14, deixando CA-12 fora e atribuindo numeração estável a CA-13. Alinhar identificadores e testes reais antes de usar a matriz de aceite como prova comercial.

## 5. Posicionamento recomendado

### Proposta de valor

> **Uma plataforma clara e confiável para controlar convites, respostas e presença em eventos fechados — com visibilidade sobre titulares, acompanhantes e representantes, da lista inicial ao credenciamento.**

### Cliente inicial a validar

Hipóteses coerentes com o MVP: agências/assessorias que operam listas nominais; empresas com convenções, lançamentos, eventos internos e encontros de clientes; instituições que precisam classificar convidados, distribuir permissões e gerar presença; eventos protocolares em que representante não é sinônimo de acompanhante.

### Fronteira competitiva

| Atender nesta versão | Fora desta versão |
|---|---|
| Eventos privados com lista fechada | Inscrição pública/autosserviço do convidado |
| Confirmação ativa por ligação registrada pela equipe | Marketplace e descoberta pública de eventos |
| Reconfirmação por WhatsApp quando disponível, inicialmente com registro do contato | Bilheteria, lotes, checkout, reembolso e chargeback |
| Importação de listas, gestão de contatos, acompanhantes/representantes e status | CRM completo e campanhas de marketing multicanal |
| Fila de trabalho, histórico de tentativas, retornos e relatórios operacionais | Agenda de congresso, hotelaria, viagens, networking e app de participantes |
| Check-in e exportação existentes, sujeitos à validação do MVP | IA, agentes autônomos e respostas automáticas nesta versão |

## 6. Roadmap priorizado

### P0 — Demonstração e piloto assistido

1. **Modelar o ciclo de contato do operador.** Exibir estados simples e distintos: não contatado, tentativa sem resposta, retorno agendado, confirmou, recusou, contato inválido e reconfirmação pendente/concluída. Validar vocabulário com a cliente antes de congelar enums.
2. **Registrar cada tentativa.** Guardar convidado, evento, canal (ligação/WhatsApp/outro se necessário), instante, operador, resultado e observação objetiva; histórico cronológico auditável, sem sobrescrever tentativas anteriores.
3. **Fila de trabalho.** Priorizar não contatados, retornos vencidos e reconfirmações pendentes; permitir busca/filtros por categoria, responsável e status. Não criar motor de workflow genérico.
4. **Reconfirmação manual por WhatsApp.** Para esta versão, registrar que a equipe enviou/recebeu contato e o resultado, quando houver WhatsApp disponível. Não prometer disparo ou leitura automática.
5. **Fluxo demonstrável e sem atrito.** Evento fictício, equipe preparada e sequência: importar → ligar e registrar resultado → agendar retorno → confirmar → reconfirmar por WhatsApp → resolver caso de representante → check-in → relatório.
6. **Relatório de operação.** Mostrar total da lista, contatados, tentativas sem resposta, confirmados, recusados, pendentes de retorno e reconfirmados, além de presença quando aplicável; deixar denominadores claros.
7. **Polir fluxos críticos.** Busca rápida, filtros, estados vazios, mensagens claras, feedback e navegação mobile/desktop.
8. **Revalidar regras e segurança.** Executar e registrar Pest, Pint e build; conferir autorização, importação, numeração, check-in repetido, evento encerrado, conflitos e acesso ao histórico.
9. **Ambiente de demo seguro.** Dados fictícios; credenciais padrão estritamente isoladas em desenvolvimento/demo, nunca em produção.

### P1 — Evolução após validar operação real

1. **Integração oficial com WhatsApp Business**, caso a cliente confirme que volume e rotina justificam: template, consentimento/base aplicável, custo, entrega, resposta e falha precisam ser observáveis. A integração não deve alterar o histórico de chamadas nem tornar a resposta automática sem aprovação.
2. **Agendamento de reconfirmação**, com lembrete interno para operador e janela configurável antes do evento. Começar por fila/tarefa, sem automação externa.
3. **Acompanhantes com limite por titular e status individual**, mantendo representantes como papel distinto.
4. **Capacidade e lista de espera** somente se houver limite de lotação; regras simples e promoção manual auditada.
5. **QR individual para check-in**, com busca manual como fallback, se o processo de entrada justificar.
6. **Campos operacionais mínimos** (por exemplo, restrição alimentar) só após confirmação da necessidade e da política de dados.

### Versão futura — IA (fora do escopo atual)

Avaliar IA somente após acumular fluxo e dados operacionais suficientes, com casos de uso concretos: priorizar pendências, resumir histórico, sugerir próxima ação ou preparar rascunho de mensagem. Não delegar decisão de confirmação, interpretação de respostas ambíguas ou comunicação automática sem supervisão e autorização. Nenhum componente de IA é requisito para o piloto desta versão.

### P2 — Escala comercial após recorrência

Organizações/contas e segregação multi-tenant; onboarding; API/webhooks versionados e assinados; mensuração de canais; relatórios entre eventos. Recursos de ingressos, campanhas amplas e módulos de conferência permanecem fora da estratégia definida, salvo mudança explícita de segmento.

**Regra de priorização:** um item P1 sobe quando a cliente confirma frequência, dor atual, alternativa, custo/risco e critério de sucesso. Interesse verbal numa feature não substitui validação de fluxo e disposição de uso/pagamento.

## 7. Requisitos transversais

### Segurança e privacidade

Convidados e o histórico de contato são dados pessoais. A LGPD estabelece princípios e exige medidas técnicas e administrativas de segurança desde a concepção, direitos do titular e término/eliminação do tratamento nos limites aplicáveis. Para o produto:

- coletar apenas dados necessários para convite, operação e relatório;
- explicar finalidade e papéis de controlador/operador na contratação e no fluxo do convidado;
- separar acesso do operador de check-in, gestor e administrador;
- nunca expor lista por link público, ID sequencial ou export sem autorização;
- definir retenção e descarte/anonimização executáveis, inclusive cópias e exportações;
- auditar sem registrar dados pessoais excessivos;
- documentar backup, acesso administrativo, resposta a incidente e demo fictícia.

Este relatório não substitui análise jurídica de papéis, bases legais, contratos ou retenção específicos do cliente.

### Comunicação por e-mail

Antes de prometer alta entregabilidade, validar provedor, domínio, SPF/DKIM/DMARC, bounce, descadastro quando aplicável, limites e métricas. Gmail mantém requisitos de autenticação para remetentes e exigências adicionais para envio em volume.

### Confiabilidade

- RSVP, promoção e check-in idempotentes quando repetidos;
- auditoria com ator, instante, ação e alteração essencial;
- transações nos limites de consistência;
- tratamento claro de concorrência e estado inválido;
- logs e monitoramento sem vazamento de dados pessoais;
- export protegida contra injeção de fórmula, já prevista no MVP;
- procedimento de recuperação para importação e operação no evento.

### Experiência premium

- ação principal clara por tela e hierarquia consistente;
- mensagens na linguagem do usuário com próximo passo;
- uso rápido no celular por convidado e operador;
- tabelas densas e escaneáveis no desktop;
- teclado, foco, labels e contraste acessíveis;
- identidade visual consistente, sem depender de animação decorativa;
- teste com organizador real: criar evento, importar 100 pessoas, encontrar convidado, corrigir resposta e credenciar sem fricção.

## 8. Roteiro de demonstração (8–12 minutos)

1. **Contexto (1 min):** a equipe recebe lista fechada e precisa confirmar ativamente cada presença, acompanhar pendências e chegar ao evento com números confiáveis.
2. **Importação (1–2 min):** prévia, erros/duplicidades e confirmação antes de gravar.
3. **Ligação (2 min):** abrir convidado, registrar tentativa e resultado; agendar retorno se não houve resposta. Mostrar responsável e histórico.
4. **Reconfirmação WhatsApp (1 min):** registrar contato/reconfermação quando disponível, sem simular integração que não existe.
5. **Exceção de vínculo (1–2 min):** demonstrar representante/acompanhante e resolução explícita de conflito.
6. **Painel e operação (2 min):** fila de pendências, relatórios por status e denominadores; demonstrar check-in e permissões se houver tempo.
7. **Descoberta (2–4 min):** entender processo atual, volume, janela de ligações, reconfirmação, equipe, exceções e relatório.

### Perguntas para qualificar a oportunidade

- Quais tipos de evento, volumes e frequência?
- A lista já existe ou o público se inscreve sozinho?
- Quem recebe a lista e quem faz as ligações?
- Quantas tentativas são feitas e como se decide quando encerrar ou retornar?
- Quais resultados precisam ser registrados (não atendeu, pediu retorno, confirmou, recusou etc.)?
- Quando e para quem é feita a reconfirmação por WhatsApp? O número está disponível para todos?
- Como se evita que duas pessoas da equipe liguem para o mesmo convidado?
- Como lidam com acompanhante, substituição/representante e mudança de resposta?
- Qual relatório operacional a equipe precisa antes do evento?
- Check-in exige vários pontos ou precisa lidar com rede instável?
- Quais dados são necessários e por quanto tempo?
- O que define sucesso do piloto e em qual data/evento?

## 9. Saída para piloto

- [ ] Cliente, evento, volume, data e responsável do piloto definidos.
- [ ] Fluxo de ligação e registro de resultado validado com quem opera a lista.
- [ ] Reconfirmação por WhatsApp definida para casos em que o canal estiver disponível.
- [ ] Fila de trabalho, responsável, retorno e histórico demonstrados ou lacunas assumidas.
- [ ] Papéis e permissões testados no navegador e servidor.
- [ ] Testes e build executados; resultados registrados.
- [ ] Importação/exportação validadas com arquivo representativo minimizado.
- [ ] Dados e credenciais de demonstração fictícios e isolados.
- [ ] Suporte e contingência do dia definidos.
- [ ] Privacidade, retenção e canais alinhados.
- [ ] Escopo, exclusões e critério de sucesso registrados.

## 10. Conclusão

O MVP já possui núcleo de domínio útil para eventos privados: lista nominal, estados RSVP, relações de titular/acompanhante/representante, permissões, auditoria, importação e presença. O produto deve evoluir para refletir a rotina real: a equipe liga, registra o resultado, organiza retornos e reconfirma pelo WhatsApp quando disponível.

Autosserviço público, bilheteria e IA não fazem parte desta versão. IA poderá ser avaliada em uma nova versão, após observar o fluxo e confirmar um caso de uso que reduza trabalho sem retirar controle da equipe.

Próxima ação: validar com a potencial cliente os status de contato e a cadência de reconfirmação; depois implementar o menor fluxo que cubra fila de trabalho, histórico de tentativas, retorno e relatório operacional.

## Fontes

### Repositório auditado

- [README do MVP](https://github.com/nandinhos/rsvp-laravel/blob/main/README.md)
- [Arquitetura](https://github.com/nandinhos/rsvp-laravel/blob/main/docs/ARCHITECTURE.md)
- [Desenvolvimento](https://github.com/nandinhos/rsvp-laravel/blob/main/docs/DEVELOPMENT.md)
- [Rotas web](https://github.com/nandinhos/rsvp-laravel/blob/main/routes/web.php)
- [Dependências](https://github.com/nandinhos/rsvp-laravel/blob/main/composer.json)

### Benchmark e referências primárias

- [Académie française — R.S.V.P.](https://www.dictionnaire-academie.fr/article/A9R1899-A)
- [Partiful — convites e RSVP](https://partiful.com/)
- [Partiful — RSVP, capacidade e espera](https://help.partiful.com/en-us/articles/15525368-how-do-i-modify-the-types-of-rsvps-that-are-allowed)
- [Eventbrite — check-in pelo app de organizador](https://www.eventbrite.com/help/pt-br/articles/741083/como-fazer-check-in-dos-participantes-no-evento-com-o-eventbrite-organizer/)
- [Cvent — registro de eventos](https://www.cvent.com/en/event-marketing-management/event-registration-software)
- [RSVPify — plataforma e funcionalidades](https://rsvpify.com/)
- [RSVP.com.br — credenciamento](https://rsvp.com.br/credenciamento-de-eventos)
- [RSVP.com.br — convidados e integrações](https://rsvp.com.br/gestao-de-convidados)
- [RSVP.com.br — evento fechado por convite](https://rsvp.com.br/evento-por-convite)
- [Sympla — check-in](https://produtores.sympla.com.br/funcionalidades/check-in-para-eventos/)
- [Lei nº 13.709/2018 — LGPD](https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm)
- [Google — diretrizes para remetentes de e-mail](https://support.google.com/mail/answer/81126)

**Nota de pesquisa:** páginas comerciais descrevem capacidades declaradas pelos fornecedores; servem para mapear padrões e posicionamento, não para comparar desempenho medido, preço ou satisfação verificada. Planos, limites, preços e regras mudam e devem ser confirmados diretamente antes de decisão comercial.
