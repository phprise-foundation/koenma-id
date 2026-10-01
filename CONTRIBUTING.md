# Contribuindo com o Koenma ID

Obrigado pelo interesse em contribuir. Antes de escrever qualquer linha de
código, **leia a filosofia do projeto** — ela é a doutrina que guia todas as
decisões aqui, e toda contribuição precisa estar alinhada com ela.

---

## 1. A filosofia é a doutrina

O documento mais importante deste repositório é
[`docs/PHILOSOPHY.md`](docs/PHILOSOPHY.md). Ele orienta tanto desenvolvedores
humanos quanto agentes de IA.

**Antes de propor qualquer mudança, verifique se ela não viola nenhuma regra da
filosofia.** Se houver conflito entre uma instrução pontual e a filosofia, **a
filosofia vence** — e o conflito deve ser trazido para discussão, nunca resolvido
silenciosamente.

Se você tiver dúvida sobre como aplicar uma regra a um caso concreto, consulte
[`docs/GUIDE.md`](docs/GUIDE.md). Se o caso não estiver lá, **converse com o
mantenedor** antes de decidir; depois, adicione o exemplo ao GUIDE para que a
próxima pessoa não precise perguntar de novo.

### O OTAKU Manifesto

A filosofia se chama **Fluid Structure Design** e se apoia em cinco pilares, um
para cada letra do acrônimo **OTAKU**:

- **O — Own your Discipline.** Restrição como libertação. As **9 regras do Object
  Calisthenics** são obrigatórias: um nível de indentação por método, sem `else`,
  envolver primitivos e strings, coleções de primeira classe, um ponto por linha,
  não abreviar, entidades pequenas (50 linhas por classe, 10 arquivos por pacote),
  no máximo 2 variáveis de instância, e sem getters/setters/propriedades públicas.
- **T — Tools for Composition.** Modularidade à la Unix: cada componente faz uma
  coisa e a faz bem. O fluxo da aplicação é um *data pipe* — a entrada entra por
  adaptadores, atravessa o núcleo e sai, mantendo a lógica isolada dos efeitos
  colaterais.
- **A — Armor the Core.** Soberania do domínio. A lógica de negócio é o ativo mais
  valioso e é protegida por **Clean Architecture**: as dependências apontam apenas
  para dentro, em direção ao domínio. O "coração" (casos de uso e entidades) nunca
  conhece frameworks, bancos ou APIs externas.
- **K — Keep Infrastructure Silent.** Infraestrutura é detalhe. Banco, mailer e
  broker são secundários; o acesso a dados é tratado como uma coleção em memória
  (**Repository Pattern**). As entidades de domínio são **POPOs**, ignorantes de
  como são salvas ou transmitidas.
- **U — Universal Language & Contracts.** Linguagem ubíqua (DDD) solidificada em
  **contratos design-first (OpenAPI)**. O contrato é a promessa técnica imutável
  entre sistemas. Se o código não lê como o especialista de negócio fala, ou se o
  contrato é contornado, o sistema está quebrado.

### As três formas de repositório

Todo repositório se encaixa em um destes arquétipos:

- **Atom** — a menor unidade de granularidade. Estrutura plana (um nível dentro de
  `src/`), sem subdivisões, cumprindo um único contrato.
- **Assembly** — ponto de conexão/meta-pacote. Sem código em `src/`; apenas
  orquestra a união de Atoms via `composer.json`.
- **Core** — o sistema vivo. Segue Clean Architecture e DDD, e deve ser *lean*:
  tudo que é genérico é movido para Atoms; o Core guarda só o que é único do
  negócio (entidades, casos de uso, adaptadores específicos).

> O **Koenma ID** é um repositório **Core**.

---

## 2. Documentos do projeto

Ordem de prioridade (o de menor número vence em caso de divergência):

1. [`docs/PHILOSOPHY.md`](docs/PHILOSOPHY.md) — a doutrina
2. [`docs/ROADMAP.md`](docs/ROADMAP.md), [`docs/STATE.md`](docs/STATE.md),
   [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — documentos de estado
3. [`README.md`](README.md) — documentação do produto
4. [`docs/GUIDE.md`](docs/GUIDE.md), [`docs/GITFLOW.md`](docs/GITFLOW.md) —
   documentos auxiliares

**Antes de começar**, leia PHILOSOPHY, STATE, ROADMAP e ARCHITECTURE.
**Ao terminar**, atualize STATE, ROADMAP, ARCHITECTURE (se mudou), README (se o
uso mudou) e CHANGELOG.

---

## 3. Fluxo de contribuição

Seguimos um fluxo enxuto baseado em branches curtas e PRs para a `main`. O guia
completo está em [`docs/GITFLOW.md`](docs/GITFLOW.md). Resumo:

1. **Crie uma branch de trabalho** a partir da `main` atualizada:
   - `feat/<descricao>` para nova funcionalidade
   - `fix/<descricao>` para correção
2. **Implemente** as alterações e adicione ao stage (`git add`).
3. **Commit** no padrão *Conventional Commits*:
   ```
   feat(KOENMA-123): descrição curta no imperativo
   ```
4. **Push** e abra um **PR** para a `main` (`gh pr create`).
5. **Verifique conflitos.** Sem conflito, faça o merge apagando a branch:
   ```
   gh pr merge <numero> --merge --delete-branch
   ```
6. **Tag semver** conforme o tipo da mudança:
   - `fix` → incrementa **patch** (`v1.0.0` → `v1.0.1`)
   - `feat` → incrementa **minor** (`v1.0.0` → `v1.1.0`)
   - *breaking change* → incrementa **major** (`v1.0.0` → `v2.0.0`)
7. **Release:** a cada nova *major*, publique a tag como release.

---

## 4. Padrões de código

- **Symfony Best Practices** (https://symfony.com/doc/current/best_practices.html).
- **PHP attributes** para metadados de framework (nada de YAML/XML de rota).
- **Autowiring e autoconfiguração**; YAML de serviço é último recurso.
- **Sem controllers** — tudo via `ApiResource` + State Providers/Processors.
- **Sem PUT** — apenas `POST`, `GET`, `PATCH`.
- **Sem prefixo `/api`** — rotas na raiz.
- **Sem traits** — classes curtas.
- **Sem Gedmo** — soft delete e timestamps nativos do Doctrine.
- **Estilo:** `@Symfony` (php-cs-fixer), derivado do PSR-12.

### Escrita de arquivos

**Sempre escreva arquivos via script (`python` ou `sh`) pela linha de comando**,
nunca por edição direta com funções de *single replace*. Valide o trecho antigo
com `assert` antes de substituir e imprima confirmação ao final. Após editar PHP,
rode `php -l <arquivo>`.

---

## 5. Testes

Uma funcionalidade só está pronta quando tem um teste que a exercita **como um
chamador a usaria** — requisição HTTP para uma rota, chamada de serviço para um
serviço. "Não lançou exceção" não é teste.

```bash
bin/phpunit                                   # suíte completa
bin/phpunit tests/Integration/Api/UserApiTest.php
bin/phpunit --filter testNomeDoCaso
```

- **Unit** → ValueObjects e lógica pura.
- **Integration/Api** → `WebTestCase` (HTTP real).
- **Integration/Service** → `KernelTestCase`.
- **EndToEnd** → fluxo completo.

---

## 6. Migrations

Alterações de schema passam **sempre** por migrations:

```bash
bin/console make:migration
bin/console doctrine:migrations:migrate --no-interaction
```

Nunca use `doctrine:schema:update` nem SQL escrito à mão.

---

## 7. Segredos

- `.env` é commitado e contém **apenas defaults**.
- Segredos reais vão para `.env.local` (git-ignored) ou para o vault
  (`bin/console secrets:set`), lidos via `%env(...)%`.
- **Nunca** commite chaves, tokens ou senhas.

---

## 8. Checklist antes de abrir o PR

- [ ] Li a filosofia e a mudança não viola nenhuma regra
- [ ] A funcionalidade tem teste que a exercita como um chamador usaria
- [ ] `bin/phpunit` está verde
- [ ] `bin/console lint:container` passa
- [ ] Migrations criadas (se houve mudança de schema)
- [ ] STATE, ROADMAP, ARCHITECTURE, README e CHANGELOG atualizados
- [ ] Commit no padrão Conventional Commits
- [ ] Nenhum segredo no diff

---

## 9. Dúvidas

Se algo não estiver claro na filosofia ou no GUIDE, **pergunte antes de
implementar**. É melhor uma pergunta do que uma contribuição que precise ser
refeita.
