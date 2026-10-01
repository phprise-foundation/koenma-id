# Roadmap — Koenma ID

> Este arquivo descreve o que **ainda não começou**. Para o estado atual, veja
> [`STATE.md`](./STATE.md).

---

## Fase 1 — Entidades + Rotas de Token (em andamento)

**Objetivo:** ter o núcleo de autenticação funcionando de ponta a ponta.

- [x] Entidades e migration
- [x] DTOs com grupos por método
- [x] Rotas CRUD (Partner, Project, ApiKey, Contractor, User)
- [x] Rotas de Token: `/token/create`, `/token/refresh`, `/token/verify`, `/token/revoke`
- [ ] Firewall de segurança (Lexik JWT) — adiado até definirmos o modelo de permissões
- [x] Testes de integração e ponta a ponta

---

## Fase 1.5 — Modelo de Segurança e Escopo por Parceiro (planejada)

**Objetivo:** endurecer o modelo de chaves e isolar dados por parceiro.

- [x] **POST /users** passa a ser `POST /contractors/{contractorId}/users` (não cria mais Contractor)
- [x] Chave de segurança recebida por cabeçalho (`X-Security-Key`), validada por hash e expiração
- [x] Remover `apiKey`, `contractorName`, `contractorDocument` do payload de criação de User
- [x] Unicidade `username` por Contractor (mesmo username permitido em Contractors diferentes) + testes
- [x] Renomear o campo da chave para `securityKey` em todo o sistema (nunca em payloads)
- [ ] Endpoint de **deleção** de API Key (exige a chave no cabeçalho)
- [ ] Filtro de API Keys **não expiradas** no GetCollection
- [ ] `/token/verify` valida também a chave de segurança (mesmo Partner, não expirada)
- [ ] **Master Key** (variável de ambiente) para criar/editar Partners
- [ ] Segregação por parceiro em todos os endpoints (Master vê tudo; chave de parceiro vê só o seu)

---

## Fase 2 — Verificação de Email

**Objetivo:** garantir que o email do Partner é válido antes de liberar operações.

- [ ] Ao cadastrar um Partner, enviar um código para o email
- [ ] Endpoint para validar o código
- [ ] Bloquear criação de Project e ApiKey enquanto `email_verified = false`
- [ ] Reenvio de código

---

## Fase 3 — Endurecimento de Segurança

- [ ] Rotação de refresh tokens (um uso por token)
- [ ] Detecção de reuso de refresh token (revogar família)
- [ ] Rate limiting nas rotas de token
- [ ] Auditoria de emissão/revogação de tokens
- [ ] Expiração configurável por Partner/Project

---

## Fase 4 — Observabilidade e Operação

- [ ] Logs estruturados de autenticação
- [ ] Métricas (tokens emitidos, falhas, latência)
- [ ] Health check
- [ ] Documentação OpenAPI revisada

---

## Fase 5 — Automação de Qualidade e Release (após a refatoração OTAKU)

**Objetivo:** garantir a filosofia e automatizar o versionamento. **Depende** da
fase de refatoração (os hooks de estilo só fazem sentido quando o código já
estiver adequado ao OTAKU Manifesto).

- [ ] **Git hooks de controle de código** (pre-commit / pre-push):
  - `php-cs-fixer` (regras `@Symfony` + Object Calisthenics)
  - PHPStan / Psalm (análise estática)
  - `php -l` nos arquivos alterados
  - `bin/phpunit` antes do push
- [ ] **Proteção de tags** no GitHub (ruleset: impedir deleção/reescrita)
- [ ] **Auto-tagging na `main`** via GitHub Actions:
  - Workflow em `push` para `main` que calcula a próxima versão a partir dos
    commits (Conventional Commits) e cria a tag + release
  - Alternativa: `semantic-release` ou `release-please`
- [ ] **CI** (GitHub Actions): rodar a suíte em cada PR
- [ ] **Release automática** por tag (uma release por tag, com notas geradas)

---

## Ideias futuras (não comprometidas)

- Suporte a múltiplos algoritmos de assinatura (RS256/ES256)
- Escopos/permissões por ApiKey
- Webhooks de eventos de autenticação
- Interface administrativa (gestão de Partners/Projects)
