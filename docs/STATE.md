# Estado do Projeto — Koenma ID

> **Atualizado em:** 2026-09-30
> **Branch:** `1.x`
> **Fase atual:** Fase 1 — Entidades + Rotas de Token (em andamento)

Este arquivo é o **ponto de entrada** para qualquer agente ou pessoa que retome o
projeto. Leia-o antes de qualquer coisa. Ele diz onde paramos, o que foi decidido
e o que vem a seguir.

---

## 1. Visão geral

O **Koenma ID** é o serviço de identidade e autenticação do ecossistema Phprise. Atua como "cadeado no
portão" das APIs internas (Nami, Yagami, etc.), autenticando **parceiros**
(máquina-a-máquina, via API Key) e **usuários** (pessoas, via JWT).

Hierarquia de domínio:

```
Partner (contratante da API)
├── Project → ApiKey      (autenticação máquina-a-máquina)
└── Contractor → User     (autenticação de pessoas)
```

---

## 2. Stack

> **Localização:** `/home/esdras/src/phprise/koenma-id`
> **Namespace raiz:** `Phprise\KoenmaID` (PSR-4: `src/` → `Phprise\KoenmaID\`, `tests/` → `Phprise\KoenmaID\Tests\`)
> **Pacote:** `phprise/koenma-id`

| Componente | Versão |
|---|---|
| PHP | 8.5 |
| Symfony | 8.1.6 (última estável) |
| API Platform | 4.3.18 |
| Doctrine ORM | 3.7 |
| PostgreSQL | 16 (Docker) |
| Lexik JWT | 3.2 |
| PHPUnit | 13 (via test-pack) |

---

## 3. Decisões tomadas (NÃO reverter sem discussão)

Estas decisões foram deliberadas. Um agente que "melhorar" isso sem alinhamento
vai contra o combinado.

| Decisão | Motivo |
|---|---|
| **ULID como PK, armazenado como `UUID` nativo do Postgres** | Ordenável por tempo; coluna `uuid` para índices, ordenação e FKs eficientes |
| **IDs com prefixo legível por entidade** (`prt_`, `prj_`, `aky_`, `cnt_`, `usr_`, `rtk_`) | Identificação visual do tipo; prefixo é só apresentação, **não** vai ao banco |
| **1 ValueObject de ID por entidade** (`PartnerId`, `UserId`, ...) | Encapsula prefixo + ULID; valida prefixo no parse |
| **1 Doctrine CustomType de ID por entidade** (`partner_id`, `user_id`, ...) | Remove prefixo ao persistir; adiciona ao hidratar |
| **`PrefixedIdGenerator`** (CustomIdGenerator) | Gera o ValueObject via o Type registrado; substitui o `UlidGenerator` |
| **Soft delete via coluna `deleted_at`** | Nativo do Doctrine; sem dependência extra |
| **Timestamps via lifecycle callbacks** (`#[ORM\PrePersist]`/`#[ORM\PreUpdate]`) | Nativo, sem Gedmo |
| **NÃO usar Gedmo** | `stof/doctrine-extensions-bundle` é incompatível com Symfony 8.1 |
| **Refresh tokens persistidos em tabela** | Permite revogação real (não só stateless) |
| **Password com argon2id** | Mesmo algoritmo do Keycloak |
| **`/token/create` combina username+password + API Key do Partner** | Fluxo de cadastro: Partner → Project → ApiKey → Contractor → User |
| **Grupos de serialização por método** (`:post`, `:get`, `:patch`) | Segregação de payloads; **não usamos PUT** |
| **Sem traits** | Preferência: CustomGenerator via atributo + classes curtas |
| **Tabela `user` citada** (`#[ORM\Table(name: '`user`')]`) | `user` é palavra reservada no Postgres; sem aspas o `FROM user` é interpretado como função |
| **API Platform: saída JSON-LD/JSON/XML/CSV; entrada JSON/LD+JSON; PATCH merge-patch+json/JSON** | Formatos explícitos em `api_platform.yaml` |
| **`dama/doctrine-test-bundle` habilitado em `test`** | Rollback automático entre testes; registrado em `bundles.php` |
| **Sem prefixo `/api` nas rotas** | O produto é uma API (`api.<produto>.io`); `/api` seria redundante. Rotas na raiz (`/partners`, `/token/create`, `/docs`) |
| **Autenticação desabilitada por enquanto** | Firewall `main` com `security: false` e `access_control` `PUBLIC_ACCESS`; reativar quando definirmos permissões de API Key (ver dívida técnica) |
| **Sem controllers; tudo via `ApiResource`** | Mantém OpenAPI/docs alinhados, respeita formatos do YAML, filtros/paginação/validação consistentes. `TokenController` foi removido e migrado para `TokenResource` + State processors |
| **`find()` funciona com ID prefixado** | `AbstractPrefixedIdType::convertToDatabaseValue` aceita string prefixada e converte para ValueObject; evita `findById()` em cada repositório (presente e futuro) |

---

## 4. Concluído

- [x] Symfony 8.1.6, Postgres 16, Lexik JWT, MakerBundle, test-pack
- [x] Chaves JWT geradas em `config/jwt/`
- [x] Postgres em `127.0.0.1:5432` (porta fixa no `compose.override.yaml`)
- [x] 6 entidades: `Partner`, `Project`, `ApiKey`, `Contractor`, `User`, `RefreshToken`
- [x] 6 repositórios
- [x] Migration `Version20260912141613` executada
- [x] DTOs de entrada/saída com grupos `:post`/`:get`/`:patch` para todas as entidades
- [x] ApiResources (rotas CRUD) para Partner, Project, ApiKey, Contractor, User
- [x] Providers e Processors (State) para todas as entidades
- [x] Serviços de domínio: `PartnerRegistrar`, `ProjectRegistrar`, `ApiKeyIssuer`, `ContractorRegistrar`, `UserRegistrar`
- [x] DTOs de Token: `TokenCreateInput`, `TokenRefreshInput`, `TokenVerifyInput`, `TokenRevokeInput`, `TokenOutput`, `TokenVerifyOutput`
- [x] **ValueObjects de ID** (`AbstractPrefixedId` + 6 concretos) com prefixo + ULID
- [x] **Doctrine CustomTypes de ID** (`AbstractPrefixedIdType` + 6 concretos) mapeando para `UUID`
- [x] **`PrefixedIdGenerator`** substituindo o `UlidGenerator` nas 6 entidades
- [x] **Rotas de Token** (`/token/create`, `/token/refresh`, `/token/verify`, `/token/revoke`) implementadas
- [x] **`security.yaml`** com provider `UserRepository`, firewall JWT e argon2id
- [x] Serviços de Token: `TokenIssuer`, `TokenRefresher`, `TokenVerifier`, `TokenRevoker`, `TokenAuthenticator`
- [x] Ambiente de teste: `.env.test`, banco `app_test`, `dama/doctrine-test-bundle`
- [x] Testes: `UserRegistrarTest`, `PartnerApiTest`, `TokenFlowTest`, `PrefixedIdTest` + `TestDataFactory`
- [x] **Suíte verde: 49 testes, 109 assertions**
- [x] **API Key em texto puro exibida uma única vez na criação** (`ApiKeyOutput::$key`, grupo `api_key:post`); nunca armazenada, nunca recuperável
- [x] **Formato da API Key**: `sk_` + 32 caracteres alfanuméricos (`/^sk_[A-Za-z0-9]{32}$/`)
- [x] **Documentação OpenAPI em inglês**: `description` por `ApiResource`, `summary`+`description` por operação, doc comments nos campos dos DTOs
- [x] **Governança de documentos**: `AGENTS.md` reescrito com a doutrina (`docs/PHILOSOPHY.md`), ordem de prioridade dos documentos, fluxo obrigatório (ler antes / atualizar depois) e a regra de escrever arquivos via script
- [x] **`docs/ARCHITECTURE.md`** criado: mapa de camadas, fluxo de requisição, estrutura de pastas, onde mexer para cada tipo de mudança
- [x] **`docs/GITFLOW.md`** preenchido: fluxo enxuto (branch curta → commit conventional → PR → merge `-d` → tag semver → release → `bin/publish.sh`)
- [x] **`CONTRIBUTING.md`** criado: visão geral da filosofia + processo de contribuição
- [x] **`README.md`** atualizado: seção "Filosofia e documentação" apontando PHILOSOPHY/GUIDE/GITFLOW/CONTRIBUTING
- [x] **Segurança**: `JWT_PASSPHRASE` real removido do `.env` (rastreado) e movido para `.env.local` (git-ignored); `.gitignore` reorganizado
- [x] **Fase 1.5 / Etapa B — POST /users vinculado ao Contractor** (concluída):
  - Rota `POST /contractors/{contractorId}/users` (não cria mais Contractor)
  - `UserInput` sem `apiKey`, `contractorName`, `contractorDocument`
  - `UserRegistrar::register(Contractor, UserInput)` valida a chave via `SecurityKeyContext`
  - `UserPostProcessor` resolve o Contractor pelo `contractorId` da rota (404 se ausente)
  - Unicidade `username` por Contractor (migration `uniq_user_contractor_username`)
  - Testes: `UserApiTest` (12) + `UserRegistrarTest` (4)
  - Suíte total: **64 testes, 127 assertions**
- [x] **Fase 1.5 / Etapa C — Chave de segurança nas rotas de token e API Key** (concluída):
  - `/token/create` e `/token/verify` usam `X-Security-Key` (sem `apiKey` no payload)
  - `DELETE /api-keys/{id}` (soft-delete, exige chave do Partner dono)
  - GetCollection de API Keys filtra as expiradas (`findActiveByProject`)
  - Master Key obrigatória para criar/editar Partners (`MasterScopeGuard`)
  - Suíte total: **72 testes, 139 assertions**
- [ ] **Fase 1.5 / Etapa D — Segregação por parceiro** (próximo passo):
  - Master vê tudo; chave de parceiro vê só o seu, em todos os endpoints
  - Toca todos os providers/processors de collection e item
- [x] **Versionamento e releases**:
  - Política `0.y.z` adotada (desenvolvimento inicial); `v1.0.0` só quando todas as fases estiverem prontas
  - Tag inicial reescrita de `v1.0.0` para **`v0.1.0`** (exceção única de fundação, documentada no GITFLOW)
  - Release `v0.1.0` publicada (uma release por tag)
  - Ruleset "Protect version tags" ativo: bloqueia deleção/reescrita de `refs/tags/v*`
- [x] **Fase 1.5 / Etapa A — Modelo de chave e cabeçalho** (concluída):
  - `ApiKeyOutput::$key` renomeado para `$securityKey` (grupo `api_key:post`)
  - `ValueObject\SecurityKey`: valida formato `sk_[A-Za-z0-9]{32}` e expõe `hash()` (sha256)
  - `Service\Security\SecurityScope`: value object do escopo (`master` / `partner` / `anonymous`)
  - `Service\Security\SecurityKeyContext`: resolve o header `X-Security-Key` (hash + não expirada) e cacheia por request
  - `Service\Security\MasterSecurityKey`: lê `%env(MASTER_SECURITY_KEY)%` e compara com `hash_equals`
  - `.env` ganhou `MASTER_SECURITY_KEY=` (default vazio); `.env.test` ganhou `MASTER_SECURITY_KEY` e `JWT_PASSPHRASE`
  - `SecurityKeyContext` público em `when@test` (para testes)
  - Testes: `SecurityKeyTest` (5) + `SecurityKeyContextTest` (5)
  - Suíte total: **59 testes, 121 assertions**
- [x] **Endpoints de Token documentados individualmente** (não mais "Creates a Token resource." genérico)
- [x] **Rotas na raiz** (sem prefixo `/api`): `/partners`, `/projects`, `/token/*`, `/docs`
- [x] **Autenticação desabilitada** (firewall `main` `security: false`; `access_control` `PUBLIC_ACCESS`)
- [x] **`TokenController` removido** e migrado para `TokenResource` + 4 State processors (`TokenCreateProcessor`, `TokenRefreshProcessor`, `TokenVerifyProcessor`, `TokenRevokeProcessor`)
- [x] **Endpoints de Token documentados** no OpenAPI/Swagger (`/docs`)
- [x] **`AbstractPrefixedIdType` aceita string prefixada** em `convertToDatabaseValue`; `find()`/`findBy()` funcionam nativamente com IDs prefixados
- [x] **`findById()` removido** de `PartnerRepository`/`ProjectRepository` (não é mais necessário)
- [x] **Renomeação para Koenma ID**: projeto movido para `/home/esdras/src/phprise/koenma-id`
  - Namespace `App\` → `Phprise\KoenmaID\` (97 arquivos em `src/` e `tests/`)
  - `composer.json`: `name` = `phprise/koenma-id`, autoload PSR-4 atualizado
  - Configs (`services.yaml`, `doctrine.yaml`, `security.yaml`, `validator.yaml`, `doctrine_migrations.yaml`) atualizados
  - `KERNEL_CLASS` (`.env.test`), `bin/console`, `public/index.php` atualizados
  - Título da API (`api_platform.yaml`) = `Koenma ID API`; docs atualizados
  - `.env.test` corrigido: `DATABASE_URL` aponta para `app` (o sufixo `_test` é adicionado pelo `dbname_suffix`)
- [x] **Testes de integração dos subresources** (a lacuna que deixou o bug passar):
  - `ProjectApiTest`: POST/GET collection em `/partners/{partnerId}/projects`, GET/PATCH em `/projects/{id}`, 404
  - `ApiKeyApiTest`: POST/GET collection em `/projects/{projectId}/api-keys`, GET/PATCH em `/api-keys/{id}`, 404
  - `ContractorApiTest`: POST/GET collection em `/partners/{partnerId}/contractors`, GET/PATCH em `/contractors/{id}`, 404
  - `UserApiTest`: POST em `/users` (via API Key), GET collection em `/contractors/{contractorId}/users`, GET/PATCH em `/users/{id}`, 404
  - Cobertura de escopo: cada listagem verifica que só retorna filhos do pai informado
  - Suíte total: **48 testes, 103 assertions**
- [x] **Dockerfile de produção (FrankenPHP)** para deploy no Cloudflare:
  - `Dockerfile` multi-stage: stage `vendor` (composer install --no-dev) + stage `runtime` (dunglas/frankenphp:1-php8.4-bookworm)
  - Extensões: `pdo_pgsql`, `intl`, `opcache`, `zip`
  - `docker/Caddyfile`: worker mode (`./public/index.php`), `php_server` com root `public/`
  - `.dockerignore` (exclui `tests/`, `var/`, `vendor/`, `docs/`, etc.)
  - `compose.yaml`: serviço `app` (build) + `database` (Postgres), healthcheck em `/partners`
  - `.env.prod` + `composer dump-env prod` no build (gera `.env.local.php`)
  - Imagem final: **641MB**; container `healthy`
  - Validado em produção: API responde (200), subresources POST/GET OK, `/token/*` rejeita entradas inválidas

## Retomando o trabalho (após desligar a máquina)

O Docker para quando a máquina é desligada, derrubando os containers. Ao retomar:

```bash
docker compose up -d          # sobe app + database
docker compose ps             # confirma ambos healthy
curl -s -o /dev/null -w '%{http_code}\n' http://localhost/partners   # deve dar 200
```

Se o `app` estiver `unhealthy` com erro `could not translate host name "database"`,
é porque o container do banco caiu: `docker compose up -d database` resolve.

## Em andamento — Etapa 3: Deploy no Cloudflare

**Decisão de arquitetura (2026-09-16):**
- Usar **Cloudflare Containers** (Worker + container FrankenPHP)
- Postgres **externo** (Neon/Supabase/outro), conectado **direto** pelo container via `DATABASE_URL`
- **Sem Hyperdrive**: o Hyperdrive é binding de Worker (V8 isolate) e não é acessível de dentro do container (VM Linux). Para a app Symfony inteira rodando no container, a conexão direta é mais simples e suficiente.

**Feito:**
- [x] `package.json` (`@cloudflare/containers`, `wrangler`, `typescript`)
- [x] `wrangler.jsonc` (Worker + container `KoenmaIdContainer` + Durable Object + migration v1)
- [x] `src/index.ts` (Worker roteia tudo para o container; `defaultPort=80`; env vars via `envVars`)
- [x] `tsconfig.json`, `.dev.vars` (dev local), `.gitignore` atualizado
- [x] `wrangler deploy --dry-run` OK (builda imagem + valida bindings)
- [x] `tsc --noEmit` OK
- [x] `wrangler dev` testado end-to-end: subresources POST/GET, `/token/verify`, `/token/create` — tudo OK

**Próximos passos:**
- [ ] Provisionar Postgres externo (Neon/Supabase) e rodar migrations
- [ ] Configurar secrets de produção: `npx wrangler secret put APP_SECRET` / `DATABASE_URL` / `JWT_PASSPHRASE`
- [ ] Decidir estratégia das chaves JWT em produção (hoje `config/jwt/*.pem` está na imagem)
- [ ] `npx wrangler deploy` e validar

---

## 5. Em andamento

- [ ] **Tour manual interrompido na criação de Project** (via sub-recurso `/partners/{partnerId}/projects`)
  - Bug encontrado: `ProjectPostProcessor` não resolvia o `partnerId` prefixado → corrigido via `AbstractPrefixedIdType`
  - **Pendente de validação manual**: reexecutar o tour a partir da criação de Project
- [ ] Revisão final da Fase 1 (documentação, cobertura de testes)

---

## 6. Próximos passos (ordem sugerida)

1. Revisar cobertura de testes das rotas de Token (casos de erro: token expirado, revogado, assinatura inválida)
2. Implementar verificação de email (código por email antes de criar Project/ApiKey)
3. Avaliar rate limiting nas rotas de Token
  > Uma ideia eh adicionarmos um Redis e definirmos o limite de uso de um token (sugestao verify=60pm, create=5pm, refresh=5pm) Isso deve impedir um atacante de invadir com senhas aleatorias e de praticar um DDoS, se achar necessario podemos ainda adicionar um delay poposital na geraçao de tokens tanto create quanto refresh. Voce tem uma sugestao melhor?
4. Iniciar Fase 2 (a definir)

---

## 7. Pendências conhecidas / dívida técnica

- **`UserRegistrar`**: a validação de API Key usa `hash('sha256', ...)`, consistente
  com `ApiKeyIssuer`. Confirmar que o formato da chave em texto puro é o esperado.
  > Espero uma chave no formaro /sk_[A-Za-z0-9]{32}/
- **`ApiKeyIssuer`**: **resolvido**. A chave em texto puro é retornada uma única vez na
  resposta do POST (`ApiKeyOutput::$key`, grupo `api_key:post`). O banco guarda apenas
  `keyHash`, `keyPrefix` e `keySuffix`. Formato: `sk_` + 32 caracteres alfanuméricos.
- **Verificação de email**: o fluxo futuro (código por email antes de criar Project/ApiKey)
  ainda não foi implementado. Deixar o caminho livre.
  > Deixar caminho livre (sem algo que impeça), mas ainda nao vamos implementar porque nao tenho definido o serviço de Email que vou utilizar, ainda esta em pesuisa.
- **`security.yaml`**: autenticação **desabilitada** por enquanto (firewall `main` `security: false`).
  O provider `app_user_provider` (UserRepository) já está configurado, mas o firewall JWT foi
  desligado até definirmos o modelo de permissões de API Key.
  > Vamos deixar isto para depois, muitas destas rotas precisarao ser acessadas antes da criaçao de usuario e portanto nao terao o cabeçalho authorization, mas vamos precisar de um modelo de permissoes das API Keys e definir uma API Key MASTER para usarmos em um dash de criaçao de parceiros e chaves de acesso, outras API Keys nao terao esta permissao. Vamos deixar isso apenas anotado e quando integrarmos os sistemas a gente volta neste ponto.

---

## 8. Como rodar

```bash
# Subir o banco
docker compose up -d

# Rodar migrations
bin/console doctrine:migrations:migrate --no-interaction

# Validar mapeamento
bin/console doctrine:schema:validate --skip-sync

# Rodar testes
bin/phpunit

# Servidor de desenvolvimento
symfony serve -d
```

---

## 9. Estrutura de pastas relevante

```
src/
├── ApiResource/        # DTOs + ApiResources (rotas)
│   ├── Partner/
│   ├── Project/
│   ├── ApiKey/
│   ├── Contractor/
│   ├── User/
│   └── Token/
├── Doctrine/
│   ├── IdGenerator/    # PrefixedIdGenerator
│   └── Type/           # AbstractPrefixedIdType + 6 tipos de ID
├── Entity/             # Entidades Doctrine
├── Repository/         # Repositórios (findById converte prefixo → ValueObject)
├── Service/            # Regras de negócio (Registrars, Issuers, Token)
├── State/              # Providers e Processors do API Platform
└── ValueObject/        # AbstractPrefixedId + 6 IDs (prefixo + ULID)
```
