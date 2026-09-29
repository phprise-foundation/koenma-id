# Roadmap — Koenma ID

> Este arquivo descreve o que **ainda não começou**. Para o estado atual, veja
> [`STATE.md`](./STATE.md).

---

## Fase 1 — Entidades + Rotas de Token (em andamento)

**Objetivo:** ter o núcleo de autenticação funcionando de ponta a ponta.

- [x] Entidades e migration
- [x] DTOs com grupos por método
- [x] Rotas CRUD (Partner, Project, ApiKey, Contractor, User)
- [ ] Rotas de Token: `/token/create`, `/token/refresh`, `/token/verify`, `/token/revoke`
- [ ] Firewall de segurança (Lexik JWT)
- [ ] Testes de integração e ponta a ponta

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

## Ideias futuras (não comprometidas)

- Suporte a múltiplos algoritmos de assinatura (RS256/ES256)
- Escopos/permissões por ApiKey
- Webhooks de eventos de autenticação
- Interface administrativa (gestão de Partners/Projects)
