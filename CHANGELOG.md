# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/)
e o projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---

## [Unreleased]

### Adicionado

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

### Alterado

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
