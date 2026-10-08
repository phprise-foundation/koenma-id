---
id: RFC-002-3-fix-1
status: done
responsible: none
blocked: false
reason: ""
worker: ""
comments: |
    "Hugo: Tarefa atômica derivada da RFC-002-3-fix."
    "Davi: Substituído o helper stubScopedProjectLookup() por instância real de ScopedProjectLookup montada com ProjectRepository stubado e ScopeGuard real (SecurityScopeProvider stubado)."
    "Davi: Cenários cobertos: escopo irrestrito, parceiro escopado dono do projeto e projeto de outro parceiro (NotFoundHttpException). Suíte verde (168 testes / 369 asserções)."
---

# Refatorar ApiKeyCollectionProviderTest para não duplicar a classe final ScopedProjectLookup

Em `tests/Unit/State/ApiKey/ApiKeyCollectionProviderTest.php`, substituir o helper `stubScopedProjectLookup()` (que chama `createStub()` sobre a classe `final` `ScopedProjectLookup` e falha) por uma instância real de `ScopedProjectLookup`, montada com colaboradores stubados, seguindo o padrão já existente em `ScopedProjectLookupTest`:

- `ProjectRepository` stubado: `find()` retorna o `Project` (ou `null`) conforme o caso.
- `ScopeGuard` real, construído com um `SecurityScopeProvider` stubado cujo `scope()` retorna `SecurityScope::master()` (escopo irrestrito), `SecurityScope::partner($partner)` do mesmo parceiro (acesso permitido) ou `SecurityScope::partner($outroPartner)` (acesso negado).

Mapear os três cenários atuais: escopo irrestrito (acesso permitido), parceiro escopado dono do projeto (acesso permitido) e projeto de outro parceiro (acesso negado, `NotFoundHttpException`). Rodar `bin/phpunit` e garantir a suíte verde.
