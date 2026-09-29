# Koenma ID

Serviço de identidade e autenticação do ecossistema **PHPRise**. Atua como o
"cadeado no portão" das APIs internas (Nami, Yagami, etc.), autenticando
**parceiros** (máquina-a-máquina, via API Key) e **usuários** (pessoas, via JWT).

![Koenma Jr](docs/assets/koenma-jr.png)

---

## Sumário

1. [Visão geral](#1-visão-geral)
2. [A analogia com Koenma](#2-a-analogia-com-koenma)
3. [Arquitetura e hierarquia de domínio](#3-arquitetura-e-hierarquia-de-domínio)
4. [Stack](#4-stack)
5. [Como subir o sistema](#5-como-subir-o-sistema)
6. [Convenções da API](#6-convenções-da-api)
7. [Passo a passo de uso](#7-passo-a-passo-de-uso)
   - [7.1 Criar um Partner](#71-criar-um-partner)
   - [7.2 Criar um Project](#72-criar-um-project)
   - [7.3 Criar uma API Key](#73-criar-uma-api-key)
   - [7.4 Criar um Contractor](#74-criar-um-contractor)
   - [7.5 Criar um User](#75-criar-um-user)
   - [7.6 Gerar tokens (create)](#76-gerar-tokens-create)
   - [7.7 Renovar o access token (refresh)](#77-renovar-o-access-token-refresh)
   - [7.8 Validar um token (verify)](#78-validar-um-token-verify)
   - [7.9 Revogar um refresh token (revoke)](#79-revogar-um-refresh-token-revoke)
8. [Referência dos endpoints](#8-referência-dos-endpoints)
9. [Códigos de erro](#9-códigos-de-erro)
10. [Documentação interativa](#10-documentação-interativa)
11. [Desenvolvimento](#11-desenvolvimento)
12. [Testes](#12-testes)

---

## 1. Visão geral

O **Koenma ID** é o serviço central de identidade do ecossistema PHPRise. Ele
resolve um problema simples de enunciar e chato de implementar: **quem pode
entrar, e como provar isso**.

Ele atende dois públicos distintos:

| Público | Quem é | Como se autentica |
|---|---|---|
| **Parceiro** | Uma empresa/organização que contrata a API | **API Key** (máquina-a-máquina) |
| **Usuário** | Uma pessoa dentro de um parceiro | **JWT** (username + password) |

O fluxo de cadastro é uma cascata: um **Partner** cria **Projects**, cada Project
emite **API Keys**, cada Partner também tem **Contractors**, e cada Contractor
agrupa **Users**. Só depois de tudo isso montado é que um usuário consegue
**gerar tokens** e navegar pelas APIs internas.

---

## 2. A analogia com Koenma

**Koenma** é o personagem de *Yu Yu Hakusho* que governa o Mundo Espiritual. Ele
é o filho do Rei Enma, o juiz supremo do além, e ocupa o cargo de **príncipe
regente**: na prática, é ele quem decide quem entra, quem sai e quem tem
permissão para atravessar o portal entre os mundos.

A escolha do nome não é decorativa — o encaixe é quase literal:

| Koenma (personagem) | Koenma ID (sistema) |
|---|---|
| Governa o portal entre o Mundo Espiritual e o Mundo Humano | É o "cadeado no portão" das APIs internas |
| Decide quem pode atravessar o portal | Decide quem pode acessar as APIs (Nami, Yagami, ...) |
| Emite selos e autorizações para os agentes | Emite **API Keys** e **tokens JWT** |
| Distingue categorias de agentes (detetives, espíritos, etc.) | Distingue **Partners**, **Contractors** e **Users** |
| Pode revogar uma autorização antes do prazo | Pode **revogar** um refresh token antes da expiração |
| Mantém registros de quem tem qual permissão | Persiste **RefreshTokens** para revogação real |

Assim como Koenma não deixa ninguém atravessar o portal sem um selo válido, o
Koenma ID não deixa nenhuma requisição passar sem uma credencial válida — e,
quando necessário, **cassa o selo** na hora.

---

## 3. Arquitetura e hierarquia de domínio

```
Partner (contratante da API)
├── Project → ApiKey      (autenticação máquina-a-máquina)
└── Contractor → User     (autenticação de pessoas)
```

- Um **Partner** é a raiz. Tudo pertence a um Partner.
- Um **Project** pertence a um Partner e agrupa **API Keys**.
- Uma **API Key** autentica um Project em chamadas máquina-a-máquina.
- Um **Contractor** pertence a um Partner e agrupa **Users**.
- Um **User** pertence a um Contractor e autentica-se com username + password.

### Identificadores

Todas as entidades usam **ULID** como chave primária, armazenado como `UUID`
nativo do Postgres. Na API, os IDs são exibidos com um **prefixo legível** que
identifica o tipo do recurso:

| Entidade | Prefixo | Exemplo |
|---|---|---|
| Partner | `prt_` | `prt_01M3P7G08SMPKKMTNARPG7170E` |
| Project | `prj_` | `prj_01M3P7G0CGS57VC42QW5P11AWD` |
| ApiKey | `aky_` | `aky_01M3P7G0G193ET1CQTR47EDQPD` |
| Contractor | `cnt_` | `cnt_01M3P7G0...` |
| User | `usr_` | `usr_01M3P7G0...` |
| RefreshToken | `rtk_` | `rtk_01M3P7G0...` |

> O prefixo é **apenas apresentação**. O banco guarda somente o ULID convertido
> para `UUID`. Você pode usar o ID prefixado livremente em qualquer rota.

---

## 4. Stack

| Componente | Versão |
|---|---|
| PHP | 8.5 |
| Symfony | 8.1 |
| API Platform | 4.3 |
| Doctrine ORM | 3.7 |
| PostgreSQL | 16 |
| Lexik JWT | 3.2 |
| PHPUnit | 13 |

---

## 5. Como subir o sistema

### Pré-requisitos

- Docker e Docker Compose
- (Opcional, para desenvolvimento local) PHP 8.5 e Composer

### Subir com Docker (recomendado)

```bash
# 1. Clone o repositório
git clone <url-do-repositorio> koenma-id
cd koenma-id

# 2. Suba os containers (app + database)
docker compose up -d

# 3. Confirme que ambos estão healthy
docker compose ps

# 4. Rode as migrations
docker compose exec app bin/console doctrine:migrations:migrate --no-interaction

# 5. Verifique se a API responde
curl -s -o /dev/null -w '%{http_code}\n' http://localhost/partners
# Esperado: 200
```

A API estará disponível em **http://localhost**.

> **Ao retomar o trabalho após desligar a máquina:** o Docker derruba os
> containers. Rode `docker compose up -d` novamente. Se o `app` ficar
> `unhealthy` com erro `could not translate host name "database"`, é porque o
> container do banco caiu: `docker compose up -d database` resolve.

### Subir localmente (sem Docker para a app)

```bash
# 1. Suba apenas o banco
docker compose up -d database

# 2. Instale as dependências
composer install

# 3. Rode as migrations
bin/console doctrine:migrations:migrate --no-interaction

# 4. Suba o servidor de desenvolvimento
symfony serve -d
# ou: php -S localhost:8000 -t public
```

---

## 6. Convenções da API

- **Sem prefixo `/api`**: as rotas ficam na raiz (`/partners`, `/token/create`).
- **Formatos de saída**: JSON-LD, JSON, XML, CSV.
- **Formatos de entrada**: JSON e JSON-LD.
- **PATCH**: `application/merge-patch+json` ou `application/json`.
- **Não usamos PUT**: apenas `POST`, `GET` e `PATCH`.
- **Autenticação**: atualmente **desabilitada** (firewall `main` com
  `security: false`). O modelo de permissões de API Key será definido antes de
  integrar com os demais sistemas.

### Content-Type nos exemplos

Todos os exemplos abaixo usam `Content-Type: application/json`. Para respostas
em JSON puro (sem o envelope JSON-LD), envie o cabeçalho:

```
Accept: application/json
```

---

## 7. Passo a passo de uso

O fluxo completo, na ordem correta:

```
Partner → Project → ApiKey → Contractor → User → Tokens
```

> **Dica:** os exemplos usam `jq` para formatar o JSON. Se não tiver, remova o
> `| jq` e leia a saída crua. Também é possível encadear os comandos capturando
> os IDs em variáveis de shell, como mostrado no final de cada seção.

### 7.1 Criar um Partner

O Partner é a raiz de tudo. Ele precisa de um **nome**, um **email** e um
**documento** (ambos únicos no sistema).

```bash
curl -X POST http://localhost/partners \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Acme Corporation",
    "emailAddress": "contact@acme.example.com",
    "document": "12345678000199"
  }'
```

**Resposta (201 Created):**

```json
{
  "id": "prt_01M3P7G08SMPKKMTNARPG7170E",
  "name": "Acme Corporation",
  "emailAddress": "contact@acme.example.com",
  "emailVerified": false,
  "document": "12345678000199",
  "active": true,
  "createdAt": "2026-09-29T09:21:22+00:00"
}
```

Guarde o `id` — ele será usado em todas as etapas seguintes.

```bash
PARTNER_ID="prt_01M3P7G08SMPKKMTNARPG7170E"
```

**Erros possíveis:**

| Status | Motivo |
|---|---|
| `422` | Campo obrigatório ausente, email inválido ou documento longo demais |
| `409` | Email ou documento já cadastrado |

### 7.2 Criar um Project

Um Project pertence a um Partner e agrupa as API Keys. É o "produto" que o
parceiro está integrando.

```bash
curl -X POST "http://localhost/partners/$PARTNER_ID/projects" \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Portal do Cliente",
    "description": "Integração do portal web com as APIs internas"
  }'
```

**Resposta (201 Created):**

```json
{
  "id": "prj_01M3P7G0CGS57VC42QW5P11AWD",
  "partnerId": "prt_01M3P7G08SMPKKMTNARPG7170E",
  "name": "Portal do Cliente",
  "description": "Integração do portal web com as APIs internas",
  "active": true,
  "createdAt": "2026-09-29T09:21:22+00:00"
}
```

```bash
PROJECT_ID="prj_01M3P7G0CGS57VC42QW5P11AWD"
```

**Erros possíveis:**

| Status | Motivo |
|---|---|
| `404` | Partner não encontrado |
| `422` | Nome ausente ou descrição longa demais |

### 7.3 Criar uma API Key

A API Key autentica o Project em chamadas máquina-a-máquina.

> **ATENÇÃO — leia com atenção.**
>
> A chave em texto puro é exibida **uma única vez**, na resposta desta criação.
> Ela **não é armazenada** no banco: guardamos apenas o hash SHA-256, o prefixo
> e o sufixo. Se você perder a chave, **não há como recuperá-la** — será
> necessário gerar outra.

```bash
curl -X POST "http://localhost/projects/$PROJECT_ID/api-keys" \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Chave de Produção",
    "expiresInDays": 365
  }'
```

**Resposta (201 Created):**

```json
{
  "id": "aky_01M3P7G0G193ET1CQTR47EDQPD",
  "projectId": "prj_01M3P7G0CGS57VC42QW5P11AWD",
  "name": "Chave de Produção",
  "keyPrefix": "sk_9OqPu",
  "keySuffix": "Ez775exr",
  "expiresAt": "2027-09-29T09:21:22+00:00",
  "createdAt": "2026-09-29T09:21:22+00:00",
  "key": "sk_9OqPu4m0VRfEwPc9x6fRejjQEz775exr"
}
```

O campo **`key`** contém a chave em texto puro. **Copie e guarde agora.**

```bash
API_KEY="sk_9OqPu4m0VRfEwPc9x6fRejjQEz775exr"
```

**Formato da chave:** `sk_` seguido de 32 caracteres alfanuméricos
(`/^sk_[A-Za-z0-9]{32}$/`).

**O campo `key` NÃO aparece em nenhuma outra resposta.** Consulte a chave depois
e você verá apenas os metadados:

```bash
curl "http://localhost/api-keys/aky_01M3P7G0G193ET1CQTR47EDQPD"
```

```json
{
  "id": "aky_01M3P7G0G193ET1CQTR47EDQPD",
  "projectId": "prj_01M3P7G0CGS57VC42QW5P11AWD",
  "name": "Chave de Produção",
  "keyPrefix": "sk_9OqPu",
  "keySuffix": "Ez775exr",
  "expiresAt": "2027-09-29T09:21:22+00:00",
  "createdAt": "2026-09-29T09:21:22+00:00"
}
```

> Omita `expiresInDays` para criar uma chave que **nunca expira**.

**Erros possíveis:**

| Status | Motivo |
|---|---|
| `404` | Project não encontrado |
| `422` | Nome ausente ou `expiresInDays` não positivo |

### 7.4 Criar um Contractor

Um Contractor pertence a um Partner e agrupa os Users. Pense nele como o
"cliente" ou "organização" dentro do parceiro.

```bash
curl -X POST "http://localhost/partners/$PARTNER_ID/contractors" \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Filial São Paulo",
    "document": "98765432000188"
  }'
```

**Resposta (201 Created):**

```json
{
  "id": "cnt_01M3P7G0H5Y8ZK3QW9X2N4T6VB",
  "partnerId": "prt_01M3P7G08SMPKKMTNARPG7170E",
  "name": "Filial São Paulo",
  "document": "98765432000188",
  "active": true,
  "createdAt": "2026-09-29T09:21:22+00:00"
}
```

```bash
CONTRACTOR_ID="cnt_01M3P7G0H5Y8ZK3QW9X2N4T6VB"
```

**Erros possíveis:**

| Status | Motivo |
|---|---|
| `404` | Partner não encontrado |
| `409` | Documento já cadastrado |
| `422` | Nome ou documento ausente |

### 7.5 Criar um User

O User é a pessoa que vai autenticar com username e password. A criação exige
uma **API Key válida** do Partner que possui o Contractor.

> Se o Contractor informado (`contractorDocument`) ainda não existir, ele é
> criado automaticamente. Se já existir, o User é vinculado a ele.

```bash
curl -X POST "http://localhost/users" \
  -H 'Content-Type: application/json' \
  -d '{
    "apiKey": "sk_9OqPu4m0VRfEwPc9x6fRejjQEz775exr",
    "contractorName": "Filial São Paulo",
    "contractorDocument": "98765432000188",
    "username": "joao.silva",
    "emailAddress": "joao.silva@acme.example.com",
    "password": "S3nh4-F0rte!"
  }'
```

**Resposta (201 Created):**

```json
{
  "id": "usr_01M3P7G0J7K9M2N4P6Q8R0S2TU",
  "contractorId": "cnt_01M3P7G0H5Y8ZK3QW9X2N4T6VB",
  "username": "joao.silva",
  "emailAddress": "joao.silva@acme.example.com",
  "emailVerified": false,
  "active": true,
  "createdAt": "2026-09-29T09:21:22+00:00"
}
```

**Regras de validação:**

| Campo | Regra |
|---|---|
| `username` | 3 a 180 caracteres, único |
| `emailAddress` | Email válido |
| `password` | 8 a 255 caracteres |

**Erros possíveis:**

| Status | Motivo |
|---|---|
| `401` | API Key inválida, expirada ou revogada |
| `409` | Username já cadastrado |
| `422` | Campos inválidos |

### 7.6 Gerar tokens (create)

Com o User criado, é possível autenticar e obter um par de tokens. A operação
`/token/create` exige a **API Key**, o **username** e a **password**.

```bash
curl -X POST http://localhost/token/create \
  -H 'Content-Type: application/json' \
  -d '{
    "apiKey": "sk_9OqPu4m0VRfEwPc9x6fRejjQEz775exr",
    "username": "joao.silva",
    "password": "S3nh4-F0rte!"
  }'
```

**Resposta (200 OK):**

```json
{
  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refreshToken": "a3f1c9e2b7d4...",
  "tokenType": "Bearer",
  "expiresIn": 0
}
```

| Campo | Descrição |
|---|---|
| `accessToken` | JWT assinado, usado para autenticar as requisições seguintes |
| `refreshToken` | Token opaco, usado para obter um novo access token após a expiração |
| `tokenType` | Esquema esperado no cabeçalho `Authorization` (`Bearer`) |
| `expiresIn` | Tempo de vida do access token em segundos |

```bash
ACCESS_TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
REFRESH_TOKEN="a3f1c9e2b7d4..."
```

**Erros possíveis:**

| Status | Motivo |
|---|---|
| `401` | API Key inválida, username inexistente ou password incorreta |
| `422` | Campos obrigatórios ausentes |

> **Nota de segurança:** a API Key precisa pertencer ao **mesmo Partner** do
> usuário. Um usuário de um Partner não consegue autenticar com a API Key de
> outro.

### 7.7 Renovar o access token (refresh)

Quando o access token expirar, use o refresh token para obter um novo par. O
refresh token antigo é **automaticamente revogado** (rotação de tokens).

```bash
curl -X POST http://localhost/token/refresh \
  -H 'Content-Type: application/json' \
  -d '{
    "refreshToken": "a3f1c9e2b7d4..."
  }'
```

**Resposta (200 OK):**

```json
{
  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refreshToken": "b8e2d0f3c1a5...",
  "tokenType": "Bearer",
  "expiresIn": 0
}
```

> **Importante:** guarde o **novo** `refreshToken`. O anterior deixa de
> funcionar imediatamente após o refresh.

**Erros possíveis:**

| Status | Motivo |
|---|---|
| `401` | Refresh token inválido, expirado ou já revogado |
| `422` | Campo `refreshToken` ausente |

### 7.8 Validar um token (verify)

Verifica se um access token é válido e retorna informações sobre ele, **sem
consumi-lo**.

```bash
curl -X POST http://localhost/token/verify \
  -H 'Content-Type: application/json' \
  -d '{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
  }'
```

**Resposta (200 OK) — token válido:**

```json
{
  "valid": true,
  "username": "joao.silva",
  "expiresAt": "2026-09-29T10:21:22+00:00"
}
```

**Resposta (200 OK) — token inválido ou expirado:**

```json
{
  "valid": false,
  "username": null,
  "expiresAt": null
}
```

> A operação sempre responde `200`. O campo `valid` indica o resultado.

### 7.9 Revogar um refresh token (revoke)

Invalida um refresh token **antes** da expiração natural. Use para encerrar uma
sessão ou responder a um vazamento suspeito.

```bash
curl -X POST http://localhost/token/revoke \
  -H 'Content-Type: application/json' \
  -d '{
    "refreshToken": "b8e2d0f3c1a5..."
  }'
```

**Resposta (200 OK):**

```json
{
  "revoked": true
}
```

Após a revogação, qualquer tentativa de usar esse refresh token em
`/token/refresh` retorna `401`.

**Erros possíveis:**

| Status | Motivo |
|---|---|
| `401` | Refresh token inválido |
| `422` | Campo `refreshToken` ausente |

### Fluxo completo em um único script

```bash
#!/usr/bin/env bash
set -euo pipefail

BASE="http://localhost"
SUFFIX="$(date +%s)"

# 1. Partner
PARTNER_ID=$(curl -s -X POST "$BASE/partners" \
  -H 'Content-Type: application/json' \
  -d "{\"name\":\"Acme $SUFFIX\",\"emailAddress\":\"acme$SUFFIX@example.com\",\"document\":\"doc-$SUFFIX\"}" \
  | jq -r '.id')
echo "Partner: $PARTNER_ID"

# 2. Project
PROJECT_ID=$(curl -s -X POST "$BASE/partners/$PARTNER_ID/projects" \
  -H 'Content-Type: application/json' \
  -d "{\"name\":\"Portal $SUFFIX\"}" \
  | jq -r '.id')
echo "Project: $PROJECT_ID"

# 3. ApiKey (guarde a chave!)
API_KEY=$(curl -s -X POST "$BASE/projects/$PROJECT_ID/api-keys" \
  -H 'Content-Type: application/json' \
  -d '{"name":"Chave de Teste"}' \
  | jq -r '.key')
echo "ApiKey: $API_KEY"

# 4. Contractor
CONTRACTOR_ID=$(curl -s -X POST "$BASE/partners/$PARTNER_ID/contractors" \
  -H 'Content-Type: application/json' \
  -d "{\"name\":\"Filial $SUFFIX\",\"document\":\"cnt-$SUFFIX\"}" \
  | jq -r '.id')
echo "Contractor: $CONTRACTOR_ID"

# 5. User
curl -s -X POST "$BASE/users" \
  -H 'Content-Type: application/json' \
  -d "{\"apiKey\":\"$API_KEY\",\"contractorName\":\"Filial $SUFFIX\",\"contractorDocument\":\"cnt-$SUFFIX\",\"username\":\"user$SUFFIX\",\"emailAddress\":\"user$SUFFIX@example.com\",\"password\":\"S3nh4-F0rte!\"}" \
  | jq -r '.id'

# 6. Tokens
TOKENS=$(curl -s -X POST "$BASE/token/create" \
  -H 'Content-Type: application/json' \
  -d "{\"apiKey\":\"$API_KEY\",\"username\":\"user$SUFFIX\",\"password\":\"S3nh4-F0rte!\"}")
ACCESS_TOKEN=$(echo "$TOKENS" | jq -r '.accessToken')
REFRESH_TOKEN=$(echo "$TOKENS" | jq -r '.refreshToken')
echo "Access token: ${ACCESS_TOKEN:0:40}..."

# 7. Verify
curl -s -X POST "$BASE/token/verify" \
  -H 'Content-Type: application/json' \
  -d "{\"token\":\"$ACCESS_TOKEN\"}" | jq

# 8. Refresh
REFRESHED=$(curl -s -X POST "$BASE/token/refresh" \
  -H 'Content-Type: application/json' \
  -d "{\"refreshToken\":\"$REFRESH_TOKEN\"}")
NEW_REFRESH=$(echo "$REFRESHED" | jq -r '.refreshToken')
echo "Refreshed."

# 9. Revoke
curl -s -X POST "$BASE/token/revoke" \
  -H 'Content-Type: application/json' \
  -d "{\"refreshToken\":\"$NEW_REFRESH\"}" | jq
```

---

## 8. Referência dos endpoints

### Partners

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/partners` | Lista todos os partners |
| `GET` | `/partners/{id}` | Retorna um partner |
| `POST` | `/partners` | Cria um partner |
| `PATCH` | `/partners/{id}` | Atualiza nome ou email |

### Projects

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/partners/{partnerId}/projects` | Lista os projects de um partner |
| `GET` | `/projects/{id}` | Retorna um project |
| `POST` | `/partners/{partnerId}/projects` | Cria um project |
| `PATCH` | `/projects/{id}` | Atualiza nome ou descrição |

### API Keys

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/projects/{projectId}/api-keys` | Lista as API Keys de um project |
| `GET` | `/api-keys/{id}` | Retorna uma API Key (sem a chave em texto puro) |
| `POST` | `/projects/{projectId}/api-keys` | Cria uma API Key (**exibe a chave uma única vez**) |
| `PATCH` | `/api-keys/{id}` | Atualiza o nome |

### Contractors

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/partners/{partnerId}/contractors` | Lista os contractors de um partner |
| `GET` | `/contractors/{id}` | Retorna um contractor |
| `POST` | `/partners/{partnerId}/contractors` | Cria um contractor |
| `PATCH` | `/contractors/{id}` | Atualiza o nome |

### Users

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/contractors/{contractorId}/users` | Lista os users de um contractor |
| `GET` | `/users/{id}` | Retorna um user |
| `POST` | `/users` | Cria um user (exige API Key) |
| `PATCH` | `/users/{id}` | Atualiza username, email ou password |

### Tokens

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/token/create` | Autentica e emite access + refresh token |
| `POST` | `/token/refresh` | Troca um refresh token por um novo par |
| `POST` | `/token/verify` | Valida um access token e retorna informações |
| `POST` | `/token/revoke` | Revoga um refresh token antes da expiração |

---

## 9. Códigos de erro

| Status | Significado |
|---|---|
| `200` | Sucesso |
| `201` | Recurso criado |
| `401` | Credencial inválida (API Key, password ou token) |
| `404` | Recurso não encontrado |
| `409` | Conflito (email, documento ou username já cadastrado) |
| `422` | Erro de validação no payload |

**Exemplo de erro de validação (422):**

```json
{
  "@context": "/contexts/ConstraintViolationList",
  "@type": "ConstraintViolationList",
  "hydra:title": "An error occurred",
  "hydra:description": "name: This value should not be blank.",
  "violations": [
    {
      "propertyPath": "name",
      "message": "This value should not be blank."
    }
  ]
}
```

---

## 10. Documentação interativa

O Koenma ID expõe documentação OpenAPI gerada automaticamente a partir dos
atributos do código. Acesse:

- **Swagger UI / ReDoc / Scalar:** http://localhost/docs
- **OpenAPI JSON:** `curl -H 'Accept: application/vnd.openapi+json' http://localhost/docs`

Cada operação tem `summary` e `description` próprios, e cada campo dos DTOs é
documentado. Os quatro endpoints de Token são descritos individualmente, já que
todos usam `POST` mas têm finalidades distintas.

---

## 11. Desenvolvimento

### Comandos úteis

```bash
# Console
bin/console about
bin/console debug:router
bin/console debug:container
bin/console lint:container

# Doctrine
bin/console doctrine:migrations:migrate --no-interaction
bin/console doctrine:schema:validate --skip-sync
bin/console make:migration

# Cache
bin/console cache:clear
```

### Estrutura de pastas

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
├── Repository/         # Repositórios
├── Service/            # Regras de negócio (Registrars, Issuers, Token)
├── State/              # Providers e Processors do API Platform
└── ValueObject/        # AbstractPrefixedId + 6 IDs (prefixo + ULID)
```

### Migrations

Alterações de schema passam **sempre** por migrations:

```bash
bin/console make:migration
bin/console doctrine:migrations:migrate --no-interaction
```

Nunca use `doctrine:schema:update` nem SQL escrito à mão.

---

## 12. Testes

```bash
# Suíte completa
bin/phpunit

# Um arquivo específico
bin/phpunit tests/Integration/Api/ApiKeyApiTest.php

# Com filtro por nome
bin/phpunit --filter testExposesPlainKeyOnlyOnCreation
```

A suíte cobre:

- **Unit**: ValueObjects de ID (`PrefixedIdTest`)
- **Integration**: serviços (`UserRegistrarTest`) e API HTTP de todas as
  entidades (`PartnerApiTest`, `ProjectApiTest`, `ApiKeyApiTest`,
  `ContractorApiTest`, `UserApiTest`)
- **End-to-end**: ciclo completo de tokens (`TokenFlowTest`)

O ambiente de teste usa o banco `app_test` com rollback automático entre testes
(`dama/doctrine-test-bundle`).

---

## Licença

MIT - livre para uso.
