# Arquitetura — Koenma ID

> **Atualizado em:** 2026-10-08
> **Objetivo deste documento:** descrever a estrutura de arquivos e as
> responsabilidades de cada camada, para que agentes e pessoas encontrem o que
> precisam **sem abrir arquivos desnecessariamente** (economia de contexto/token)
> e alterem o código de forma certeira.

> **Antes de iniciar o desenvolvimento**, leia este documento junto com
> [`PHILOSOPHY.md`](./PHILOSOPHY.md), [`STATE.md`](./STATE.md) e
> [`ROADMAP.md`](./ROADMAP.md). **Ao finalizar**, atualize-o se a estrutura mudou.

---

## 1. Visão em uma frase

API Symfony + API Platform que expõe recursos de identidade (Partner, Project,
ApiKey, Contractor, User) e um ciclo de vida de tokens (create/refresh/verify/revoke),
usando **State Providers/Processors** em vez de controllers.

---

## 2. Fluxo de uma requisição

```
HTTP Request
   │
   ▼
ApiResource (atributo #[ApiResource] + operações)   ← define rota, input, output, grupos
   │
   ▼
State Provider  (GET)  ──►  Repository  ──►  Entity
   │
   ▼
State Processor (POST/PATCH)  ──►  Service (regra de negócio)  ──►  Entity  ──►  Repository
   │
   ▼
Entity (ou Output DTO de Token)  ──►  Serializer (grupos)  ──►  HTTP Response
```

- **Não há controllers.** Toda rota nasce de um `ApiResource` + operações.
- **Providers** leem; **Processors** escrevem. Ambos delegam para Repository/Service.
- **Services** concentram a regra de negócio; nunca conhecem HTTP.
- **Entidades** são expostas diretamente como `ApiResource` (Fase 1.6); só `Token` mantém DTOs (`Input`/`Output`).

---

## 3. Estrutura de pastas

```
src/
├── ApiResource/        # Contrato da API: apenas Token (DTOs + rotas)
│   └── Token/
├── ApiPlatform/        # UriVariableTransformer (IDs prefixados)
│   └── UriVariableTransformer/
├── Doctrine/
│   ├── IdGenerator/    # PrefixedIdGenerator (CustomIdGenerator)
│   └── Type/           # AbstractPrefixedIdType + 6 tipos de ID
├── Entity/             # Entidades Doctrine (6) + ApiResource direto (Fase 1.6)
├── Repository/         # Repositórios (6)
├── Service/            # Regras de negócio
│   ├── ApiKey/
│   ├── Contractor/
│   ├── Partner/
│   ├── Project/
│   ├── Security/       # Resolução do header X-Security-Key, escopo e tipo de chave
│   ├── Token/
│   └── User/
├── State/              # Providers e Processors do API Platform
│   ├── Partner/
│   ├── Project/
│   ├── ApiKey/
│   ├── Contractor/
│   ├── User/
│   └── Token/
├── Serializer/         # Normalizer de IDs prefixados
│   └── Normalizer/
├── ValueObject/        # AbstractPrefixedId + 6 IDs (prefixo + ULID)
└── Kernel.php
```

---

## 4. Camadas em detalhe

### 4.1 `ApiResource` — contrato da API

A partir da **Fase 1.6**, as entidades (`Partner`, `Project`, `ApiKey`,
`Contractor`, `User`) são expostas **diretamente** como `#[ApiResource]` com
grupos de serialização por método (`:post`, `:get`, `:patch`). Os DTOs de
input/output dessas entidades foram removidos.

| Recurso | Contrato |
|---|---|
| `Partner`, `Project`, `ApiKey`, `Contractor`, `User` | `#[ApiResource]` na própria entidade + `#[Groups]` |
| `Token` | DTOs (`TokenCreateInput`, `TokenOutput`, ...) em `ApiResource/Token/` |

**Recursos e rotas:**

| Recurso | Rotas |
|---|---|
| `Partner` | `GET/POST /partners`, `GET/PATCH/DELETE /partners/{id}` |
| `Project` | `GET/POST /partners/{partnerId}/projects`, `GET/PATCH/DELETE /projects/{id}` |
| `ApiKey` | `GET/POST /projects/{projectId}/api-keys`, `GET/PATCH/DELETE /api-keys/{id}` |
| `Contractor` | `GET/POST /partners/{partnerId}/contractors`, `GET/PATCH/DELETE /contractors/{id}` |
| `User` | `GET/POST /contractors/{contractorId}/users`, `GET/PATCH/DELETE /users/{id}` |
| `Token` | `POST /token/create`, `/token/refresh`, `/token/verify`, `/token/revoke` |

> **Token é especial:** todas as operações são `POST` (cada uma carrega um segredo
> no corpo), mas têm finalidades distintas. Por isso cada operação tem `summary`
> e `description` próprios via `OpenApiOperation`.

### 4.2 `Entity/` — modelo de domínio

6 entidades, todas com ULID como PK (via `PrefixedIdGenerator`), soft delete
(`deleted_at`) e timestamps por lifecycle callbacks.

```
Partner ──┬── Project ── ApiKey
          └── Contractor ── User ── RefreshToken
```

| Entidade | Relação | Observação |
|---|---|---|
| `Partner` | raiz | email e documento únicos |
| `Project` | → Partner | agrupa ApiKeys |
| `ApiKey` | → Project | guarda `keyHash`, `keyPrefix`, `keySuffix` (nunca a chave) |
| `Contractor` | → Partner | documento único |
| `User` | → Contractor | implementa `UserInterface` + `PasswordAuthenticatedUserInterface` |
| `RefreshToken` | → User | permite revogação real |

### 4.3 `Repository/` — acesso a dados

Um repositório por entidade. `UserRepository` implementa `PasswordUpgraderInterface`.
`RefreshTokenRepository` tem `findOneByHash()`.

> `find()`/`findBy()` funcionam com **ID prefixado** (ex.: `prt_...`) porque
> `AbstractPrefixedIdType::convertToDatabaseValue` converte a string para o
> ValueObject. Não é preciso `findById()`.

### 4.4 `Service/` — regras de negócio

| Serviço | Responsabilidade |
|---|---|
| `PartnerRegistrar` | cria Partner; valida unicidade de email e documento |
| `ProjectRegistrar` | cria Project vinculado a um Partner |
| `ApiKeyIssuer` | gera a chave (`sk_` + 32 alfanuméricos), guarda hash/prefixo/sufixo; retorna `IssuedApiKey` |
| `ContractorRegistrar` | cria Contractor; valida documento |
| `UserRegistrar` | valida API Key, cria User vinculado ao Contractor |
| `TokenIssuer` | emite access + refresh token |
| `TokenAuthenticator` | valida API Key + username + password |
| `TokenRefresher` | troca refresh token por novo par (rotaciona) |
| `TokenVerifier` | valida access token (JWT) |
| `TokenRevoker` | revoga refresh token |

**Value objects de retorno:** `IssuedApiKey`, `IssuedToken`, `VerifiedToken`.

### 4.4.1 `Service/Security/` — chave de segurança e escopo

| Serviço | Responsabilidade |
|---|---|
| `SecurityKeyContext` | Lê o header `X-Security-Key`, resolve a `ApiKey` (hash + não expirada) e devolve o `SecurityScope`; expõe `keyType()`; cacheia por request; implementa `SecurityScopeProvider` |
| `SecurityScope` | Value object do escopo: `master()`, `partner(Partner)`, `anonymous()`; guarda a entidade `Partner` |
| `SecurityScopeProvider` | Interface (`scope(): SecurityScope`) implementada por `SecurityKeyContext` via `#[AsAlias]`; desacopla o voter da resolução concreta do escopo |
| `SecurityKeyType` | enum `Master` / `Partner` / `Anonymous` |
| `MasterSecurityKey` | Lê `%env(MASTER_SECURITY_KEY)%` e compara com `hash_equals` |
| `MasterScopeGuard` | Garante operações master-only (`assertMaster()`): Master Key permite; anônimo devolve `401`; chave de parceiro devolve `403` |
| `MultiTenantAuthorizationVoter` | `Voter` Symfony (`VIEW`/`EDIT`/`DELETE`): Master Key acessa tudo; chave de parceiro restrita ao próprio `Partner`; negação retorna `false` (o `AccessDecisionManager` gera o 403) |
| `ScopeGuard` | Decide a visibilidade (`allows()`) e a escrita (`assertCanWrite()`) de um `Partner` a partir do `SecurityScope`: Master (global), chave do próprio parceiro, anônimo (401 na escrita) e outro parceiro (404) |
| `ScopedPartnerLookup` / `ScopedProjectLookup` / `ScopedContractorLookup` | Resolvem o recurso-pai respeitando o escopo; fora do escopo lançam 404, e são usados pelos `*CollectionProvider` |

O header é `X-Security-Key`. A chave em texto puro só é exibida uma vez, na
criação (`ApiKeyOutput::$securityKey`). O contexto **não lança exceção** quando o
header está ausente/inválido: devolve `anonymous()`, e cada endpoint decide se
exige a chave. A segregação por parceiro nas listagens é aplicada nos
`*CollectionProvider` via `Scoped*Lookup`: Master Key e escopo anônimo veem tudo; a
chave de parceiro fica restrita ao próprio `Partner`.

### 4.5 `State/` — integração com API Platform

- **Providers** (`ProviderInterface`): leem dados para `GET`.
- **Processors** (`ProcessorInterface`): escrevem dados para `POST`/`PATCH`/`DELETE` (soft-delete via `*DeleteProcessor`).

Cada um recebe o DTO de entrada, delega ao Service/Repository e devolve o DTO de
saída. São a "cola" entre o API Platform e o domínio.

### 4.6 `ValueObject/` e `Doctrine/` — identidade

- `AbstractPrefixedId` + 6 concretos (`PartnerId`, `ProjectId`, `ApiKeyId`,
  `ContractorId`, `UserId`, `RefreshTokenId`): encapsulam prefixo + ULID.
- `AbstractPrefixedIdType` + 6 tipos: mapeiam para `UUID` nativo do Postgres,
  removendo o prefixo ao persistir e adicionando ao hidratar.
- `PrefixedIdGenerator`: `CustomIdGenerator` que gera o ValueObject via o Type.

| Entidade | Prefixo | Tipo Doctrine |
|---|---|---|
| Partner | `prt_` | `partner_id` |
| Project | `prj_` | `project_id` |
| ApiKey | `aky_` | `api_key_id` |
| Contractor | `cnt_` | `contractor_id` |
| User | `usr_` | `user_id` |
| RefreshToken | `rtk_` | `refresh_token_id` |

---

## 5. Configuração (`config/`)

| Arquivo | Conteúdo |
|---|---|
| `packages/api_platform.yaml` | formatos de entrada/saída, título da API |
| `packages/doctrine.yaml` | conexão, tipos de ID registrados, `dbname_suffix` em test |
| `packages/security.yaml` | provider `app_user_provider`, hashing argon2id, firewall |
| `packages/lexik_jwt_authentication.yaml` | chaves JWT |
| `packages/nelmio_cors.yaml` | CORS |
| `packages/validator.yaml` | validação |
| `routes/api_platform.yaml` | rotas do API Platform |
| `services.yaml` | serviços e autowiring |

---

## 6. Testes (`tests/`)

```
tests/
├── bootstrap.php
├── Factory/TestDataFactory.php          # cria dados de teste
├── Unit/
│   ├── Entity/                          # ContractorUnitTest, ProjectUnitTest
│   ├── Service/                         # ApiKey, Contractor, Security, User
│   │   └── Security/SecurityKeyTypeTest.php, SecurityScopeTest.php, SecurityKeyContextPartnerTest.php
│   └── ValueObject/PrefixedIdTest.php
├── Integration/
│   ├── Api/                             # HTTP (WebTestCase)
│   │   ├── PartnerApiTest.php
│   │   ├── ProjectApiTest.php
│   │   ├── ApiKeyApiTest.php
│   │   ├── ContractorApiTest.php
│   │   ├── UserApiTest.php
│   │   └── PartnerSegregationMutationTest.php
│   └── Service/
│       ├── SecurityKeyContextTest.php   # keyType() e exposição do Partner (KernelTestCase)
│       └── UserRegistrarTest.php
└── EndToEnd/TokenFlowTest.php           # ciclo completo de tokens
```

- **Unit** → `KernelTestCase`/puro.
- **Integration/Api** → `WebTestCase` (requisição HTTP real).
- **EndToEnd** → fluxo completo.
- Ambiente `test` usa banco `app_test` com rollback automático
  (`dama/doctrine-test-bundle`).

---

## 7. Infraestrutura

| Arquivo | Papel |
|---|---|
| `compose.yaml` | serviços `app` (FrankenPHP) + `database` (Postgres 16) |
| `compose.override.yaml` | porta fixa do Postgres (`5432:5432`) |
| `Dockerfile` | build multi-stage (vendor + runtime FrankenPHP) |
| `docker/Caddyfile` | worker mode + `php_server` |
| `wrangler.jsonc` / `src/index.ts` | Cloudflare Containers (Worker + container) |
| `bin/publish.sh` | publica no GitHub + Packagist |
| `migrations/` | migrations Doctrine |

---

## 8. Onde mexer para cada tipo de mudança

| Quero… | Mexo em… |
|---|---|
| Adicionar um campo a um recurso | `Entity/` (entidade `#[ApiResource]` + `#[Groups]`) + migration |
| Adicionar uma rota | entidade `#[ApiResource]` (operações) + Provider/Processor em `State/` |
| Mudar regra de negócio | `Service/<área>/` |
| Mudar o contrato da API | entidade (grupos `#[Groups]`) ou `ApiResource/Token/` para Token |
| Adicionar um tipo de ID | `ValueObject/`, `Doctrine/Type/`, registrar em `doctrine.yaml` |
| Mudar o schema | `bin/console make:migration` (nunca `schema:update`) |
| Adicionar um teste | `tests/` na pasta correspondente ao tipo |

---

## 9. Convenções rápidas

- **Sem controllers** — tudo via `ApiResource` + State.
- **Sem DTOs de entidade** (Fase 1.6) — entidades expostas diretamente; só `Token` usa DTOs.
- **Sem PUT** — apenas `POST`, `GET`, `PATCH`.
- **Sem prefixo `/api`** — rotas na raiz.
- **Grupos de serialização por método** (`:post`, `:get`, `:patch`).
- **Sem traits** — classes curtas + CustomGenerator via atributo.
- **Sem Gedmo** — soft delete e timestamps nativos do Doctrine.
- **IDs prefixados** são só apresentação; o banco guarda `UUID`.
