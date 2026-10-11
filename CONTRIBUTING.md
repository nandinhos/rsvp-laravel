# Como contribuir

Obrigado por contribuir com o RSVP. Este guia resume o fluxo esperado.

## Fluxo

1. Crie um branch a partir de `dev`:
   `git checkout dev && git pull && git checkout -b feature/descricao`
2. Faça alterações pequenas e focadas, com testes.
3. Antes de abrir o PR:
   ```bash
   ./vendor/bin/pest     # testes verdes
   ./vendor/bin/pint     # code style PHP
   npm run build         # se tocou em assets
   ```
4. Abra um Pull Request para `dev` descrevendo o quê e o porquê.

> `dev` é o branch de integração. `main` recebe apenas releases estáveis.

## Convenções de commit

Formato: `tipo(escopo): descrição em pt-BR`

- `feat` — nova funcionalidade
- `fix` — correção de bug
- `refactor` — refatoração sem mudança de comportamento
- `docs` — documentação
- `test` — testes
- `chore` — infra/tooling

Exemplos:
```
feat(design): adiciona StatCard reutilizável
fix(auth): logout redireciona para a tela de login
docs: documenta pipeline de importação
```

## Padrões de código

- Regras de domínio em `app/Services/`, não em controllers.
- Enums como fonte da verdade dos estados.
- Autorização sempre validada no servidor (`EventPolicy`), nunca só na UI.
- Frontend: reutilize os componentes de `resources/js/Components/ui/` e as classes
  `institutional-*` (sem hex hardcoded).
- Cada novo comportamento deve ter teste proporcional ao risco.

## Testes

Os testes mapeiam os critérios de aceite (CA-01 … CA-14). Ao alterar regras de
domínio (RSVP, representação, isolamento, numeração), adicione/atualize o teste
correspondente. Veja a tabela em [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Segurança

- Nunca commite segredos. `.env` é ignorado; use `.env.example` como referência.
- Não exponha `APP_KEY` real nem credenciais de produção no repositório.
