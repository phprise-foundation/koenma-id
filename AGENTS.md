# AGENTS.md

> **Leia isto antes de qualquer coisa.** Este documento define como agentes
> (humanos ou IA) devem se comportar neste repositório. Ele não substitui a
> filosofia do projeto — ele aponta para ela.

---

## 0. A doutrina: `docs/PHILOSOPHY.md`

O documento **mais importante** deste projeto é [`docs/PHILOSOPHY.md`](docs/PHILOSOPHY.md).
Ele é a nossa **doutrina**: orienta tanto desenvolvedores humanos quanto agentes
de IA, e define os princípios que guiam todo o desenvolvimento.

**Regra inegociável:** antes de propor ou implementar qualquer mudança, verifique
se ela **não viola nenhuma regra da filosofia**. Se houver conflito entre uma
instrução pontual e a filosofia, **a filosofia vence** — e o conflito deve ser
trazido para discussão, não resolvido silenciosamente.

Se você não tem certeza de como aplicar uma regra da filosofia a um caso
concreto, consulte [`docs/GUIDE.md`](docs/GUIDE.md). Se o caso não estiver lá,
**pare e converse** com o mantenedor antes de decidir; depois, adicione o
exemplo ao GUIDE para que a próxima pessoa não precise perguntar de novo.

---

## 1. Documentos do projeto

### Ordem de prioridade

Quando dois documentos divergirem, o de menor número vence:

1. **`docs/PHILOSOPHY.md`** — a doutrina. Orienta humanos e agentes.
2. **`docs/ROADMAP.md`**, **`docs/STATE.md`**, **`docs/ARCHITECTURE.md`** — documentos de estado.
3. **`README.md`** — documentação do produto para quem consome.
4. **`docs/GUIDE.md`**, **`docs/GITFLOW.md`** — documentos auxiliares.

### O que cada um é

| Documento | Papel | Quando consultar | Quando atualizar |
|---|---|---|---|
| `docs/PHILOSOPHY.md` | Doutrina do projeto | **Sempre**, antes de qualquer mudança | Quando a doutrina evoluir (raro, com discussão) |
| `docs/STATE.md` | Onde paramos, o que foi decidido, próximos passos | **Antes de iniciar** o desenvolvimento | **Ao finalizar** o desenvolvimento |
| `docs/ROADMAP.md` | Plano de execução: o caminho seguido e o que ainda vem | **Antes de iniciar** o desenvolvimento | **Ao finalizar** o desenvolvimento |
| `docs/ARCHITECTURE.md` | Estrutura de arquivos e responsabilidades | **Antes de iniciar**; evita abrir arquivos à toa | Quando a estrutura mudar |
| `docs/GITFLOW.md` | Guia dos fluxos Git (branch, commit, PR, release) | Antes de commitar/abrir PR | Quando o fluxo mudar |
| `docs/GUIDE.md` | Exemplos práticos de aplicação da filosofia | Quando houver dúvida de aplicação | Ao resolver um caso novo (após discussão) |
| `README.md` | Como usar o produto | — | **Sempre** que o uso mudar |
| `CONTRIBUTING.md` | Como contribuir (inclui visão da filosofia) | Antes de contribuir | Quando o processo mudar |

### Fluxo obrigatório

**Antes de iniciar o desenvolvimento**, leia:

- `docs/PHILOSOPHY.md` (a doutrina)
- `docs/STATE.md` (onde paramos)
- `docs/ROADMAP.md` (para onde vamos)
- `docs/ARCHITECTURE.md` (como o código está organizado)

**Ao finalizar o desenvolvimento**, atualize:

- `docs/STATE.md` (novo estado, decisões tomadas, próximos passos)
- `docs/ROADMAP.md` (marque o que avançou)
- `docs/ARCHITECTURE.md` (se a estrutura mudou)
- `README.md` (se o uso mudou)
- `CHANGELOG.md` (registre a mudança)

> **Se algum desses documentos não existir, avise o mantenedor** em vez de
> inventar conteúdo. Ele fornecerá o documento.

---

## 2. Como escrever arquivos

**Sempre escreva arquivos via script (`python` ou `sh`) executado pela linha de
comando**, nunca por edição direta com funções de *single replace*.

Motivo: as ferramentas de edição por substituição única falham de forma
silenciosa ou parcial em arquivos grandes, com caracteres especiais, ou quando o
trecho aparece mais de uma vez. Um script é determinístico, verificável e
reproduzível.

```bash
# Preferido: script Python com verificação
python3 - <<'PYEOF'
import pathlib

path = pathlib.Path('caminho/do/arquivo.php')
content = path.read_text()
old = "trecho antigo"
new = "trecho novo"
assert old in content, "trecho antigo não encontrado"
path.write_text(content.replace(old, new))
print("OK")
PYEOF
```

Regras:

- **Sempre** valide que o trecho antigo existe (`assert`) antes de substituir.
- **Sempre** imprima uma confirmação ao final.
- Para arquivos novos, use `create_new_file` ou um heredoc em script.
- Após editar PHP, rode `php -l <arquivo>` para validar a sintaxe.

---

## 3. Sobre este projeto

This is a Symfony project. Check `composer.json` for the exact Symfony/PHP version
in use, and read `symfony.lock` to see which recipes ran. Don't assume Doctrine,
Twig, API Platform, Messenger, or Lock are installed unless one of those says so.

## Ask before generating

If the task doesn't specify, ask rather than guess:

- Persistence: Doctrine ORM, Doctrine ODM, or none?
- Interface: server-rendered (Twig), API (Serializer, maybe API Platform), or both?
- Auth: SecurityBundle, and which authenticator?

If you can't ask (no interactive channel), state the assumption you're making and
pick the smallest option (e.g. no persistence layer) rather than scaffolding a
full stack nobody asked for.

## Adding features: Flex, not hand-wiring

Install new capabilities with `composer require <package>` (e.g. `symfony/lock`,
`symfony/messenger`, `orm-pack`) and let the Flex recipe register the bundle and
generate its config. Don't hand-edit `config/bundles.php` or hand-write a bundle's
base config; that's what the recipe is for. Don't skip a good-fit component just
because it isn't installed yet; installing it is one command.

## Conventions

Follow https://symfony.com/doc/current/best_practices.html to write idiomatic
Symfony:

- Use PHP attributes for framework metadata, and not only on controllers:
  `#[Route]`, `#[MapRequestPayload]`, `#[IsGranted]` on actions, `#[Assert\...]`
  on properties, `#[AsCommand]`, `#[AsEventListener]`, `#[AsMessageHandler]`, and
  `#[AsAlias]` / `#[AsTaggedItem]` / `#[Autoconfigure]` on services. No YAML or
  XML routing.
- Rely on autowiring and autoconfiguration. Type-hint constructor arguments and
  let the container resolve them. Where a type-hint can't express it, stay in the
  class with `#[Autowire]` (parameters, env vars, expressions) or `#[Target]` (one
  of several implementations of an interface). A YAML service definition is the
  last resort, not the first.
- Controllers extend `AbstractController`, stay thin, and delegate to services.
- Use the framework for what it already does: Form for server-rendered forms,
  Validator for validation, Serializer for JSON, Messenger for async work,
  Security (voters, authenticators) for access control, Twig `path()`/`url()`
  instead of hardcoded URLs.
- Before hand-writing infrastructure (locks, queues, caches, HTTP clients,
  mailers, schedulers) or reaching for a third-party library, check whether a
  Symfony component covers it. It usually does.

Three specifics worth spelling out, because they are easy to get wrong:

- Bind request data with `#[MapRequestPayload]` / `#[MapQueryString]` on action
  arguments, which wires up Serializer and Validator for you, instead of calling
  `json_decode()` or `SerializerInterface` by hand. If neither package is
  installed yet, `composer require` them rather than falling back to manual
  parsing.
- Use constructor property promotion, and `readonly` for DTOs and value objects.
  Don't mark a service `readonly` if it might become `lazy: true`: a lazy proxy
  can't extend a `readonly` class.
- Use `symfony/lock` (`LockFactory`) for mutual exclusion. A hand-built flag or
  lock file looks fine in review and is usually wrong under concurrency.

## Everyday workflow

- Run the app with `symfony serve -d`, and commands with `symfony console ...`
  (or `bin/console` when the Symfony CLI isn't available).
- When something fails, read `var/log/dev.log` and the web profiler
  (`/_profiler`) before changing code.
- If `maker-bundle` is installed, prefer `bin/console make:*` with every argument
  passed up front and `--no-interaction` where supported: makers prompt on a
  terminal by default, which hangs a non-interactive shell. If a maker still
  needs interactive input, hand-write the code instead.
- If Doctrine ORM is installed, schema changes go through migrations
  (`bin/console make:migration`, then `doctrine:migrations:migrate`), never
  `doctrine:schema:update` or hand-written SQL.
- `.env` is committed and holds defaults only. Real secrets belong in `.env.local`
  (git-ignored) or the secrets vault (`bin/console secrets:set`), read via
  `%env(...)%`.

## Testing

Install `symfony/test-pack` if it isn't already. Functional/HTTP tests extend
`WebTestCase`; service-level tests extend `KernelTestCase`. Run
`php bin/phpunit` (falls back to `vendor/bin/phpunit`). A feature isn't done
until it has a test that exercises it the way a caller would, an HTTP request for
a controller or a service call for a service, not just "it didn't throw."

## Code style

Symfony's coding standard, the `@Symfony` php-cs-fixer ruleset (a PSR-12-derived
superset). Run `vendor/bin/php-cs-fixer fix` if `friendsofphp/php-cs-fixer` is
installed; it isn't part of the skeleton by default.

## Discover, don't guess

Framework APIs change between versions and your training data may be stale. Look
things up in the project instead of relying on memory:

- `bin/console about`: versions, environment, paths.
- `bin/console debug:router`, `debug:container`, `debug:autowiring <name>`,
  `debug:config <bundle>`, `config:dump-reference <bundle>`: what exists and how
  it is configured.
- `bin/console lint:container`, plus `lint:twig templates/` and
  `lint:yaml config/` where those packages are installed: validate before running.
- Read the installed source and docblocks under `vendor/`.
- Docs: https://symfony.com/doc/current/ (switch to the version matching
  `composer.json` if it differs).
