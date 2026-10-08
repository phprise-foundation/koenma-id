# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/)
e o projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---

## [Unreleased]

### Adicionado

- **Segregação por parceiro na consulta, atualização e exclusão** (RFC-002-4/5/6):
  - `GET /partners/{id}` passa a usar `ScopedPartnerLookup`: a chave de parceiro vê só o próprio `Partner`; fora do escopo retorna 404
  - `PATCH` de Partner/Project/Contractor/ApiKey/User respeita o escopo: a chave de parceiro atualiza apenas recursos do próprio `Partner` (404 fora dele); `PATCH /partners/{id}` passa a aceitar a chave do próprio parceiro (antes exigia Master Key)
  - Novas operações `DELETE` (soft-delete) para `Partner`, `Project`, `Contractor` e `User`: a Master Key exclui qualquer recurso e a chave de parceiro apenas os do próprio `Partner` (401 sem chave, 404 fora do escopo)
  - `DELETE /api-keys/{id}` alinhado a 403/404: esconder a API Key de outro parceiro agora retorna 404 (antes 401)
  - `ScopeGuard::assertCanWrite()` centraliza a decisão de escrita (Master Key, chave do próprio parceiro ou anônimo)
- **Correções de robustez** nos `*ItemProvider`: `ContractorItemProvider` passa a usar `ScopedContractorLookup` e `ApiKeyItemProvider` valida `Project` nulo, eliminando riscos de NPE em `getPartner()->getId()` / `getProject()->getId()`

### Testes

- `ScopeGuardTest`: 4 testes novos para `assertCanWrite()` (Master, próprio parceiro, anônimo 401, outro parceiro 404)
- `PartnerSegregationMutationTest` (funcional): isolamento multi-tenant de consulta, atualização e exclusão para Partner, Project, Contractor, ApiKey e User
- `ApiKeyApiTest`: a deleção de API Key de outro parceiro passa a esperar 404
- Suíte total: **194 testes, 433 assertions** (verde)

## [0.1.8] — 2026-10-08

### Corrigido

- **`ApiKeyCollectionProviderTest`** tentava duplicar a classe `final` `ScopedProjectLookup` (erro "Class ScopedProjectLookup is final and cannot be doubled"); agora monta uma instância real de `ScopedProjectLookup` com `ProjectRepository` stubado e `ScopeGuard` real (`SecurityScopeProvider` stubado), seguindo o padrão de `ScopedProjectLookupTest`

### Testes

- `ApiKeyCollectionProviderTest` (unitário): escopo irrestrito, parceiro escopado dono do projeto e projeto de outro parceiro (404)
- Suíte total: **168 testes, 369 assertions** (verde)

## [0.1.7] — 2026-10-08

### Adicionado

- **Segregação por parceiro nas listagens** (`GET /partners`, `/partners/{partnerId}/projects`, `/partners/{partnerId}/contractors`, `/contractors/{contractorId}/users`, `/projects/{projectId}/api-keys`):
  - chave de parceiro vê apenas os recursos do próprio `Partner`; ao informar o pai de outro parceiro a resposta é 404
  - Master Key e requisições sem chave de parceiro mantêm a visão irrestrita (compatível com o healthcheck atual)
- **Serviços de escopo** `ScopeGuard`, `ScopedPartnerLookup`, `ScopedProjectLookup` e `ScopedContractorLookup`, que centralizam a decisão de visibilidade usada pelos `*CollectionProvider`

### Testes

- `ScopeGuardTest`, `ScopedPartnerLookupTest`, `ScopedProjectLookupTest`, `ScopedContractorLookupTest` (unitários)
- `PartnerSegregationListingTest` (funcional): Master Key x chave de parceiro x 404 fora do escopo em todas as listagens
- Suíte total: **165 testes, 366 assertions** (verde)

## [0.1.6] — 2026-10-08

### Adicionado

- **Voter de autorização multi-tenant** (`MultiTenantAuthorizationVoter`, atributos `VIEW`/`EDIT`/`DELETE`):
  - Master Key tem acesso global a qualquer recurso/escopo
  - chave de parceiro restrita ao `Partner` do próprio escopo (via `Partner` e `Project`)
  - subject não suportado (`stdClass`) abstém (`ACCESS_ABSTAIN`); acesso negado retorna `false` (o `AccessDecisionManager` gera o 403)
- **Interface `SecurityScopeProvider`**, implementada por `SecurityKeyContext` via `#[AsAlias]`; `SecurityScope` excluído do auto-registro em `services.yaml`

### Corrigido

- `lint:container` quebrava porque `SecurityScope` (construtor privado) era auto-registrado como serviço; agora é excluído e o voter é um `security.voter` válido

### Testes

- `MultiTenantAuthorizationVoterTest` (unitário, 8 testes) exercitando a API pública `Voter::vote()`
- Suíte total: **131 testes, 311 assertions** (verde)

---

## [0.1.5] — 2026-10-08

### Adicionado

- **Testes da distinção entre Master Key e chave de parceiro** (`SecurityKeyMasterPartnerTest`, unitário):
  - Master Key resolve para `SecurityKeyType::Master`, com escopo master e sem `Partner`
  - chave de parceiro válida resolve para `SecurityKeyType::Partner`, expondo o `Partner`
  - a Master Key nunca é consultada como chave de parceiro; a chave de parceiro nunca é resolvida como master
  - escopos master e de parceiro são mutuamente exclusivos

### Testes

- Suíte total: **123 testes, 301 assertions** (verde)

---

## [0.1.4] — 2026-10-08

### Adicionado

- **Testes da exposição do `Partner` no `SecurityKeyContext`**
  (`SecurityKeyContextPartnerTest`, unitário):
  - escopo de parceiro expõe a entidade `Partner` e seus dados (`getPartner()`)
  - Master, anônimo, chave desconhecida, deletada e expirada **não** expõem `Partner`
  - o escopo resolvido é cacheado e mantém o mesmo `Partner`
  - contextos distintos expõem o próprio `Partner`, sem vazamento entre si
- **Teste de integração** em `SecurityKeyContextTest`: a entidade `Partner`
  persistida é exposta por `scope()->getPartner()`

### Testes

- Suíte total: **118 testes, 283 assertions** (verde)

---

## [0.1.3] — 2026-10-08

### Adicionado

- **Testes da exposição do `Partner` no `SecurityScope`** (`SecurityScopeTest`):
  - `partnerId()` é derivado de `getPartner()?->getId()` (delegação verificada ao `Partner` exposto)
  - escopos de parceiros distintos expõem o próprio `Partner`, sem vazamento entre si

### Testes

- Suíte total: **108 testes, 255 assertions** (100% verde)

---

## [0.1.2] — 2026-10-06

### Adicionado

- **Testes unitários em `SecurityKeyTypeTest`**: chaves de parceiro deletadas
  (`getDeletedAt() != null`) e expiradas (`isExpired() == true`) resolvem para
  `SecurityKeyType::Anonymous`
- Helper `apiKey()` estendido para aceitar `deletedAt` e `expired`

### Testes

- Suíte total: **106 testes, 247 assertions** (100% verde)

---

## [0.1.1] — 2026-10-06

### Adicionado

- **`SecurityKeyType`** (enum `Master`/`Partner`/`Anonymous`) e `SecurityKeyContext::keyType()`
- **Testes de integração** em `SecurityKeyContextTest` para `keyType()` (Master, Partner, Anonymous) com dados reais persistidos

### Alterado

- **`SecurityScope`** agora guarda a entidade `Partner` (antes `PartnerId`); `partnerId()` derivado de `getId()`

### Testes

- Suíte total: **104 testes, 243 assertions** (100% verde)

---

## [Unreleased]

### Adicionado

- **Fase 1.6 — Refatoração DTO → Entidade**:
  - Entidades `Partner`, `Project`, `ApiKey`, `Contractor` e `User` expostas diretamente como `#[ApiResource]` + `#[Groups]`
  - DTOs `*Input`/`*Output`/`*PatchInput` e as `*Resource` intermediárias removidos
  - Rotas de `User` restauradas: `GET`/`POST /contractors/{contractorId}/users` e `GET`/`PATCH /users/{id}`
  - `State\User\CreateProvider` criado; `UserPatchProcessor` reescrito para operar na entidade gerenciada (hash de senha via `previous_data`)
  - `password` anotado com `#[ApiProperty(initializable: true)]` para permitir a denormalização no POST
  - `docs/WIP-refactor-dto-to-entity.md` removido
- **Testes**: suíte 100% verde (**86 testes, 191 assertions**)

- **Governança de documentos**:
  - `AGENTS.md` reescrito: a doutrina (`docs/PHILOSOPHY.md`) como regra inegociável, ordem de prioridade dos documentos, fluxo obrigatório (ler antes de iniciar / atualizar ao finalizar) e a regra de escrever arquivos via script (`python`/`sh`)
  - `docs/ARCHITECTURE.md`: mapa de camadas, fluxo de requisição, estrutura de pastas e guia "onde mexer para cada tipo de mudança"
  - `docs/GITFLOW.md`: fluxo enxuto (branch curta → commit conventional → PR → merge `-d` → tag semver → release → `bin/publish.sh`)
  - `CONTRIBUTING.md`: visão geral da filosofia + processo de contribuição
  - `README.md`: seção "Filosofia e documentação" apontando PHILOSOPHY/GUIDE/GITFLOW/CONTRIBUTING
- **Fase 1.5 / Etapa A — Modelo de chave e cabeçalho**:
  - `ApiKeyOutput::$key` renomeado para `$securityKey` (a chave em texto puro só aparece na criação)
  - `ValueObject\SecurityKey`: valida o formato `sk_[A-Za-z0-9]{32}` e expõe `hash()`
  - `Service\Security\SecurityScope`: escopo da requisição (`master` / `partner` / `anonymous`)
  - `Service\Security\SecurityKeyContext`: resolve o header `X-Security-Key` (hash + não expirada), cacheado por request
  - `Service\Security\MasterSecurityKey`: lê `%env(MASTER_SECURITY_KEY)%` e compara com `hash_equals`
  - `.env` ganhou `MASTER_SECURITY_KEY=` (default vazio); `.env.test` ganhou `MASTER_SECURITY_KEY` e `JWT_PASSPHRASE`
  - Testes: `SecurityKeyTest` + `SecurityKeyContextTest`
- **Fase 1.5 / Etapa B — POST /users vinculado ao Contractor**:
  - Rota `POST /contractors/{contractorId}/users` (não cria mais Contractor)
  - `UserInput` sem `apiKey`, `contractorName`, `contractorDocument`
  - `UserRegistrar::register(Contractor, UserInput)` valida a chave via `SecurityKeyContext`
  - `UserPostProcessor` resolve o Contractor pelo `contractorId` (404 se ausente)
  - Unicidade `username` por Contractor (migration `uniq_user_contractor_username`)
  - Testes: `UserApiTest` + `UserRegistrarTest`
- **Fase 1.5 / Etapa C — Chave de segurança nas rotas de token e API Key**:
  - `/token/create` e `/token/verify` usam `X-Security-Key` (sem `apiKey` no payload)
  - `DELETE /api-keys/{id}` (soft-delete, exige chave do Partner dono)
  - GetCollection de API Keys filtra as expiradas (`findActiveByProject`)
  - Master Key obrigatória para criar/editar Partners (`MasterScopeGuard`)
  - Testes: `TokenFlowTest`, `ApiKeyApiTest`, `PartnerApiTest`

### Alterado

- **Versionamento**: adotada a política `0.y.z` (desenvolvimento inicial). A `v1.0.0`
  só será declarada quando todas as fases estiverem prontas. A tag inicial foi
  reescrita de `v1.0.0` para **`v0.1.0`** (exceção única de fundação)
- **Tags imutáveis**: ruleset "Protect version tags" no GitHub bloqueia deleção e
  reescrita de `refs/tags/v*`

- **Política de releases**: passamos de "release só a cada major" para **uma
  release por tag** (cada tag gera sua própria release, com notas geradas; a
  release não se move). Documentado em `docs/GITFLOW.md` §3.1
- **Política de tags**: prefixo `v` documentado explicitamente (`v1.0.0`)
- **`bin/publish.sh`**: remote via SSH e visibilidade configurável (`--public`/`--private`, padrão privado)

### Segurança

- **`JWT_PASSPHRASE` real removido do `.env`** (que estava rastreado pelo git) e movido para `.env.local` (git-ignored); `.env` agora contém apenas defaults
- `.gitignore` reorganizado com bloco `###> local overrides ###` cobrindo `/.env`, `/.env.local`, `/.env.*.local`, `.aider*`
- `image.png` movido para `docs/assets/koenma-jr.png`; README atualizado


- **API Key em texto puro exibida uma única vez na criação**:
  - `ApiKeyIssuer::issue()` agora retorna `IssuedApiKey` (entidade + chave em texto puro)
  - `ApiKeyOutput` ganhou o campo `key` (grupo `api_key:post`), preenchido por `ApiKeyOutput::fromIssued()`
  - O campo `key` aparece **somente** na resposta do POST; GET e PATCH nunca o expõem
  - A chave em texto puro **não é armazenada**: o banco guarda apenas `keyHash`, `keyPrefix` e `keySuffix`
- **Formato da API Key padronizado**: `sk_` + 32 caracteres alfanuméricos (`/^sk_[A-Za-z0-9]{32}$/`)
- **Documentação OpenAPI em inglês** (doc comments nos atributos, sem comentários de código):
  - `description` em todos os `ApiResource` (vira a descrição da tag)
  - `summary` + `description` por operação, via `OpenApiOperation`
  - Doc comments em todos os campos dos DTOs de entrada e saída
- **Endpoints de Token documentados individualmente**: `/token/create`, `/token/refresh`, `/token/verify` e `/token/revoke` deixaram de compartilhar o texto genérico "Creates a Token resource." gerado pelo API Platform
- **`output` explícito nas operações de Token** (`TokenOutput`, `TokenVerifyOutput`, `TokenRevokeOutput`), para que os schemas de resposta apareçam no OpenAPI

### Corrigido

- **`ApiKeyResource` POST**: `normalizationContext` passou a incluir `api_key:post`, para que o campo `key` seja serializado na resposta de criação
- **`summary` não é parâmetro nomeado de `Post`/`Get`** no API Platform 4.3: movido para `openapi: new OpenApiOperation(...)`

### Testes

- `ApiKeyApiTest`: novo teste `testExposesPlainKeyOnlyOnCreation` e asserção de formato `sk_[A-Za-z0-9]{32}` na criação
- Suíte total: **49 testes, 109 assertions**


- **Entidades** (ULID como PK, soft delete via `deleted_at`, timestamps por lifecycle callbacks):
  - `Partner` — contratante da API
  - `Project` — projeto do Partner, agrupa API Keys
  - `ApiKey` — chave máquina-a-máquina (armazenada apenas como hash)
  - `Contractor` — cliente do Partner
  - `User` — usuário final (implementa `UserInterface` e `PasswordAuthenticatedUserInterface`)
  - `RefreshToken` — refresh tokens persistidos para revogação real
- **Repositórios** para todas as entidades, com `UserRepository` implementando `PasswordUpgraderInterface`
- **Migration** `Version20260912141613` criando as 6 tabelas
- **DTOs** de entrada/saída com grupos de serialização segregados por método (`:post`, `:get`, `:patch`)
- **ApiResources** (rotas CRUD) para Partner, Project, ApiKey, Contractor e User
- **`TokenResource`** (ApiResource) com 4 operações POST: `/token/create`, `/token/refresh`, `/token/verify`, `/token/revoke` — substitui o antigo `TokenController`
- **State processors de Token**: `TokenCreateProcessor`, `TokenRefreshProcessor`, `TokenVerifyProcessor`, `TokenRevokeProcessor`
- **`TokenRevokeOutput`** (DTO de resposta da revogação)
- **Providers e Processors** (State do API Platform) para todas as entidades
- **Serviços de domínio**:
  - `PartnerRegistrar` — cria Partner com validação de unicidade (email, documento)
  - `ProjectRegistrar` — cria Project vinculado a um Partner
  - `ApiKeyIssuer` — gera API Key (hash SHA-256, prefixo/sufixo para exibição)
  - `ContractorRegistrar` — cria Contractor com validação de documento
  - `UserRegistrar` — valida API Key, cria Contractor e User (fluxo de cadastro)
- **DTOs de Token**: `TokenCreateInput`, `TokenRefreshInput`, `TokenVerifyInput`, `TokenRevokeInput`, `TokenOutput`, `TokenVerifyOutput`
- **ValueObjects de ID** com prefixo legível por entidade:
  - `AbstractPrefixedId` (base) + `PartnerId` (`prt_`), `ProjectId` (`prj_`), `ApiKeyId` (`aky_`), `ContractorId` (`cnt_`), `UserId` (`usr_`), `RefreshTokenId` (`rtk_`)
  - Cada ID encapsula prefixo + ULID; `fromString` valida o prefixo, `toString` formata
- **Doctrine CustomTypes de ID** (`AbstractPrefixedIdType` + 6 concretos):
  - Mapeiam para `UUID` nativo do Postgres; removem o prefixo ao persistir e o adicionam ao hidratar
- **`PrefixedIdGenerator`** — CustomIdGenerator que gera o ValueObject via o Type registrado
- **Rotas de Token**: `/token/create`, `/token/refresh`, `/token/verify`, `/token/revoke`
- **Serviços de Token**: `TokenIssuer`, `TokenRefresher`, `TokenVerifier`, `TokenRevoker`, `TokenAuthenticator`
- **Testes**: `UserRegistrarTest`, `PartnerApiTest`, `TokenFlowTest`, `PrefixedIdTest` + `TestDataFactory` (16 testes, 37 assertions)
- **Documentação**: `docs/STATE.md` (handoff), `docs/ROADMAP.md` (fases futuras)

### Configurado

- Symfony 8.1.6, API Platform 4.3, Doctrine ORM 3.7, PostgreSQL 16
- Lexik JWT Authentication Bundle 3.2 + par de chaves gerado
- MakerBundle e test-pack (PHPUnit 13)
- Porta fixa do Postgres (`5432:5432`) no `compose.override.yaml`
- `security.yaml`: provider `app_user_provider` (UserRepository), hashing argon2id
- **Autenticação desabilitada temporariamente**: firewall `main` com `security: false` e `access_control` `PUBLIC_ACCESS` (reativar ao definir permissões de API Key)
- **Rotas na raiz** (sem prefixo `/api`): `/partners`, `/projects`, `/token/*`, `/docs`
- API Platform: saída JSON-LD/JSON/XML/CSV; entrada JSON/LD+JSON; PATCH `merge-patch+json`/JSON
- `dama/doctrine-test-bundle` habilitado no ambiente `test` (rollback automático)
- Tipos de ID registrados em `doctrine.yaml`

### Corrigido

- Tabela `user` agora é citada (`#[ORM\Table(name: '`user`')]`) — `user` é palavra reservada no Postgres
- `TestDataFactory` registrado como serviço público no ambiente de teste
- **`AbstractPrefixedIdType::convertToDatabaseValue`** agora aceita string prefixada e converte para ValueObject — `find()`/`findBy()` funcionam nativamente com IDs prefixados (ex.: `prt_...`), eliminando a necessidade de `findById()` em cada repositório
- **`ProjectPostProcessor`/`ProjectCollectionProvider`/`ProjectPatchProcessor`** passaram a resolver corretamente o `partnerId`/`id` prefixado (bug encontrado no tour manual)

### Adicionado

- **Configuração Cloudflare Containers** (Worker + container FrankenPHP)
  - `wrangler.jsonc`, `src/index.ts`, `package.json`, `tsconfig.json`
  - Worker roteia requests para o container e injeta env vars

- **Dockerfile de produção com FrankenPHP** (PHP 8.4 + Caddy embutidos, container único)
  - Multi-stage: `vendor` (composer) + `runtime` (dunglas/frankenphp)
  - `docker/Caddyfile` com worker mode e `php_server`
  - `.dockerignore`, `.env.prod`, healthcheck no `compose.yaml`

### Alterado

- **Renomeação do projeto**: `Enma Auth`/`Enma Daio` → **Koenma ID**
  - Diretório: `/home/esdras/src/fatia-io/enma-daio` → `/home/esdras/src/phprise/koenma-id`
  - Namespace raiz: `App\` → `Phprise\KoenmaID\` (97 arquivos)
  - Pacote Composer: `phprise/koenma-id`
  - Título da API: `Koenma ID API`

### Notas

- **Gedmo não é usado**: `stof/doctrine-extensions-bundle` é incompatível com Symfony 8.1.
  Soft delete e timestamps são resolvidos com recursos nativos do Doctrine.
- **PUT não é utilizado**: apenas POST, GET e PATCH.
- **Prefixo de ID é só apresentação**: o banco guarda apenas o ULID convertido para `UUID`.
